<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderItem;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderItemRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderItem();
    }

    /**
     * 加载某订单下的所有商品明细（按 id 升序）
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByOrderId(int $orderId): array
    {
        return $this->model->where('order_id', $orderId)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /** 支付事务内判断订单是否全部为虚拟商品，供持久化履约补偿标记。 */
    public function isFullyVirtualOrder(int $orderId): bool
    {
        $row = $this->model->alias('item')
            ->leftJoin('goods_spu spu', 'spu.id = item.spu_id')
            ->where('item.order_id', $orderId)
            ->field([
                'COUNT(*) AS item_count',
                "SUM(CASE WHEN spu.type = 'virtual' THEN 1 ELSE 0 END) AS virtual_count",
            ])
            ->find();
        $itemCount = (int)($row['item_count'] ?? 0);
        return $itemCount > 0 && (int)($row['virtual_count'] ?? 0) === $itemCount;
    }

    /**
     * 加行锁读取订单项，防止同一商品行并发创建多笔售后单。
     */
    public function findForUpdate(int $itemId): ?array
    {
        $row = $this->model->where('id', $itemId)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 按固定顺序锁定订单全部商品行，用于历史订单金额惰性分摊。
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByOrderIdForUpdate(int $orderId): array
    {
        return $this->model->where('order_id', $orderId)
            ->order('id', 'asc')
            ->lock(true)
            ->select()
            ->toArray();
    }

    /**
     * 回填单行的优惠、运费与净实付分摊。
     */
    public function updateAmountAllocation(
        int $itemId,
        float $discountAmount,
        float $freightAmount,
        float $payAmount
    ): bool {
        return $this->model->where('id', $itemId)->update([
            'discount_amount' => $discountAmount,
            'freight_amount'  => $freightAmount,
            'pay_amount'      => $payAmount,
        ]) > 0;
    }

    /**
     * 拆单迁移：把商品行改挂到新订单。调用方必须已持有该行的行锁。
     */
    public function reassignToOrder(int $itemId, int $newOrderId): bool
    {
        return $this->model->where('id', $itemId)
            ->update(['order_id' => $newOrderId]) > 0;
    }

    /**
     * 改价回写单行价格与金额分摊字段。调用方必须已持有该行的行锁。
     *
     * @param array<string, mixed> $data 价格/金额字段（price、total_amount、
     *                                   discount_amount、freight_amount、pay_amount 等）
     */
    public function updatePriceAndAllocation(int $itemId, array $data): bool
    {
        return $this->model->where('id', $itemId)->update($data) > 0;
    }

    /** 订单是否包含虚拟商品行（拆合单禁用判定，虚拟判定与 isFullyVirtualOrder 一致）。 */
    public function hasVirtualItem(int $orderId): bool
    {
        return $this->model->alias('item')
            ->leftJoin('goods_spu spu', 'spu.id = item.spu_id')
            ->where('item.order_id', $orderId)
            ->where('spu.type', 'virtual')
            ->count() > 0;
    }

    /**
     * 标记某 OrderItem 为已评价（评价提交后调用）
     */
    public function markReviewed(int $itemId): bool
    {
        return $this->model->where('id', $itemId)
            ->update(['is_reviewed' => 1]) > 0;
    }

    /**
     * 设置 refund_status（0=无，1=申请中，2=已退款）
     */
    public function setRefundStatus(int $itemId, int $status): bool
    {
        return $this->model->where('id', $itemId)
            ->update(['refund_status' => $status]) > 0;
    }

    /**
     * 销量 Top N 商品（按 spu_id 聚合 quantity 求和）
     *
     * @return array<int, array{spu_id:int, goods_name:string, total_qty:int}>
     */
    public function getTopProductsBetween(array $paidStatuses, string $startDate, string $endDate, int $limit = 10): array
    {
        return $this->model->alias('oi')
            ->join('order_orders o', 'o.id = oi.order_id')
            ->whereIn('o.status', $paidStatuses)
            ->where('o.created_at', '>=', $startDate)
            ->where('o.created_at', '<=', $endDate)
            ->field('oi.spu_id, oi.goods_name, SUM(oi.quantity) as total_qty')
            ->group('oi.spu_id')
            ->order('total_qty', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 按分类聚合销售额（关联 goods_spu 取 category_id）
     *
     * @return array<int, array{category_id:int, total_amount:float}>
     */
    public function getCategoryBreakdownBetween(array $paidStatuses, string $startDate, string $endDate): array
    {
        return $this->model->alias('oi')
            ->join('order_orders o', 'o.id = oi.order_id')
            ->join('goods_spu gs', 'gs.id = oi.spu_id')
            ->whereIn('o.status', $paidStatuses)
            ->where('o.created_at', '>=', $startDate)
            ->where('o.created_at', '<=', $endDate)
            ->field('gs.category_id, SUM(oi.total_amount) as total_amount')
            ->group('gs.category_id')
            ->order('total_amount', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 各 SKU 的累计销量（用于补货推荐计算日均销量）
     *
     * @return array<int, array{sku_id:int, total_qty:int}>
     */
    public function getSkuQtySumSince(array $paidStatuses, string $startDate): array
    {
        return $this->model->alias('oi')
            ->join('order_orders o', 'o.id = oi.order_id')
            ->whereIn('o.status', $paidStatuses)
            ->where('o.created_at', '>=', $startDate)
            ->field('oi.sku_id, SUM(oi.quantity) as total_qty')
            ->group('oi.sku_id')
            ->select()
            ->toArray();
    }
}
