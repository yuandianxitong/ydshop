<?php
declare(strict_types=1);

namespace app\repository\delivery;

use app\model\delivery\DeliveryOrderTrack;
use core\base\Repository;
use think\Model;

class DeliveryOrderTrackRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DeliveryOrderTrack();
    }

    /**
     * 某配送单最近轨迹（按上报时间倒序）
     */
    public function listByDeliveryOrderId(int $deliveryOrderId, int $limit = 50): array
    {
        return $this->model->where('delivery_order_id', $deliveryOrderId)
            ->order('reported_at', 'desc')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
