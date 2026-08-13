<?php

declare(strict_types=1);

namespace app\service\member;

use app\model\member\OrderMemberReward;
use app\model\order\OrderOrder;
use app\model\user\PointsLog;
use app\repository\member\MemberLevelRepository;
use app\repository\member\MemberGrowthLogRepository;
use app\repository\member\OrderMemberRewardAdjustmentRepository;
use app\repository\member\OrderMemberRewardRepository;
use app\repository\order\OrderItemRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\order\OrderRefundRepository;
use app\repository\user\PointsDebtLogRepository;
use app\repository\user\PointsLogRepository;
use app\repository\user\UserRepository;
use app\repository\system\SystemConfigRepository;
use app\service\user\UserManageService;
use app\support\OrderItemAmountAllocator;
use core\base\Service;
use think\facade\Log;

/**
 * 订单完成会员权益的唯一账务入口。
 *
 * 锁序固定为 order_orders -> order_items/order_member_rewards -> users，退款事件和
 * 完成事件因此可以串行；数据库唯一键再负责进程重放的最终幂等裁决。
 */
class OrderMemberRewardService extends Service
{
    private const RECONCILE_BATCH_SIZE = 200;

    protected OrderOrderRepository $orderOrderRepository;
    protected OrderItemRepository $orderItemRepository;
    protected OrderRefundRepository $orderRefundRepository;
    protected OrderMemberRewardRepository $rewardRepository;
    protected OrderMemberRewardAdjustmentRepository $adjustmentRepository;
    protected MemberLevelRepository $memberLevelRepository;
    protected MemberGrowthLogRepository $memberGrowthLogRepository;
    protected UserRepository $userRepository;
    protected PointsLogRepository $pointsLogRepository;
    protected PointsDebtLogRepository $pointsDebtLogRepository;
    protected UserManageService $userManageService;
    protected MemberLevelService $memberLevelService;
    protected SystemConfigRepository $systemConfigRepository;

    /** 管理后台只展示证据状态，不根据当前用户聚合值反推历史发放。 */
    public function getReviewList(array $filters): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(100, max(1, (int)($filters['limit'] ?? 20)));
        $result = $this->rewardRepository->getReviewPage($filters, $page, $limit);
        $result['summary'] = $this->rewardRepository->getReviewSummary();
        return $result;
    }

    /**
     * 人工确认“无更多可归属于本订单的历史聚合权益”。该操作不增减任何资产，
     * 只关闭无法由订单级账本证明的部分；已验证权益仍按退款流水自动冲正。
     */
    public function resolveUnverifiedAsNotAttributed(
        int $rewardId,
        int $operatorId,
        string $reason
    ): array {
        $reason = trim($reason);
        if ($rewardId <= 0 || $operatorId <= 0) {
            $this->throwBusinessException('复核记录或操作人无效');
        }
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 255) {
            $this->throwBusinessException('复核依据需为 5~255 个字符');
        }

        $snapshot = $this->rewardRepository->find($rewardId);
        if (!$snapshot) {
            $this->throwBusinessException('会员权益复核记录不存在');
        }
        $orderId = (int)($snapshot['order_id'] ?? 0);

        return $this->runInTransaction(function () use (
            $rewardId,
            $operatorId,
            $reason,
            $orderId
        ): array {
            if ($orderId <= 0 || !$this->orderOrderRepository->findForUpdate($orderId)) {
                $this->throwBusinessException('复核记录关联订单不存在');
            }
            $reward = $this->rewardRepository->findByIdForUpdate($rewardId);
            if (!$reward || (int)($reward['order_id'] ?? 0) !== $orderId) {
                $this->throwBusinessException('会员权益复核记录已变化');
            }
            $reviewStatus = (string)($reward['review_status'] ?? OrderMemberReward::REVIEW_NONE);
            if ($reviewStatus === OrderMemberReward::REVIEW_RESOLVED) {
                return ['applied' => false, 'reason' => 'already_resolved'];
            }
            if ($reviewStatus !== OrderMemberReward::REVIEW_PENDING
                || (string)($reward['origin'] ?? '') !== OrderMemberReward::ORIGIN_LEGACY_IMPORT) {
                $this->throwBusinessException('仅待复核的历史导入权益可以结案');
            }

            $reviewedAt = date('Y-m-d H:i:s');
            $evidence = $reward['evidence'] ?? [];
            if (is_string($evidence)) {
                $decoded = json_decode($evidence, true);
                $evidence = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($evidence)) {
                $evidence = [];
            }
            $evidence['manual_review'] = [
                'resolution' => 'exclude_unverified',
                'reason' => $reason,
                'operator_id' => $operatorId,
                'reviewed_at' => $reviewedAt,
            ];

            $data = [
                'review_status' => OrderMemberReward::REVIEW_RESOLVED,
                'review_resolution' => 'exclude_unverified',
                'review_reason' => $reason,
                'review_operator_id' => $operatorId,
                'reviewed_at' => $reviewedAt,
                'evidence' => $evidence,
            ];
            if ($this->isFullyVerifiedPortionReversed($reward)) {
                $data['fully_reversed_at'] = ($reward['fully_reversed_at'] ?? null) ?: $reviewedAt;
            }
            if ($this->rewardRepository->resolveReviewIfPending($rewardId, $data) !== 1) {
                $this->throwBusinessException('会员权益复核状态已变化，请刷新后重试');
            }

            $this->adjustmentRepository->create([
                'reward_id' => $rewardId,
                'order_id' => $orderId,
                'refund_id' => null,
                'user_id' => (int)$reward['user_id'],
                'action' => 'review_resolved',
                'event_key' => 'order.member.reward.review:' . $rewardId,
                'refund_amount' => 0,
                'points' => 0,
                'points_credited_reversed' => 0,
                'growth' => 0,
                'consume_amount' => 0,
                'order_count' => 0,
                'points_debt_added' => 0,
                'remark' => mb_substr('人工复核排除未验证聚合权益：' . $reason, 0, 255),
            ]);

            return ['applied' => true, 'reason' => 'resolved'];
        });
    }

    /** @return array{applied:bool,user_id:int,reason:string} */
    public function handleOrderCompleted(array $event): array
    {
        $orderId = (int)($event['order_id'] ?? 0);
        if ($orderId <= 0) {
            return ['applied' => false, 'user_id' => 0, 'reason' => 'invalid_event'];
        }

        $result = $this->runInTransaction(function () use ($orderId, $event): array {
            $order = $this->orderOrderRepository->findForUpdate($orderId);
            if (!$order) {
                return ['applied' => false, 'user_id' => 0, 'reason' => 'order_not_found'];
            }

            $userId = (int)($order['user_id'] ?? 0);
            $status = (string)($order['status'] ?? '');
            if (!in_array($status, [OrderOrder::STATUS_COMPLETED, OrderOrder::STATUS_CLOSED], true)
                || empty($order['receive_time'])) {
                return ['applied' => false, 'user_id' => $userId, 'reason' => 'order_not_completed'];
            }

            $rewardSnapshot = $this->rewardRepository->findByOrderId($orderId);
            if ($rewardSnapshot !== null
                && $this->rewardRepository->findByIdForUpdate((int)$rewardSnapshot['id']) !== null) {
                return ['applied' => false, 'user_id' => $userId, 'reason' => 'already_awarded'];
            }

            $items = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
            if ($items === []) {
                return ['applied' => false, 'user_id' => $userId, 'reason' => 'no_items'];
            }
            foreach ($items as $item) {
                if ((int)($item['refund_status'] ?? 0) === 1) {
                    return ['applied' => false, 'user_id' => $userId, 'reason' => 'refund_processing'];
                }
            }
            $items = $this->ensureItemAmountAllocations($order, $items);

            $conservativeImport = (bool)($event['conservative_import'] ?? false);
            if ($conservativeImport) {
                // 旧监听器按整单 pay_amount 发权益，完全不知道退款行。发布边界前
                // 必须保持旧口径把全部原始行纳入，随后再重放每笔 refunded 事件；
                // 否则“先退款后完成”的旧单会低估已发权益且永远无法冲正该行。
                $eligibleItemIds = array_map(
                    static fn (array $item): int => (int)$item['id'],
                    $items
                );
                $rewardCents = max(0, self::toCents($order['pay_amount'] ?? 0));
            } else {
                [$eligibleItemIds, $rewardCents] = $this->resolveEligibleItemsAtCompletion(
                    $order,
                    $items,
                    (bool)($event['reconstruct_completion_state'] ?? false)
                );
            }
            if ($rewardCents <= 0 || $eligibleItemIds === []) {
                return ['applied' => false, 'user_id' => $userId, 'reason' => 'no_eligible_amount'];
            }

            $user = $this->userRepository->findForUpdate($userId);
            if (!$user) {
                $this->throwBusinessException('用户不存在');
            }

            $pointsRate = 1.0;
            $memberLevelId = (int)($user->member_level_id ?? 0);
            if ($memberLevelId > 0) {
                $level = $this->memberLevelRepository->find($memberLevelId);
                if ($level) {
                    $pointsRate = max(0.0, (float)($level['points_rate'] ?? 1));
                }
            }

            // 原字段始终保留按完成时订单金额计算的理论快照。升级前是否真正发过，
            // 由 verified_* 与 evidence 单独表达，不能再通过把理论值清零来隐式编码。
            $points = (int)floor(($rewardCents / 100) * $pointsRate + 1e-9);
            $growth = intdiv($rewardCents, 100);
            $consumeAmount = self::fromCents($rewardCents);
            $orderCount = 1;
            $source = 'order:' . $orderId;
            $eventKey = 'order.member.reward:' . $orderId;

            // 旧监听器只有积分保留了订单级 source。成长值、累计消费和订单数没有
            // 可归属流水，因此历史导入只能认领并冲正确实有流水证明的积分，不能用
            // 理论值在退款时扣减用户后来由其他订单获得的聚合权益。
            $legacyPointsLogs = $this->pointsLogRepository->getBySourceForUpdate($source);
            $legacyImported = $conservativeImport || $legacyPointsLogs !== [];

            $origin = OrderMemberReward::ORIGIN_NATIVE;
            $verificationStatus = OrderMemberReward::VERIFICATION_VERIFIED;
            $reviewStatus = OrderMemberReward::REVIEW_NONE;
            $verifiedPoints = $points;
            $verifiedPointsCredited = 0;
            $verifiedGrowth = $growth;
            $verifiedConsumeAmount = $consumeAmount;
            $verifiedOrderCount = $orderCount;
            $evidence = [
                'version' => 1,
                'kind' => 'native_atomic_award',
                'event_key' => $eventKey,
            ];

            if ($legacyImported) {
                $inspection = $this->inspectLegacyPointsEvidence(
                    $legacyPointsLogs,
                    $userId,
                    $source
                );
                $origin = OrderMemberReward::ORIGIN_LEGACY_IMPORT;
                $verificationStatus = $inspection['verification_status'];
                $reviewStatus = OrderMemberReward::REVIEW_PENDING;
                $verifiedPoints = $inspection['verified_points'];
                // HEAD 旧版 adjustPoints 只增加 users.points，没有增加 total_points。
                $verifiedPointsCredited = 0;
                $verifiedGrowth = 0;
                $verifiedConsumeAmount = 0.0;
                $verifiedOrderCount = 0;
                $evidence = $inspection['evidence'] + [
                    'theoretical' => [
                        'points' => $points,
                        'growth' => $growth,
                        'consume_amount' => $consumeAmount,
                        'order_count' => $orderCount,
                    ],
                    'unverified_assets' => ['growth', 'consume_amount', 'order_count'],
                ];
            }
            $pointsDebtOffset = $legacyImported
                ? 0
                : min($points, max(0, (int)($user->points_debt ?? 0)));
            $pointsCredited = $legacyImported ? 0 : $points - $pointsDebtOffset;
            if (!$legacyImported) {
                $verifiedPointsCredited = $pointsCredited;
            }

            if (!$legacyImported) {
                $beforeGrowth = (int)($user->growth_value ?? 0);
                if ($points > 0) {
                    $this->userManageService->adjustPoints(
                        $userId,
                        $points,
                        '订单消费奖励',
                        PointsLog::TYPE_CONSUME_AWARD,
                        $source,
                        null,
                        $eventKey . ':points'
                    );
                }
                $this->userRepository->addOrderMemberRewardMetrics(
                    $userId,
                    $growth,
                    $consumeAmount,
                    $orderCount
                );
                if ($growth > 0) {
                    $this->memberGrowthLogRepository->create([
                        'user_id' => $userId,
                        'value' => $growth,
                        'before_growth' => $beforeGrowth,
                        'after_growth' => $beforeGrowth + $growth,
                        'source' => $eventKey . ':growth',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $reward = $this->rewardRepository->create([
                'order_id'                 => $orderId,
                'user_id'                  => $userId,
                'eligible_item_ids'        => $eligibleItemIds,
                'reward_amount'            => self::fromCents($rewardCents),
                'points_rate'              => $pointsRate,
                'points'                   => $points,
                'points_credited'          => $pointsCredited,
                'points_debt_offset'       => $pointsDebtOffset,
                'growth'                   => $growth,
                'consume_amount'           => $consumeAmount,
                'order_count'              => $orderCount,
                'origin'                   => $origin,
                'verification_status'      => $verificationStatus,
                'verified_points'          => $verifiedPoints,
                'verified_points_credited' => $verifiedPointsCredited,
                'verified_growth'          => $verifiedGrowth,
                'verified_consume_amount'  => $verifiedConsumeAmount,
                'verified_order_count'     => $verifiedOrderCount,
                'evidence'                 => $evidence,
                'review_status'            => $reviewStatus,
                'refunded_amount'          => 0,
                'reversed_points'          => 0,
                'reversed_points_credited' => 0,
                'reversed_growth'          => 0,
                'reversed_consume_amount'  => 0,
                'reversed_order_count'     => 0,
                'awarded_at'               => (string)$order['receive_time'],
            ]);

            $this->adjustmentRepository->create([
                'reward_id'                 => (int)$reward['id'],
                'order_id'                  => $orderId,
                'refund_id'                 => null,
                'user_id'                   => $userId,
                'action'                    => $legacyImported ? 'award_imported' : 'award',
                'event_key'                 => $eventKey,
                'refund_amount'             => 0,
                'points'                    => $legacyImported ? 0 : $points,
                'points_credited_reversed'  => 0,
                'growth'                    => $legacyImported ? 0 : $growth,
                'consume_amount'            => $legacyImported ? 0 : $consumeAmount,
                'order_count'               => $legacyImported ? 0 : $orderCount,
                'points_debt_added'          => 0,
                'remark'                    => $legacyImported
                    ? sprintf(
                        '历史订单保守导入：%s，认领可验证积分%d；成长值/消费统计待人工复核',
                        $verificationStatus,
                        $verifiedPoints
                    )
                    : '订单完成会员权益发放',
            ]);

            return ['applied' => true, 'user_id' => $userId, 'reason' => $legacyImported ? 'imported' : 'awarded'];
        });

        if ($result['user_id'] > 0 && in_array(
            $result['reason'],
            ['awarded', 'imported', 'already_awarded'],
            true
        )) {
            $this->memberLevelService->recalculateLevel($result['user_id']);
        }
        return $result;
    }

    /** @param array<string, mixed> $reward */
    private function isFullyVerifiedPortionReversed(array $reward): bool
    {
        $baseCents = self::toCents($reward['reward_amount'] ?? 0);
        return $baseCents > 0
            && self::toCents($reward['refunded_amount'] ?? 0) >= $baseCents
            && (int)($reward['reversed_points'] ?? 0) >= (int)($reward['verified_points'] ?? 0)
            && (int)($reward['reversed_points_credited'] ?? 0)
                >= (int)($reward['verified_points_credited'] ?? 0)
            && (int)($reward['reversed_growth'] ?? 0) >= (int)($reward['verified_growth'] ?? 0)
            && self::toCents($reward['reversed_consume_amount'] ?? 0)
                >= self::toCents($reward['verified_consume_amount'] ?? 0)
            && (int)($reward['reversed_order_count'] ?? 0)
                >= (int)($reward['verified_order_count'] ?? 0);
    }

    /** @return array{applied:bool,user_id:int,reason:string} */
    public function handleRefundCompleted(array $event): array
    {
        $refundId = (int)($event['refund_id'] ?? 0);
        if ($refundId <= 0) {
            return ['applied' => false, 'user_id' => 0, 'reason' => 'invalid_event'];
        }

        $eventKey = 'order.member.reward.refund:' . $refundId;
        $result = $this->runInTransaction(function () use (
            $eventKey,
            $refundId
        ): array {
            // 事件仅携带不可变 refund_id；关联关系、状态和金额全部以加锁后的 DB 行为准。
            $refund = $this->orderRefundRepository->findForUpdate($refundId);
            if (!$refund || (string)($refund['status'] ?? '') !== 'refunded') {
                return ['applied' => false, 'user_id' => 0, 'reason' => 'refund_not_completed'];
            }
            $orderId = (int)($refund['order_id'] ?? 0);
            $orderItemId = (int)($refund['order_item_id'] ?? 0);
            if ($orderId <= 0 || $orderItemId <= 0) {
                return ['applied' => false, 'user_id' => 0, 'reason' => 'refund_relation_invalid'];
            }
            if (!$this->orderOrderRepository->findForUpdate($orderId)) {
                return ['applied' => false, 'user_id' => 0, 'reason' => 'order_not_found'];
            }
            $orderItem = $this->orderItemRepository->findForUpdate($orderItemId);
            if (!$orderItem || (int)($orderItem['order_id'] ?? 0) !== $orderId) {
                $this->throwBusinessException('退款商品与订单关联不一致');
            }
            $reward = $this->rewardRepository->findByOrderIdForUpdate($orderId);
            if (!$reward) {
                // 完成监听失败时，补偿命令会先补奖励、再顺序重放退款。
                return ['applied' => false, 'user_id' => (int)($refund['user_id'] ?? 0), 'reason' => 'reward_not_found'];
            }
            $userId = (int)$reward['user_id'];
            if ($this->adjustmentRepository->findByEventKey($eventKey) !== null) {
                return ['applied' => false, 'user_id' => $userId, 'reason' => 'already_reversed'];
            }

            $eligibleItemIds = array_map('intval', (array)($reward['eligible_item_ids'] ?? []));
            if (!in_array($orderItemId, $eligibleItemIds, true)) {
                $this->createIgnoredRefundAdjustment($reward, $refundId, $eventKey, '退款商品在订单完成前已退款，不属于奖励基数');
                return ['applied' => true, 'user_id' => $userId, 'reason' => 'ignored'];
            }

            $baseCents = self::toCents($reward['reward_amount'] ?? 0);
            if ($baseCents <= 0) {
                $this->createIgnoredRefundAdjustment($reward, $refundId, $eventKey, '奖励基数为零');
                return ['applied' => true, 'user_id' => $userId, 'reason' => 'ignored'];
            }
            if ((int)($refund['user_id'] ?? 0) !== $userId) {
                $this->throwBusinessException('退款用户与奖励快照不一致');
            }
            $refundCents = max(0, self::toCents($refund['refund_amount'] ?? 0));
            $oldRefundedCents = min($baseCents, self::toCents($reward['refunded_amount'] ?? 0));
            $newRefundedCents = min($baseCents, $oldRefundedCents + $refundCents);

            // 退款只能冲正订单级证据明确证明已发放的权益。原 points/growth 等字段
            // 是理论快照，特别是 legacy_import 不能据此扣减用户后续订单所得资产。
            $targetPoints = self::proportionalTarget(
                (int)($reward['verified_points'] ?? 0),
                $newRefundedCents,
                $baseCents
            );
            $targetPointsCredited = self::proportionalTarget(
                (int)($reward['verified_points_credited'] ?? 0),
                $newRefundedCents,
                $baseCents
            );
            $targetGrowth = self::proportionalTarget(
                (int)($reward['verified_growth'] ?? 0),
                $newRefundedCents,
                $baseCents
            );
            $targetConsumeCents = self::proportionalTarget(
                self::toCents($reward['verified_consume_amount'] ?? 0),
                $newRefundedCents,
                $baseCents
            );
            $isFullyRefunded = $newRefundedCents >= $baseCents;
            $targetOrderCount = $isFullyRefunded
                ? (int)($reward['verified_order_count'] ?? 0)
                : 0;
            $hasPendingReview = (string)($reward['review_status'] ?? OrderMemberReward::REVIEW_NONE)
                === OrderMemberReward::REVIEW_PENDING;
            $isFullyReversed = $isFullyRefunded && !$hasPendingReview;

            $pointsDelta = max(0, $targetPoints - (int)($reward['reversed_points'] ?? 0));
            $pointsCreditedDelta = max(
                0,
                $targetPointsCredited - (int)($reward['reversed_points_credited'] ?? 0)
            );
            $growthDelta = max(0, $targetGrowth - (int)($reward['reversed_growth'] ?? 0));
            $consumeDeltaCents = max(
                0,
                $targetConsumeCents - self::toCents($reward['reversed_consume_amount'] ?? 0)
            );
            $orderCountDelta = max(
                0,
                $targetOrderCount - (int)($reward['reversed_order_count'] ?? 0)
            );

            $user = $this->userRepository->findForUpdate($userId);
            if (!$user) {
                $this->throwBusinessException('用户不存在');
            }
            $beforePoints = max(0, (int)($user->points ?? 0));
            $beforeDebt = max(0, (int)($user->points_debt ?? 0));
            $actualPointsDebit = min($beforePoints, $pointsDelta);
            $pointsDebtAdded = $pointsDelta - $actualPointsDebit;
            $afterPoints = $beforePoints - $actualPointsDebit;
            $afterDebt = $beforeDebt + $pointsDebtAdded;
            $afterTotalPoints = max(0, (int)($user->total_points ?? 0) - $pointsCreditedDelta);
            $beforeGrowth = max(0, (int)($user->growth_value ?? 0));
            $afterGrowth = max(0, $beforeGrowth - $growthDelta);
            $afterConsume = max(
                0.0,
                round((float)($user->total_consume ?? 0) - self::fromCents($consumeDeltaCents), 2)
            );
            $afterOrderCount = max(0, (int)($user->order_count ?? 0) - $orderCountDelta);

            if ($pointsDelta > 0 || $growthDelta > 0 || $consumeDeltaCents > 0 || $orderCountDelta > 0) {
                if (!$this->userRepository->updateMemberRewardState(
                    $userId,
                    $afterPoints,
                    $afterDebt,
                    $afterTotalPoints,
                    $afterGrowth,
                    $afterConsume,
                    $afterOrderCount
                )) {
                    $this->throwBusinessException('更新退款后的会员权益失败');
                }
            }

            if ($pointsDelta > 0) {
                $this->pointsLogRepository->create([
                    'user_id'       => $userId,
                    'points'        => -$actualPointsDebit,
                    'before_points' => $beforePoints,
                    'after_points'  => $afterPoints,
                    'type'          => PointsLog::TYPE_REWARD_REVERSAL,
                    'source'        => 'order_refund:' . $refundId,
                    'event_key'     => $eventKey . ':points',
                    'remark'        => '订单退款冲正消费奖励积分',
                    'operator_id'   => null,
                ]);
            }
            if ($pointsDebtAdded > 0) {
                $this->pointsDebtLogRepository->create([
                    'user_id'     => $userId,
                    'delta'       => $pointsDebtAdded,
                    'before_debt' => $beforeDebt,
                    'after_debt'  => $afterDebt,
                    'source'      => 'order_refund:' . $refundId,
                    'event_key'   => $eventKey . ':debt',
                    'remark'      => '奖励积分不足扣回，形成积分债务',
                ]);
            }
            if ($growthDelta > 0) {
                $this->memberGrowthLogRepository->create([
                    'user_id' => $userId,
                    'value' => -$growthDelta,
                    'before_growth' => $beforeGrowth,
                    'after_growth' => $afterGrowth,
                    'source' => $eventKey . ':growth',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->rewardRepository->updateReversalProgress((int)$reward['id'], [
                'refunded_amount'          => self::fromCents($newRefundedCents),
                'reversed_points'          => $targetPoints,
                'reversed_points_credited' => $targetPointsCredited,
                'reversed_growth'          => $targetGrowth,
                'reversed_consume_amount'  => self::fromCents($targetConsumeCents),
                'reversed_order_count'     => $targetOrderCount,
                'fully_reversed_at'        => $isFullyReversed ? date('Y-m-d H:i:s') : null,
            ]);

            $this->adjustmentRepository->create([
                'reward_id'                => (int)$reward['id'],
                'order_id'                 => $orderId,
                'refund_id'                => $refundId,
                'user_id'                  => $userId,
                'action'                   => 'refund_reverse',
                'event_key'                => $eventKey,
                'refund_amount'            => self::fromCents($refundCents),
                'points'                   => -$pointsDelta,
                'points_credited_reversed' => $pointsCreditedDelta,
                'growth'                   => -$growthDelta,
                'consume_amount'           => -self::fromCents($consumeDeltaCents),
                'order_count'              => -$orderCountDelta,
                'points_debt_added'         => $pointsDebtAdded,
                'remark'                   => $hasPendingReview
                    ? '仅冲正可验证历史权益；未归属聚合权益继续待人工复核'
                    : ($isFullyReversed
                        ? '订单奖励已全额冲正（含整数尾差）'
                        : '订单奖励按累计退款比例冲正'),
            ]);

            return ['applied' => true, 'user_id' => $userId, 'reason' => 'reversed'];
        });

        if ($result['user_id'] > 0) {
            // 重放也重新计算一次，修复账务已提交但等级更新前进程退出的窗口。
            $this->memberLevelService->recalculateLevel($result['user_id']);
        }
        return $result;
    }

    /** @return array{awarded:int,reversed:int,skipped:int,failed:int} */
    public function reconcile(): array
    {
        $stats = ['awarded' => 0, 'reversed' => 0, 'skipped' => 0, 'failed' => 0];
        // 升级 SQL 必须把该值写成实际发布时刻。配置缺失时使用未来时间，
        // 宁可保守导入也不猜测历史订单是否已经由旧监听器发过权益。
        $releaseBoundary = strtotime((string)$this->systemConfigRepository->getConfigValue(
            'member_reward.snapshot_started_at',
            '9999-12-31 23:59:59'
        )) ?: PHP_INT_MAX;
        $afterId = 0;
        do {
            $orders = $this->orderOrderRepository->getMemberRewardCandidatesAfterId(
                $afterId,
                self::RECONCILE_BATCH_SIZE
            );
            foreach ($orders as $order) {
                $afterId = max($afterId, (int)$order['id']);
                try {
                    $result = $this->handleOrderCompleted([
                        'order_id' => (int)$order['id'],
                        'user_id'  => (int)$order['user_id'],
                        'conservative_import' => (
                            strtotime((string)($order['receive_time'] ?? '')) ?: 0
                        ) < $releaseBoundary,
                        'reconstruct_completion_state' => true,
                    ]);
                    $result['applied'] ? $stats['awarded']++ : $stats['skipped']++;
                    foreach ($this->orderRefundRepository->getRefundedByOrderId((int)$order['id']) as $refund) {
                        $refundId = (int)($refund['id'] ?? 0);
                        if ($refundId <= 0) {
                            continue;
                        }
                        // 每个订单重建后立即冲正其历史退款，避免先给全表发奖、
                        // 再从头扫描退款造成可消费积分的长时间窗口。
                        $reversal = $this->handleRefundCompleted(['refund_id' => $refundId]);
                        $reversal['applied'] ? $stats['reversed']++ : $stats['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    Log::error('补偿订单会员奖励失败', [
                        'order_id' => (int)$order['id'],
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        } while (count($orders) === self::RECONCILE_BATCH_SIZE);

        $afterId = 0;
        do {
            $refunds = $this->orderRefundRepository->getRefundedEventsAfterId(
                $afterId,
                self::RECONCILE_BATCH_SIZE
            );
            foreach ($refunds as $refund) {
                $afterId = max($afterId, (int)$refund['refund_id']);
                try {
                    $result = $this->handleRefundCompleted($refund);
                    $result['applied'] ? $stats['reversed']++ : $stats['skipped']++;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    Log::error('补偿订单会员奖励退款冲正失败', [
                        'refund_id' => (int)$refund['refund_id'],
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        } while (count($refunds) === self::RECONCILE_BATCH_SIZE);

        return $stats;
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @return array{0:array<int,int>,1:int}
     */
    private function resolveEligibleItemsAtCompletion(
        array $order,
        array $items,
        bool $reconstructCompletionState
    ): array {
        $completionAt = strtotime((string)($order['receive_time'] ?? '')) ?: 0;
        $refundAtByItem = [];
        foreach ($this->orderRefundRepository->getRefundedByOrderId((int)$order['id']) as $refund) {
            $itemId = (int)($refund['order_item_id'] ?? 0);
            $refundAtByItem[$itemId] = max(
                $refundAtByItem[$itemId] ?? 0,
                strtotime((string)($refund['updated_at'] ?? '')) ?: 0
            );
        }

        $eligibleIds = [];
        $rewardCents = 0;
        foreach ($items as $item) {
            $itemId = (int)$item['id'];
            $refundStatus = (int)($item['refund_status'] ?? 0);
            $wasEligibleAtCompletion = $refundStatus !== 2
                || ($reconstructCompletionState
                    && ($refundAtByItem[$itemId] ?? 0) >= $completionAt
                    && $completionAt > 0);
            if (!$wasEligibleAtCompletion) {
                continue;
            }
            $eligibleIds[] = $itemId;
            $rewardCents += max(0, self::toCents($item['pay_amount'] ?? 0));
        }
        return [$eligibleIds, $rewardCents];
    }

    /**
     * 新订单已有行级分摊；历史订单若缺失/不守恒则按订单优惠、运费确定性重建，
     * 避免把 pay_amount=0 的旧行误判为无奖励。
     *
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function ensureItemAmountAllocations(array $order, array $items): array
    {
        $actualPayCents = 0;
        $actualDiscountCents = 0;
        $actualFreightCents = 0;
        foreach ($items as $item) {
            $actualPayCents += self::toCents($item['pay_amount'] ?? 0);
            $actualDiscountCents += self::toCents($item['discount_amount'] ?? 0);
            $actualFreightCents += self::toCents($item['freight_amount'] ?? 0);
        }
        if ($actualPayCents === self::toCents($order['pay_amount'] ?? 0)
            && $actualDiscountCents === self::toCents($order['discount_amount'] ?? 0)
            && $actualFreightCents === self::toCents($order['freight_amount'] ?? 0)) {
            return $items;
        }

        try {
            $allocations = OrderItemAmountAllocator::allocate(
                array_map(static fn (array $item): mixed => $item['total_amount'] ?? 0, $items),
                $order['discount_amount'] ?? 0,
                $order['freight_amount'] ?? 0
            );
        } catch (\InvalidArgumentException $e) {
            $this->throwBusinessException('历史订单会员奖励金额无法分摊：' . $e->getMessage());
        }

        foreach ($items as $index => &$item) {
            $allocation = $allocations[$index];
            $this->orderItemRepository->updateAmountAllocation(
                (int)$item['id'],
                (float)$allocation['discount_amount'],
                (float)$allocation['freight_amount'],
                (float)$allocation['pay_amount']
            );
            $item['discount_amount'] = $allocation['discount_amount'];
            $item['freight_amount'] = $allocation['freight_amount'];
            $item['pay_amount'] = $allocation['pay_amount'];
        }
        unset($item);
        return $items;
    }

    /**
     * 只把完整满足旧消费奖励账本不变量的流水计为可验证积分。
     *
     * 任一同 source 行冲突时，整组积分 provenance 降级为 unverified：部分采信会让
     * 同一历史事件在数据修复前产生不可解释的选择性冲正。
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{
     *     verified_points:int,
     *     verification_status:string,
     *     evidence:array<string,mixed>
     * }
     */
    private function inspectLegacyPointsEvidence(array $rows, int $userId, string $source): array
    {
        $verifiedPoints = 0;
        $validIds = [];
        $invalidRows = [];

        foreach ($rows as $row) {
            $reasons = [];
            $rowId = (int)($row['id'] ?? 0);
            $points = (int)($row['points'] ?? 0);

            if ((int)($row['user_id'] ?? 0) !== $userId) {
                $reasons[] = 'user_mismatch';
            }
            if ((string)($row['source'] ?? '') !== $source) {
                $reasons[] = 'source_mismatch';
            }
            if ((int)($row['type'] ?? 0) !== PointsLog::TYPE_CONSUME_AWARD) {
                $reasons[] = 'type_mismatch';
            }
            if ($points <= 0) {
                $reasons[] = 'non_positive_points';
            }
            if (!array_key_exists('before_points', $row) || !array_key_exists('after_points', $row)) {
                $reasons[] = 'balance_snapshot_missing';
            } elseif ((int)$row['after_points'] - (int)$row['before_points'] !== $points) {
                $reasons[] = 'before_after_mismatch';
            }

            if ($reasons !== []) {
                $invalidRows[] = [
                    'id' => $rowId,
                    'reasons' => $reasons,
                ];
                continue;
            }

            $validIds[] = $rowId;
            $verifiedPoints += $points;
        }

        $hasConflict = $invalidRows !== [];
        if ($hasConflict) {
            $verifiedPoints = 0;
            $verificationStatus = OrderMemberReward::VERIFICATION_UNVERIFIED;
        } elseif ($validIds !== []) {
            // 只有积分可归属，旧成长值/消费额/订单数仍未知，所以最多是 partial。
            $verificationStatus = OrderMemberReward::VERIFICATION_PARTIAL;
        } else {
            $verificationStatus = OrderMemberReward::VERIFICATION_UNVERIFIED;
        }

        return [
            'verified_points' => $verifiedPoints,
            'verification_status' => $verificationStatus,
            'evidence' => [
                'version' => 1,
                'kind' => 'legacy_points_logs',
                'source' => $source,
                'points_log_ids' => array_map(
                    static fn (array $row): int => (int)($row['id'] ?? 0),
                    $rows
                ),
                'valid_points_log_ids' => $validIds,
                'invalid_points_logs' => $invalidRows,
                'verified_points' => $verifiedPoints,
            ],
        ];
    }

    /** @param array<string, mixed> $reward */
    private function createIgnoredRefundAdjustment(
        array $reward,
        int $refundId,
        string $eventKey,
        string $remark
    ): void {
        $this->adjustmentRepository->create([
            'reward_id'                => (int)$reward['id'],
            'order_id'                 => (int)$reward['order_id'],
            'refund_id'                => $refundId,
            'user_id'                  => (int)$reward['user_id'],
            'action'                   => 'refund_ignored',
            'event_key'                => $eventKey,
            'refund_amount'            => 0,
            'points'                   => 0,
            'points_credited_reversed' => 0,
            'growth'                   => 0,
            'consume_amount'           => 0,
            'order_count'              => 0,
            'points_debt_added'         => 0,
            'remark'                   => $remark,
        ]);
    }

    private static function proportionalTarget(int $total, int $cumulativeCents, int $baseCents): int
    {
        if ($total <= 0 || $cumulativeCents <= 0 || $baseCents <= 0) {
            return 0;
        }
        if ($cumulativeCents >= $baseCents) {
            return $total;
        }
        return min($total, (int)floor($total * ($cumulativeCents / $baseCents) + 1e-9));
    }

    private static function toCents(mixed $amount): int
    {
        return (int)round((float)$amount * 100);
    }

    private static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
