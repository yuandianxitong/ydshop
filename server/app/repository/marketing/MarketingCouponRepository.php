<?php
declare(strict_types=1);

namespace app\repository\marketing;

use app\model\marketing\MarketingCoupon;
use core\base\Repository;
use think\Model as ThinkModel;

class MarketingCouponRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MarketingCoupon();
    }

    /**
     * 当前可发放的优惠券（启用 + 未结束 + 总数未发完）
     */
    public function getIssuableList(int $limit = 50): array
    {
        $now = date('Y-m-d H:i:s');
        return MarketingCoupon::where('status', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->whereOr('end_at', '>=', $now);
            })
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    public function findModel(int $id): ?MarketingCoupon
    {
        return MarketingCoupon::find($id);
    }

    public function incrementUsed(int $id, int $delta = 1): void
    {
        if ($delta <= 0) {
            return;
        }
        MarketingCoupon::where('id', $id)->inc('used_count', $delta)->update();
    }

    /**
     * 按 ID 列表统计存在的优惠券数量（用于校验 coupon_ids 是否全部有效）
     */
    public function countExistingByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        return MarketingCoupon::whereIn('id', $ids)->count();
    }
}
