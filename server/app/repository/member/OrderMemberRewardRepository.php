<?php

declare(strict_types=1);

namespace app\repository\member;

use app\model\member\OrderMemberReward;
use core\base\Repository;
use think\Model;

class OrderMemberRewardRepository extends Repository
{
    protected function getModel(): Model
    {
        return new OrderMemberReward();
    }

    public function findByOrderId(int $orderId): ?array
    {
        $row = $this->model->where('order_id', $orderId)->find();
        return $row ? $row->toArray() : null;
    }

    public function findByOrderIdForUpdate(int $orderId): ?array
    {
        $row = $this->model->where('order_id', $orderId)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $row = $this->model->where('id', $id)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    /** 管理后台历史权益证据复核列表。 */
    public function getReviewPage(array $filters, int $page, int $limit): array
    {
        $query = $this->model
            ->alias('reward')
            ->leftJoin('order_orders business_order', 'business_order.id = reward.order_id')
            ->leftJoin('users member', 'member.id = reward.user_id');

        $reviewStatus = trim((string)($filters['review_status'] ?? OrderMemberReward::REVIEW_PENDING));
        if (in_array($reviewStatus, [
            OrderMemberReward::REVIEW_NONE,
            OrderMemberReward::REVIEW_PENDING,
            OrderMemberReward::REVIEW_RESOLVED,
        ], true)) {
            $query->where('reward.review_status', $reviewStatus);
        }
        $verificationStatus = trim((string)($filters['verification_status'] ?? ''));
        if (in_array($verificationStatus, [
            OrderMemberReward::VERIFICATION_VERIFIED,
            OrderMemberReward::VERIFICATION_PARTIAL,
            OrderMemberReward::VERIFICATION_UNVERIFIED,
        ], true)) {
            $query->where('reward.verification_status', $verificationStatus);
        }
        $keyword = trim((string)($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($search) use ($keyword): void {
                $search->whereLike('business_order.order_no', '%' . $keyword . '%')
                    ->whereOr('member.nickname', 'like', '%' . $keyword . '%')
                    ->whereOr('member.mobile', 'like', '%' . $keyword . '%');
            });
        }

        $total = (clone $query)->count();
        $list = $query
            ->field([
                'reward.*',
                'business_order.order_no',
                'business_order.status AS order_status',
                'member.nickname AS user_nickname',
                'member.mobile AS user_mobile',
            ])
            ->order('reward.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /** @return array{pending:int,partial:int,unverified:int,resolved:int} */
    public function getReviewSummary(): array
    {
        $row = $this->model
            ->field([
                "SUM(CASE WHEN review_status = 'pending' THEN 1 ELSE 0 END) AS pending",
                "SUM(CASE WHEN review_status = 'pending' AND verification_status = 'partial' THEN 1 ELSE 0 END) AS partial",
                "SUM(CASE WHEN review_status = 'pending' AND verification_status = 'unverified' THEN 1 ELSE 0 END) AS unverified",
                "SUM(CASE WHEN review_status = 'resolved' THEN 1 ELSE 0 END) AS resolved",
            ])
            ->find();
        $data = $row ? $row->toArray() : [];
        return [
            'pending' => (int)($data['pending'] ?? 0),
            'partial' => (int)($data['partial'] ?? 0),
            'unverified' => (int)($data['unverified'] ?? 0),
            'resolved' => (int)($data['resolved'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $data */
    public function resolveReviewIfPending(int $rewardId, array $data): int
    {
        return $this->model
            ->where('id', $rewardId)
            ->where('review_status', OrderMemberReward::REVIEW_PENDING)
            ->update($data);
    }

    /** @param array<string, mixed> $data */
    public function updateReversalProgress(int $rewardId, array $data): bool
    {
        return $this->model->where('id', $rewardId)->update($data) > 0;
    }
}
