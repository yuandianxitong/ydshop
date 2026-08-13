<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderPayment;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderPaymentRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderPayment();
    }

    /**
     * 取订单的支付成功记录（status=1）
     */
    public function findSuccessByOrderId(int $orderId): ?array
    {
        $row = $this->model->where('order_id', $orderId)
            ->where('status', 1)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 把某订单 pending 的支付记录标记为已支付，并写入 trade_no
     *
     * @return int 受影响行数
     */
    public function markPendingPaid(int $orderId, string $tradeNo): int
    {
        return $this->model->where('order_id', $orderId)
            ->where('status', 0)
            ->update([
                'status'   => 1,
                'trade_no' => $tradeNo,
                'paid_at'  => date('Y-m-d H:i:s'),
            ]);
    }

    /** 按真实支付单 ID 加锁读取；该字段由数据库唯一约束兜底。 */
    public function findByPaymentOrderIdForUpdate(int $paymentOrderId): ?array
    {
        $row = $this->model
            ->where('payment_order_id', $paymentOrderId)
            ->lock(true)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /** 先普通读取命中主键，再按主键加锁，避免唯一索引缺行时获取 gap lock。 */
    public function findByPaymentOrderId(int $paymentOrderId): ?array
    {
        $row = $this->model->where('payment_order_id', $paymentOrderId)->find();
        return $row ? $row->toArray() : null;
    }

    public function findByIdForUpdate(int $id): ?array
    {
        $row = $this->model->where('id', $id)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 按业务订单锁定全部支付行，用于接管旧版 payment_order_id 为 NULL/0 的
     * canonical 记录。Service 必须在持有 order_orders 行锁后调用。
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByOrderIdForUpdate(int $orderId): array
    {
        return $this->model
            ->where('order_id', $orderId)
            ->order('id', 'asc')
            ->lock(true)
            ->select()
            ->toArray();
    }

    /** 普通读取旧行身份；调用方已持有 order_orders 行锁。 */
    public function findByOrderId(int $orderId): array
    {
        return $this->model
            ->where('order_id', $orderId)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /** @param int[] $ids */
    public function findByIdsForUpdate(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        return $this->model->whereIn('id', $ids)->order('id', 'asc')->lock(true)->select()->toArray();
    }

    /** 原子绑定旧支付行；只允许从 NULL/0 绑定到真实支付单 ID。 */
    public function bindPaymentOrderIdIfEmpty(int $id, int $paymentOrderId): int
    {
        return $this->model
            ->where('id', $id)
            ->where(function ($query) {
                $query->whereNull('payment_order_id')->whereOr('payment_order_id', 0);
            })
            ->update(['payment_order_id' => $paymentOrderId]);
    }

    /** 已支付历史行缺少渠道/交易号/时间时，以可信支付事件补齐。 */
    public function fillPaidMetadataIfMissing(
        int $id,
        string $channel,
        string $tradeNo,
        bool $missingPaidAt
    ): int
    {
        $data = [
            'pay_type' => $channel,
            'trade_no' => $tradeNo,
        ];
        if ($missingPaidAt) {
            $data['paid_at'] = date('Y-m-d H:i:s');
        }

        return $this->model
            ->where('id', $id)
            ->where('status', 1)
            ->update($data);
    }

    /** 将已绑定真实 payment_order_id 的旧 pending 行补齐为已支付。 */
    public function markPaidByIdIfPending(int $id, string $channel, string $tradeNo): int
    {
        return $this->model
            ->where('id', $id)
            ->where('status', 0)
            ->update([
                'pay_type' => $channel,
                'status'   => 1,
                'trade_no' => $tradeNo,
                'paid_at'  => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * 支付单累计全退后才把业务支付记录标记为已退款。
     */
    public function markFullyRefunded(int $orderId): int
    {
        return $this->model->where('order_id', $orderId)
            ->where('status', 1)
            ->update([
                'status'      => 2,
                'refunded_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
