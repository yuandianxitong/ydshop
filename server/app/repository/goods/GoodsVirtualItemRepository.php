<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsVirtualItem;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsVirtualItemRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsVirtualItem();
    }

    /**
     * 领取一张未使用的虚拟商品卡密：找一张 unused + 未分配的，标记为 sold 并绑定到 order_item
     *
     * 返回被领取卡密的 id；若库存不足返回 null（调用方按需告警 / 创建工单）。
     *
     * 调用方必须在 Service 事务内使用；SELECT FOR UPDATE 会让不同订单依次领取
     * 不同卡密。兼容旧表默认 order_item_id=0 与新数据 NULL。
     */
    public function claimUnused(int $skuId, int $orderItemId): ?int
    {
        $row = $this->model->where('sku_id', $skuId)
            ->where('status', 'unused')
            ->where(function ($query): void {
                $query->whereNull('order_item_id')->whereOr('order_item_id', 0);
            })
            ->order('id', 'asc')
            ->lock(true)
            ->find();
        if (!$row) {
            return null;
        }

        $affected = $this->model->where('id', $row->id)
            ->where('status', 'unused')
            ->where(function ($query): void {
                $query->whereNull('order_item_id')->whereOr('order_item_id', 0);
            })
            ->update([
                'status'        => 'sold',
                'order_item_id' => $orderItemId,
            ]);

        return $affected > 0 ? (int)$row->id : null;
    }
}
