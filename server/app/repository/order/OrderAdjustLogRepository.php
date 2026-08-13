<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderAdjustLog;
use core\base\Repository;
use think\Model as ThinkModel;

/** 订单拆/合/改价日志（只追加）。create 继承自基类 Repository::create。 */
class OrderAdjustLogRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderAdjustLog();
    }

    /**
     * 某订单的全部调整日志（新→旧）。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByOrderId(int $orderId): array
    {
        return $this->model->where('order_id', $orderId)
            ->order('created_at', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 命中 related_order_ids 的日志（如查子单时回溯父单的拆单记录）。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByRelatedOrderId(int $orderId): array
    {
        return $this->model
            ->whereRaw('JSON_CONTAINS(related_order_ids, ?)', [(string)$orderId])
            ->order('created_at', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }
}
