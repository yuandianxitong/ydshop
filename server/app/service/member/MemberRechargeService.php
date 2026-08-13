<?php
declare(strict_types=1);

namespace app\service\member;

use app\model\member\MemberRechargeOrder;
use app\model\user\BalanceLog;
use app\model\user\PointsLog;
use app\repository\member\MemberRechargeOrderRepository;
use app\repository\member\MemberGrowthLogRepository;
use app\repository\member\RechargePackageRepository;
use app\repository\user\BalanceLogRepository;
use app\repository\user\PointsLogRepository;
use app\repository\user\UserRepository;
use app\service\payment\PaymentService;
use app\service\user\UserManageService;
use core\base\Service;
use think\facade\Log;

class MemberRechargeService extends Service
{
    protected PaymentService $paymentService;
    protected UserManageService $userManageService;
    protected MemberLevelService $memberLevelService;
    protected RechargePackageRepository $rechargePackageRepository;
    protected MemberRechargeOrderRepository $memberRechargeOrderRepository;
    protected BalanceLogRepository $balanceLogRepository;
    protected PointsLogRepository $pointsLogRepository;
    protected MemberGrowthLogRepository $memberGrowthLogRepository;
    protected UserRepository $userRepository;

    /** 管理后台历史充值成长值复核列表。 */
    public function getGrowthReviewList(array $filters): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(100, max(1, (int)($filters['limit'] ?? 20)));
        $result = $this->memberRechargeOrderRepository->getGrowthReviewPage(
            $filters,
            $page,
            $limit
        );
        $result['summary'] = $this->memberRechargeOrderRepository->getGrowthReviewSummary();
        return $result;
    }

    /**
     * 关闭历史充值成长值歧义。confirmed_applied 只留痕；confirmed_missing 使用
     * 独立唯一 source 精确补发一次，并与复核状态在同一事务内完成。
     */
    public function resolveGrowthReview(
        int $rechargeId,
        int $operatorId,
        string $resolution,
        string $reason
    ): array {
        $reason = trim($reason);
        if ($rechargeId <= 0 || $operatorId <= 0) {
            $this->throwBusinessException('充值复核记录或操作人无效');
        }
        if (!in_array($resolution, [
            MemberRechargeOrder::GROWTH_RESOLUTION_CONFIRMED_APPLIED,
            MemberRechargeOrder::GROWTH_RESOLUTION_CONFIRMED_MISSING,
        ], true)) {
            $this->throwBusinessException('充值成长值复核结论无效');
        }
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 255) {
            $this->throwBusinessException('复核依据需为 5~255 个字符');
        }

        return $this->runInTransaction(function () use (
            $rechargeId,
            $operatorId,
            $resolution,
            $reason
        ): array {
            $recharge = $this->memberRechargeOrderRepository->findByIdForUpdate($rechargeId);
            if (!$recharge) {
                $this->throwBusinessException('充值成长值复核记录不存在');
            }
            $status = (string)($recharge['growth_review_status']
                ?? MemberRechargeOrder::GROWTH_REVIEW_NONE);
            if ($status === MemberRechargeOrder::GROWTH_REVIEW_RESOLVED) {
                return [
                    'applied' => false,
                    'growth_added' => false,
                    'reason' => 'already_resolved',
                    'resolution' => (string)($recharge['growth_review_resolution'] ?? ''),
                ];
            }
            if ($status !== MemberRechargeOrder::GROWTH_REVIEW_PENDING
                || empty($recharge['settled_at'])
                || (int)($recharge['status'] ?? 0) !== 1) {
                $this->throwBusinessException('仅已结算且待复核的历史充值可以结案');
            }

            $userId = (int)($recharge['user_id'] ?? 0);
            $growthValue = (int)($recharge['expected_growth_value'] ?? 0);
            if ($userId <= 0 || $growthValue <= 0) {
                $this->throwBusinessException('充值复核记录缺少用户或理论成长值');
            }

            $growthAdded = false;
            if ($resolution === MemberRechargeOrder::GROWTH_RESOLUTION_CONFIRMED_MISSING) {
                $source = 'member.recharge.growth.review:' . $rechargeId;
                if (!$this->memberLevelService->addGrowthValue($userId, $growthValue, $source)) {
                    $this->throwBusinessException('成长值补发事件已存在但复核尚未结案，请人工核对');
                }
                $this->memberLevelService->checkAndUpgrade($userId);
                $growthAdded = true;
            }

            $reviewedAt = date('Y-m-d H:i:s');
            if ($this->memberRechargeOrderRepository->resolveGrowthReviewIfPending(
                $rechargeId,
                [
                    'growth_review_status' => MemberRechargeOrder::GROWTH_REVIEW_RESOLVED,
                    'growth_review_resolution' => $resolution,
                    'growth_review_reason' => $reason,
                    'growth_review_operator_id' => $operatorId,
                    'growth_reviewed_at' => $reviewedAt,
                ]
            ) !== 1) {
                $this->throwBusinessException('充值成长值复核状态已变化，请刷新后重试');
            }

            return [
                'applied' => true,
                'growth_added' => $growthAdded,
                'growth_value' => $growthValue,
                'resolution' => $resolution,
            ];
        });
    }

    /**
     * PC/UniApp 兼容入口：服务端决定支付形态与微信身份，不接受任何赠送参数。
     */
    public function createRechargeForClient(
        int $userId,
        string|int|float $amount,
        string $channel,
        string $clientType,
        string $clientIp = '',
        int $packageId = 0
    ): array {
        if (!in_array($channel, ['wechat', 'alipay'], true)) {
            $this->throwBusinessException('不支持的充值支付渠道');
        }
        if ($packageId < 0) {
            $this->throwBusinessException('充值套餐参数非法');
        }

        // 套餐金额由服务端套餐表读取；只有自定义充值才解析客户端金额。
        $amountCents = 0;
        if ($packageId === 0) {
            $amountCents = $this->moneyToCents($amount);
            if ($amountCents < 100 || $amountCents > 1000000) {
                $this->throwBusinessException('充值金额范围为 1.00 ~ 10000.00 元');
            }
        }

        $extra = ['client_type' => $clientType];
        if ($channel === 'wechat') {
            $resolved = $this->paymentService->resolveWechatPayParams($clientType, $userId);
            $tradeType = (string)$resolved['trade_type'];
            $extra['appid'] = (string)$resolved['appid'];
            if (!empty($resolved['openid'])) {
                $extra['openid'] = (string)$resolved['openid'];
            }
            if ($tradeType === 'h5') {
                $extra['client_ip'] = $clientIp;
            }
        } else {
            $tradeType = match ($clientType) {
                'pc' => 'page',
                'app' => 'app',
                'miniapp', 'wechat_h5', 'h5' => 'wap',
                default => $this->throwBusinessException('不支持的客户端类型'),
            };
        }

        return $this->createRechargeOrder(
            $userId,
            $amountCents / 100,
            $channel,
            $tradeType,
            $extra,
            $packageId,
            0
        );
    }

    /**
     * 创建充值订单
     *
     * @param int    $userId     用户ID
     * @param float  $amount     充值金额（package_id>0 时可为0，从套餐读取）
     * @param string $channel    支付渠道 alipay / wechat
     * @param string $tradeType  支付类型 jsapi / native / h5 / app
     * @param array  $extra      额外参数（如 openid、appid 等）
     * @param int    $packageId  套餐ID（0 表示自定义金额）
     * @param float  $giftAmount 赠送金额（packageId=0 时手动传入，通常为0）
     * @return array             支付结果
     */
    public function createRechargeOrder(
        int $userId,
        float $amount,
        string $channel,
        string $tradeType,
        array $extra = [],
        int $packageId = 0,
        float $giftAmount = 0
    ): array {
        $giftPoints = 0;

        // 自定义充值不能由客户端指定赠送金额；赠送只能来自后台启用的套餐。
        if ($packageId === 0) {
            $giftAmount = 0;
        }

        if ($packageId > 0) {
            $package = $this->rechargePackageRepository->find($packageId);
            if (!$package || (int)($package['status'] ?? 0) !== 1) {
                $this->throwBusinessException('套餐不存在或已下架');
            }
            $amount      = (float) $package['amount'];
            $giftAmount  = (float) $package['gift_amount'];
            $giftPoints  = (int)   $package['gift_points'];
        }

        if ($amount <= 0) {
            $this->throwBusinessException('充值金额必须大于0');
        }

        // 14 位时间 + 48 bit CSPRNG，在 32 字符字段内保留足够并发熵；数据库
        // member_recharge_orders.order_no 的唯一键作为最终碰撞防线。
        $orderNo = 'RCH' . date('YmdHis') . bin2hex(random_bytes(6));

        // 创建本地充值订单记录
        $rechargeOrder = $this->memberRechargeOrderRepository->create([
            'user_id'     => $userId,
            'package_id'  => $packageId > 0 ? $packageId : null,
            'order_no'    => $orderNo,
            'amount'      => $amount,
            'gift_amount' => $giftAmount,
            'gift_points' => $giftPoints,
            'status'      => 0,
        ]);

        // 调用支付服务创建支付单
        $payParams = array_merge($extra, [
            'out_trade_no' => 'RCH_' . $orderNo,
            'subject'      => '余额充值-' . $orderNo,
            'total_amount' => $amount,
            'trade_type'   => $tradeType,
            'user_id'      => $userId,
            'biz_type'     => 'recharge',
        ]);

        // 即使 driver 抛出“响应丢失/超时”，provider 也可能已经受理支付，且
        // PaymentService 已创建本地 payment_order。充值业务单必须保留 pending，
        // 后续真实回调可凭 order_no 找到它并通过 bindPaymentOrderIdIfEmpty 补绑。
        $result = $this->paymentService->createOrder($channel, $payParams);

        $paymentOrderId = (int)($result['payment_id'] ?? 0);
        if ($paymentOrderId <= 0 || $this->memberRechargeOrderRepository->setPaymentOrderId(
            (int)$rechargeOrder['id'],
            $paymentOrderId
        ) !== true) {
            $this->throwBusinessException('充值单关联支付单失败');
        }

        return $result;
    }

    /**
     * 处理充值成功（支付回调调用）
     *
     * @param string $orderNo  支付订单号（可带 RCH_ 前缀）
     * @param string|int|float $amount 实际支付金额
     * @param string $channel  支付渠道
     */
    public function handleRechargeSuccess(
        string $orderNo,
        string|int|float $amount,
        string $channel,
        int $paymentOrderId,
        string $eventKey
    ): void {
        // 去掉 RCH_ 前缀，还原业务订单号
        $bizOrderNo = str_starts_with($orderNo, 'RCH_')
            ? substr($orderNo, 4)
            : $orderNo;

        if ($paymentOrderId <= 0 || trim($eventKey) === '') {
            $this->throwBusinessException('充值支付事件缺少可信标识');
        }
        $paidCents = $this->moneyToCents($amount);

        $summary = $this->runInTransaction(function () use (
            $bizOrderNo,
            $channel,
            $paymentOrderId,
            $eventKey,
            $paidCents
        ): array {
            $rechargeOrder = $this->memberRechargeOrderRepository->findByOrderNoForUpdate($bizOrderNo);
            if (!$rechargeOrder) {
                $this->throwBusinessException('充值订单不存在');
            }

            $localCents = $this->moneyToCents($rechargeOrder['amount'] ?? 0);
            if ($localCents !== $paidCents) {
                $this->throwBusinessException('支付金额与充值订单不匹配');
            }

            $boundPaymentId = (int)($rechargeOrder['payment_order_id'] ?? 0);
            if ($boundPaymentId === 0) {
                if ($this->memberRechargeOrderRepository->bindPaymentOrderIdIfEmpty(
                    (int)$rechargeOrder['id'],
                    $paymentOrderId
                ) !== 1) {
                    $this->throwBusinessException('充值单关联支付单失败');
                }
            } elseif ($boundPaymentId !== $paymentOrderId) {
                $this->throwBusinessException('充值单关联的支付单不匹配');
            }

            if (!empty($rechargeOrder['settled_at'])) {
                return [
                    'replayed' => true,
                    'user_id' => (int)$rechargeOrder['user_id'],
                    'gift_amount' => (float)$rechargeOrder['gift_amount'],
                    'gift_points' => (int)$rechargeOrder['gift_points'],
                    'growth_audit_required' => (string)($rechargeOrder['growth_review_status'] ?? '')
                        === MemberRechargeOrder::GROWTH_REVIEW_PENDING,
                ];
            }

            if (!in_array((int)($rechargeOrder['status'] ?? -1), [0, 1], true)) {
                $this->throwBusinessException('当前充值订单状态不可结算');
            }

            $userId = (int)$rechargeOrder['user_id'];
            $giftCents = $this->moneyToCents($rechargeOrder['gift_amount'] ?? 0);
            $giftPoints = (int)$rechargeOrder['gift_points'];
            $legacySettlement = (int)($rechargeOrder['status'] ?? 0) === 1;
            $legacyState = [
                'cash_exists' => false,
                'gift_exists' => false,
                'points_exists' => false,
                'growth_exists' => false,
                'growth_audit_required' => false,
            ];

            if ($legacySettlement) {
                // 固定锁序：充值单 -> 用户 -> 各资产流水。旧 status=1 可能来自
                // “先改状态、再逐项发资产”的版本，必须逐笔核对后才能接管。
                if (!$this->userRepository->findForUpdate($userId)) {
                    $this->throwBusinessException('用户不存在');
                }
                $legacyState = $this->inspectAndClaimLegacySettlement(
                    $userId,
                    $bizOrderNo,
                    $localCents,
                    $giftCents,
                    $giftPoints,
                    $eventKey
                );
            }

            // 固定锁序：member_recharge_orders → users → ledger logs。各资产带独立
            // event_key，历史半成品重放时只补缺失步骤。
            if (!$legacyState['cash_exists']) {
                $this->userManageService->adjustBalance(
                    $userId,
                    $localCents / 100,
                    '在线充值',
                    BalanceLog::TYPE_RECHARGE,
                    'recharge:' . $bizOrderNo,
                    null,
                    $eventKey . ':cash'
                );
            }

            if ($giftCents > 0 && !$legacyState['gift_exists']) {
                $this->userManageService->adjustBalance(
                    $userId,
                    $giftCents / 100,
                    '充值赠送',
                    BalanceLog::TYPE_RECHARGE,
                    'recharge_gift:' . $bizOrderNo,
                    null,
                    $eventKey . ':gift'
                );
            }

            if ($giftPoints > 0 && !$legacyState['points_exists']) {
                $this->userManageService->adjustPoints(
                    $userId,
                    $giftPoints,
                    '充值赠送积分',
                    PointsLog::TYPE_RECHARGE_GIFT,
                    'recharge_points:' . $bizOrderNo,
                    null,
                    $eventKey . ':points'
                );
            }

            $growthValue = intdiv($localCents + $giftCents, 10); // 每元 10 成长值
            if ($growthValue > 0 && !$legacyState['growth_exists']) {
                $allLegacyPrerequisitesExisted = $legacyState['cash_exists']
                    && ($giftCents === 0 || $legacyState['gift_exists'])
                    && ($giftPoints === 0 || $legacyState['points_exists']);
                if ($legacySettlement && $allLegacyPrerequisitesExisted) {
                    // HEAD 旧版没有成长值流水：现金已入账时无法证明成长值是否也
                    // 执行过。只有全部配置要求的前置流水都已存在才属于歧义，
                    // 保守跳过并留下审计告警，绝不能猜测后重复增加。
                    $legacyState['growth_audit_required'] = true;
                } else {
                    // 旧顺序是 cash→gift→points→growth；任一必需前置流水缺失
                    // 都可证明成长值尚未执行，补齐前置资产后必须补发成长值。
                    $this->memberLevelService->addGrowthValue(
                        $userId,
                        $growthValue,
                        $eventKey . ':growth'
                    );
                }
            }
            if ($growthValue > 0) {
                $this->memberLevelService->checkAndUpgrade($userId);
            }

            // 最后一步才把充值单标记为完整结算；此前任一步失败会整体回滚。
            if ($this->memberRechargeOrderRepository->markSettledIfUnsettled(
                (int)$rechargeOrder['id'],
                $channel,
                [
                    'expected_growth_value' => $growthValue,
                    'growth_review_status' => $legacyState['growth_audit_required']
                        ? MemberRechargeOrder::GROWTH_REVIEW_PENDING
                        : MemberRechargeOrder::GROWTH_REVIEW_NONE,
                    'growth_review_reason' => $legacyState['growth_audit_required']
                        ? '历史充值的余额及赠送流水均已存在，但缺少订单级成长值流水，无法确认是否已发放'
                        : '',
                ]
            ) !== 1) {
                $this->throwBusinessException('充值订单结算状态已变化，请重试');
            }

            return [
                'replayed' => false,
                'user_id' => $userId,
                'gift_amount' => $giftCents / 100,
                'gift_points' => $giftPoints,
                'growth_audit_required' => $legacyState['growth_audit_required'],
            ];
        });

        $this->reportSettlement([
            'user_id'     => $summary['user_id'],
            'order_no'    => $bizOrderNo,
            'amount'      => $this->formatCents($paidCents),
            'gift_amount' => $summary['gift_amount'],
            'gift_points' => $summary['gift_points'],
            'replayed'    => $summary['replayed'],
            'growth_audit_required' => (bool)($summary['growth_audit_required'] ?? false),
        ]);
    }

    /**
     * @return array{cash_exists:bool,gift_exists:bool,points_exists:bool,growth_exists:bool,growth_audit_required:bool}
     */
    private function inspectAndClaimLegacySettlement(
        int $userId,
        string $bizOrderNo,
        int $cashCents,
        int $giftCents,
        int $giftPoints,
        string $eventKey
    ): array {
        $cash = $this->inspectLegacyMoneyRows(
            $this->balanceLogRepository->findBySourcesForUpdate([
                'recharge:' . $bizOrderNo,
                'payment:' . $bizOrderNo,
            ]),
            $userId,
            $cashCents,
            '充值现金'
        );
        if ($cash !== null) {
            $this->claimLegacyLedgerEventKey(
                $this->balanceLogRepository,
                $cash,
                $userId,
                $eventKey . ':cash'
            );
        }

        $gift = null;
        if ($giftCents > 0) {
            $gift = $this->inspectLegacyMoneyRows(
                $this->balanceLogRepository->findBySourcesForUpdate([
                    'recharge_gift:' . $bizOrderNo,
                ]),
                $userId,
                $giftCents,
                '充值赠送余额'
            );
            if ($gift !== null) {
                $this->claimLegacyLedgerEventKey(
                    $this->balanceLogRepository,
                    $gift,
                    $userId,
                    $eventKey . ':gift'
                );
            }
        }

        $points = null;
        if ($giftPoints > 0) {
            $rows = $this->pointsLogRepository->findBySourcesForUpdate([
                'recharge_points:' . $bizOrderNo,
            ]);
            if (count($rows) > 1) {
                $this->throwBusinessException('充值赠送积分存在多条历史流水，需人工核对');
            }
            if ($rows !== []) {
                $points = $rows[0];
                if ((int)($points['user_id'] ?? 0) !== $userId
                    || (int)($points['points'] ?? 0) !== $giftPoints
                    || (int)($points['type'] ?? 0) !== PointsLog::TYPE_RECHARGE_GIFT) {
                    $this->throwBusinessException('充值赠送积分历史流水与业务事实冲突');
                }
                $this->claimLegacyLedgerEventKey(
                    $this->pointsLogRepository,
                    $points,
                    $userId,
                    $eventKey . ':points'
                );
            }
        }

        $growthValue = intdiv($cashCents + $giftCents, 10);
        $growthRows = $growthValue > 0
            ? $this->memberGrowthLogRepository->findBySourcesForUpdate($userId, [
                $eventKey . ':growth',
                'recharge:' . $bizOrderNo,
            ])
            : [];
        if (count($growthRows) > 1) {
            $this->throwBusinessException('充值成长值存在多条流水，需人工核对');
        }
        if ($growthRows !== [] && (int)($growthRows[0]['value'] ?? 0) !== $growthValue) {
            $this->throwBusinessException('充值成长值流水与业务事实冲突');
        }

        return [
            'cash_exists' => $cash !== null,
            'gift_exists' => $gift !== null,
            'points_exists' => $points !== null,
            'growth_exists' => $growthRows !== [],
            'growth_audit_required' => false,
        ];
    }

    /** @param array<int,array<string,mixed>> $rows @return array<string,mixed>|null */
    private function inspectLegacyMoneyRows(
        array $rows,
        int $userId,
        int $expectedCents,
        string $label
    ): ?array {
        if (count($rows) > 1) {
            $this->throwBusinessException($label . '存在多条历史流水，需人工核对');
        }
        if ($rows === []) {
            return null;
        }
        $row = $rows[0];
        $actualCents = (int)round((float)($row['amount'] ?? 0) * 100);
        if ((int)($row['user_id'] ?? 0) !== $userId
            || $actualCents !== $expectedCents
            || (int)($row['type'] ?? 0) !== BalanceLog::TYPE_RECHARGE) {
            $this->throwBusinessException($label . '历史流水与业务事实冲突');
        }
        return $row;
    }

    private function claimLegacyLedgerEventKey(
        BalanceLogRepository|PointsLogRepository $repository,
        array $row,
        int $userId,
        string $eventKey
    ): void {
        $existingEventKey = trim((string)($row['event_key'] ?? ''));
        if ($existingEventKey !== '') {
            if (!hash_equals($existingEventKey, $eventKey)) {
                $this->throwBusinessException('充值历史流水已绑定其他事件，需人工核对');
            }
            return;
        }
        if ($repository->claimEventKeyIfEmpty((int)$row['id'], $userId, $eventKey) !== 1) {
            $this->throwBusinessException('充值历史流水接管失败，请重试');
        }
    }

    /** 测试可替换的充值结算日志边界。 */
    protected function reportSettlement(array $context): void
    {
        if (!empty($context['growth_audit_required'])) {
            Log::warning('历史充值成长值无法确认，已保守跳过重复发放并标记人工审计', $context);
            return;
        }
        Log::info('充值处理完成', $context);
    }

    protected function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            $this->throwBusinessException('充值金额格式非法');
        }
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        return ((int)$integer * 100) + (int)str_pad($decimal, 2, '0');
    }

    protected function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
