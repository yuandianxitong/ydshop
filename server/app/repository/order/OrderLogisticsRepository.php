<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderLogistics;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderLogisticsRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderLogistics();
    }

    /**
     * 按订单 id 取最新一条物流记录（一对一）
     */
    public function findByOrderId(int $orderId): ?array
    {
        $row = $this->model->where('order_id', $orderId)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 更新某订单物流（按 order_id 写）
     */
    public function updateByOrderId(int $orderId, array $data): bool
    {
        return $this->model->where('order_id', $orderId)->update($data) > 0;
    }

    /**
     * 按 id 取模型实例（save() 风格更新时使用）
     */
    public function findModel(int $id): ?ThinkModel
    {
        return $this->model->find($id);
    }

    /**
     * 按 order_id 取模型实例
     */
    public function findModelByOrderId(int $orderId): ?ThinkModel
    {
        return $this->model->where('order_id', $orderId)->find();
    }

    /**
     * 写入面单号（存在则更新，否则插入）
     */
    public function upsertWaybillByOrderId(int $orderId, array $fields): void
    {
        $logistics = $this->model->where('order_id', $orderId)->find();
        if ($logistics) {
            $logistics->save($fields);
            return;
        }
        $this->model->insert(array_merge(['order_id' => $orderId], $fields));
    }
}
