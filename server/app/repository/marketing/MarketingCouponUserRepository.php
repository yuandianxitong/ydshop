<?php
declare(strict_types=1);

namespace app\repository\marketing;

use app\model\marketing\MarketingCouponUser;
use core\base\Repository;
use think\Model as ThinkModel;

class MarketingCouponUserRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MarketingCouponUser();
    }

    public function countByCouponAndUser(int $couponId, int $userId): int
    {
        return MarketingCouponUser::where('coupon_id', $couponId)
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * 列出用户领取的券（关联券基本信息）
     */
    public function getUserCoupons(int $userId, ?string $status, int $page, int $limit): array
    {
        $query = MarketingCouponUser::where('user_id', $userId)
            ->with(['coupon' => fn ($q) => $q->field('id, name, type, value, min_amount, start_at, end_at')])
            ->order('id', 'desc');

        if ($status && in_array($status, ['unused', 'used', 'expired'], true)) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        // 拍平 coupon 字段
        foreach ($list as &$row) {
            $coupon = $row['coupon'] ?? [];
            $row['name']       = $coupon['name']       ?? '';
            $row['type']       = $coupon['type']       ?? '';
            $row['value']      = $coupon['value']      ?? 0;
            $row['min_amount'] = $coupon['min_amount'] ?? 0;
            $row['start_at']   = $coupon['start_at']   ?? null;
            $row['end_at']     = $coupon['end_at']     ?? null;
            unset($row['coupon']);
        }
        unset($row);

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max($limit, 1)),
            ],
        ];
    }

    public function countByUserId(int $userId): int
    {
        return MarketingCouponUser::where('user_id', $userId)
            ->where('status', 'unused')
            ->count();
    }

    /**
     * 用户所有"未使用"的优惠券，带 coupon 关联（结算页可用券筛选用）
     *
     * 返回数组保留 coupon 嵌套（不拍平），因为 Service 还要读 scope_ids、
     * end_at、start_at、max_discount 等完整字段做二次过滤。
     */
    public function findUnusedByUserWithCoupon(int $userId): array
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'unused')
            ->with(['coupon'])
            ->select()
            ->toArray();
    }

    /**
     * 用户全部优惠券（不分页，带 coupon），按 id 倒序
     */
    public function findAllByUserWithCoupon(int $userId): array
    {
        return $this->model->where('user_id', $userId)
            ->with(['coupon'])
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 查找某订单已使用的优惠券记录（订单取消时退还）
     */
    public function findUsedByOrderId(int $orderId): ?array
    {
        $row = $this->model->where('used_order_id', $orderId)
            ->where('status', 'used')
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 查找某订单占用的全部已用优惠券（合单后存活单可能绑定多张券）
     */
    public function findAllUsedByOrderId(int $orderId): array
    {
        return $this->model->where('used_order_id', $orderId)
            ->where('status', 'used')
            ->select()
            ->toArray();
    }

    /**
     * 加载优惠券领取记录 + 关联券信息（calcDiscount 计算折扣金额用）
     */
    public function findByIdWithCoupon(int $id): ?array
    {
        $row = $this->model->with(['coupon'])->find($id);
        return $row ? $row->toArray() : null;
    }

    /**
     * 标记为已使用（下单时锁定）
     */
    public function markUsed(int $couponUserId, int $orderId): bool
    {
        return $this->model->where('id', $couponUserId)->update([
            'status'        => 'used',
            'used_order_id' => $orderId,
            'used_at'       => date('Y-m-d H:i:s'),
        ]) > 0;
    }

    /**
     * 合单时把已用券的订单绑定从被合并单改到存活单
     *
     * @return int 受影响行数
     */
    public function rebindUsedOrder(int $fromOrderId, int $toOrderId): int
    {
        return $this->model->where('used_order_id', $fromOrderId)
            ->where('status', 'used')
            ->update(['used_order_id' => $toOrderId]);
    }

    /**
     * 标记为未使用（订单取消时退还）
     */
    public function markUnused(int $couponUserId): bool
    {
        return $this->model->where('id', $couponUserId)->update([
            'status'        => 'unused',
            'used_order_id' => 0,
            'used_at'       => null,
        ]) > 0;
    }
}
