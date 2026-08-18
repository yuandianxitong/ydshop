<?php
declare(strict_types=1);

namespace plugins\coupon\repository;

use core\base\Repository;
use plugins\coupon\model\MarketingCoupon;
use plugins\coupon\model\MarketingCouponUser;

class MarketingCouponRepository extends Repository
{
    protected function getModel(): MarketingCoupon
    {
        return new MarketingCoupon();
    }

    /**
     * 查询"可领取"优惠券（适用于 uniapp 优惠券中心页）
     *
     * 过滤条件：
     *  - status = 1 (启用)
     *  - 未过期 (start_at <= now <= end_at，null 表示不限)
     *  - 库存够 (used_count < total_count，total_count = null 表示不限)
     *  - 用户领取次数未达上限 (per_user_limit)
     */
    public function getReceivable(int $userId): array
    {
        $now = date('Y-m-d H:i:s');

        $rows = MarketingCoupon::where('status', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->whereOr('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->whereOr('end_at', '>=', $now);
            })
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if (empty($rows)) {
            return [];
        }

        // 计算每张券对该用户的已领次数
        $couponIds   = array_column($rows, 'id');
        $userClaimed = MarketingCouponUser::where('user_id', $userId)
            ->whereIn('coupon_id', $couponIds)
            ->field('coupon_id, COUNT(*) AS cnt')
            ->group('coupon_id')
            ->select()
            ->toArray();
        $claimedMap = array_column($userClaimed, 'cnt', 'coupon_id');

        $result = [];
        foreach ($rows as $r) {
            $usedCount = (int)($r['used_count'] ?? 0);
            $total     = (int)($r['total_count'] ?? 0);
            // per_user_limit > 0 表示每用户限领次数；schema 默认 1
            $perLimit  = (int)($r['per_user_limit'] ?? 1);
            $claimed   = (int)($claimedMap[$r['id']] ?? 0);

            // 库存检查（total_count = 0 表示不限；schema 是 NULL 但 Model integer cast 会归 0）
            if ($total > 0 && $usedCount >= $total) {
                continue;
            }

            // 用户领取次数限制
            $r['has_claimed']   = $perLimit > 0 && $claimed >= $perLimit;
            $r['claimed_count'] = $claimed;
            $result[] = $r;
        }

        return $result;
    }

    /**
     * 后台优惠券分页列表（支持 keyword/type/status 过滤）
     */
    public function getAdminPageList(array $params, int $page = 1, int $limit = 20): array
    {
        $keyword = $params['keyword'] ?? $params['name'] ?? '';
        $type    = $params['type'] ?? '';
        $status  = isset($params['status']) && $params['status'] !== '' ? (int)$params['status'] : null;

        $query = $this->model->field('*');

        if ($keyword !== '') {
            $query->where('name', 'like', "%{$keyword}%");
        }
        if ($type !== '') {
            $query->where('type', $type);
        }
        if ($status !== null) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $list  = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 领取优惠券时，原子地把 used_count + 1
     */
    public function incUsedCount(int $couponId): bool
    {
        return $this->model->where('id', $couponId)->inc('used_count')->update() > 0;
    }

    /**
     * 订单取消时，安全地把 used_count - 1（仅当 > 0 时才减）
     */
    public function decUsedCount(int $couponId): bool
    {
        return $this->model->where('id', $couponId)
            ->where('used_count', '>', 0)
            ->dec('used_count')
            ->update() > 0;
    }
}
