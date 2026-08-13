<?php
declare(strict_types=1);

namespace app\repository\payment;

use app\model\payment\PaymentOrder;
use core\base\Repository;
use think\Model;

class PaymentOrderRepository extends Repository
{
    protected function getModel(): Model
    {
        return new PaymentOrder();
    }

    /**
     * 根据订单号查找
     */
    public function findByOrderNo(string $orderNo): ?Model
    {
        return PaymentOrder::findByOrderNo($orderNo);
    }

    /**
     * 按支付单号和用户双重约束查询 C 端支付单。
     */
    public function findByOrderNoAndUser(string $orderNo, int $userId): ?Model
    {
        return $this->model
            ->where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->find();
    }

    /**
     * 读取商城业务订单对应的全部支付尝试。新记录使用 business_order_no
     * 明确关联；旧记录仅在该字段为空时兼容原始单号和 ORD_ 前缀。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBusinessOrderCandidates(string $businessOrderNo): array
    {
        return $this->businessOrderCandidatesQuery($businessOrderNo)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 调用方须先锁 order_orders，随后以固定 order -> payment 顺序锁支付候选。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBusinessOrderCandidatesForUpdate(string $businessOrderNo): array
    {
        return $this->businessOrderCandidatesQuery($businessOrderNo)
            ->order('id', 'asc')
            ->lock(true)
            ->select()
            ->toArray();
    }

    private function businessOrderCandidatesQuery(string $businessOrderNo)
    {
        $numbers = array_values(array_unique([
            $businessOrderNo,
            'ORD_' . $businessOrderNo,
        ]));

        return $this->model
            ->where(function ($query): void {
                $query->where('biz_type', 'order')
                    ->whereNull('biz_type', 'OR')
                    ->whereOr('biz_type', '');
            })
            ->where(function ($query) use ($businessOrderNo, $numbers): void {
                $query->where(function ($linked) use ($businessOrderNo): void {
                    $linked->where('business_order_no', $businessOrderNo)
                        ->where('biz_type', 'order');
                })->whereOr(function ($legacy) use ($numbers): void {
                    $legacy->whereIn('order_no', $numbers)
                        ->where(function ($emptyLink): void {
                            $emptyLink->whereNull('business_order_no')
                                ->whereOr('business_order_no', '');
                        });
                });
            });
    }

    /**
     * 按 order_no 取支付平台单的 id（轻量查询，不加载完整 Model）
     */
    public function findIdByOrderNo(string $orderNo): ?int
    {
        $id = $this->model->where('order_no', $orderNo)->value('id');
        return $id ? (int)$id : null;
    }

    /**
     * 列出本地已支付单供离线幂等重放；不访问 provider。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaidForResync(?string $orderNo = null): array
    {
        $query = $this->model
            ->where('status', PaymentOrder::STATUS_PAID)
            ->order('id', 'asc');
        if ($orderNo !== null && $orderNo !== '') {
            $query->where('order_no', $orderNo);
        }
        return $query->select()->toArray();
    }

    /**
     * 自动补偿只重放发布边界后的本地 paid 记录，避免首次部署把全部历史支付
     * 重新广播给可能没有旧幂等账本的扩展监听器。手工 payment:resync 仍保留
     * 全量排障能力。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUnsettledPaidForAutomaticReconcile(string $paidFrom): array
    {
        return $this->model
            ->alias('payment')
            ->leftJoin(
                'order_orders business_order',
                "business_order.order_no = payment.business_order_no
                OR (
                    (payment.business_order_no IS NULL OR payment.business_order_no = '')
                    AND (
                        business_order.order_no = payment.order_no
                        OR CONCAT('ORD_', business_order.order_no) = payment.order_no
                    )
                )"
            )
            ->leftJoin('order_payments business_payment', 'business_payment.payment_order_id = payment.id')
            ->leftJoin('delivery_orders business_delivery', 'business_delivery.order_id = business_order.id')
            ->leftJoin(
                'member_recharge_orders recharge',
                "recharge.payment_order_id = payment.id OR CONCAT('RCH_', recharge.order_no) = payment.order_no"
            )
            ->where('payment.status', PaymentOrder::STATUS_PAID)
            ->whereNotNull('payment.paid_at')
            ->where('payment.paid_at', '>=', $paidFrom)
            ->whereRaw("(
                (
                    (payment.biz_type = 'order' OR ((payment.biz_type IS NULL OR payment.biz_type = '') AND business_order.id IS NOT NULL))
                    AND (
                        business_order.id IS NULL
                        OR business_order.status = 'pending'
                        OR business_payment.id IS NULL
                        OR business_payment.order_id <> business_order.id
                        OR business_payment.status <> 1
                        OR (business_order.delivery_type = 'merchant' AND business_delivery.id IS NULL)
                        OR business_order.virtual_fulfillment_status IN ('pending', 'failed')
                    )
                )
                OR
                (
                    (payment.biz_type = 'recharge' OR ((payment.biz_type IS NULL OR payment.biz_type = '') AND recharge.id IS NOT NULL))
                    AND (recharge.id IS NULL OR recharge.settled_at IS NULL)
                )
            )")
            ->field('payment.*')
            ->distinct(true)
            ->order('payment.id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 扫描长时间停留在 provider 操作屏障中的支付单。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStaleOperationsAfterId(
        int $afterId,
        int $limit,
        string $staleBefore
    ): array {
        return $this->model
            ->where('id', '>', max(0, $afterId))
            ->whereIn('status', [PaymentOrder::STATUS_CREATING, PaymentOrder::STATUS_CLOSING])
            ->where('updated_at', '<=', $staleBefore)
            ->order('id', 'asc')
            ->limit(min(1000, max(1, $limit)))
            ->select()
            ->toArray();
    }

    /**
     * 受发布边界与安全延迟限制的近期渠道对账窗口。游标只按主键前进，
     * provider 查询失败时下次仍可重试，不会因一条坏数据阻塞整个表。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProviderReconcileCandidatesAfterId(
        int $afterId,
        int $limit,
        string $createdFrom,
        string $updatedBefore
    ): array {
        return $this->model
            ->where('id', '>', max(0, $afterId))
            ->whereIn('status', [
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_CREATING,
                PaymentOrder::STATUS_CLOSING,
            ])
            ->where('created_at', '>=', $createdFrom)
            ->where('updated_at', '<=', $updatedBefore)
            ->order('id', 'asc')
            ->limit(min(100, max(1, $limit)))
            ->select()
            ->toArray();
    }

    /**
     * 按每行 next_at 调度的渠道对账队列。它不依赖全局游标，因此单行永久
     * 失败或持续新增支付单都不会饿死其他到期记录。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDueProviderReconcileCandidates(
        int $limit,
        string $createdFrom,
        string $updatedBefore,
        string $dueAt
    ): array {
        return $this->model
            ->whereIn('status', [
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_CREATING,
                PaymentOrder::STATUS_CLOSING,
            ])
            ->where('created_at', '>=', $createdFrom)
            ->where('updated_at', '<=', $updatedBefore)
            ->where(function ($query) use ($dueAt): void {
                $query->whereNull('provider_reconcile_next_at')
                    ->whereOr('provider_reconcile_next_at', '<=', $dueAt);
            })
            ->orderRaw('COALESCE(provider_reconcile_next_at, created_at) ASC')
            ->order('id', 'asc')
            ->limit(min(100, max(1, $limit)))
            ->select()
            ->toArray();
    }

    public function recordProviderReconcileSuccess(int $paymentOrderId, string $nextAt): bool
    {
        return $this->model->where('id', $paymentOrderId)->update([
            'provider_reconcile_retry_count' => 0,
            'provider_reconcile_next_at' => $nextAt,
            'provider_reconcile_last_error' => '',
        ]) !== false;
    }

    public function recordProviderReconcileFailure(
        int $paymentOrderId,
        string $nextAt,
        string $error
    ): bool {
        return $this->model
            ->where('id', $paymentOrderId)
            ->inc('provider_reconcile_retry_count')
            ->update([
                'provider_reconcile_next_at' => $nextAt,
                'provider_reconcile_last_error' => mb_substr($error, 0, 500),
            ]) !== false;
    }

    /**
     * 切换收款主体前的保守阻断计数：未决凭据和仍可退款的已支付单都必须继续
     * 使用原收款主体。证书/密钥轮换不调用本方法，只有主体标识变更才调用。
     */
    public function countAccountChangeBlockers(string $channel): int
    {
        return $this->model
            ->where('channel', $channel)
            ->where(function ($query): void {
                $query->whereIn('status', [
                    PaymentOrder::STATUS_CREATING,
                    PaymentOrder::STATUS_PENDING,
                    PaymentOrder::STATUS_CLOSING,
                ])->whereOr(function ($paid): void {
                    $paid->whereIn('status', [
                        PaymentOrder::STATUS_PAID,
                        PaymentOrder::STATUS_REFUNDED,
                    ])
                        ->whereRaw('ROUND(refund_amount * 100) < ROUND(total_amount * 100)');
                });
            })
            ->count();
    }

    /** 更换客户端 AppID/APIv3 解密密钥前，必须先排空所有未决支付凭据。 */
    public function countUnresolvedByChannel(string $channel): int
    {
        return $this->model
            ->where('channel', $channel)
            ->whereIn('status', [
                PaymentOrder::STATUS_CREATING,
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_CLOSING,
            ])
            ->count();
    }

    /**
     * 根据订单号查找并加行锁（用于回调处理）
     */
    public function findByOrderNoForUpdate(string $orderNo): ?Model
    {
        return $this->model->where('order_no', $orderNo)->lock(true)->find();
    }

    /**
     * 将待支付/创建中支付单原子地推进为已支付。
     *
     * 调用方必须先通过 findByOrderNoForUpdate() 持有该支付单行锁；这里仍保留
     * status 条件作为 CAS 防线，避免以后调用链调整后发生状态倒流。
     */
    public function markPaidIfPending(
        int $paymentOrderId,
        string $tradeNo,
        string $notifyData
    ): int {
        return $this->model
            ->where('id', $paymentOrderId)
            ->whereIn('status', [
                PaymentOrder::STATUS_PENDING,
                PaymentOrder::STATUS_CREATING,
                PaymentOrder::STATUS_CLOSING,
            ])
            ->update([
                'status'      => PaymentOrder::STATUS_PAID,
                'trade_no'    => $tradeNo,
                'paid_at'     => date('Y-m-d H:i:s'),
                'notify_data' => $notifyData,
            ]);
    }

    /** 将渠道已关闭的待支付单原子推进为 closed。 */
    public function markClosedIfPending(int $paymentOrderId): int
    {
        return $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_PENDING)
            ->update(['status' => PaymentOrder::STATUS_CLOSED]);
    }

    /** 取消流程在外呼 provider close 前占用关闭权。 */
    public function markClosingIfPending(int $paymentOrderId): int
    {
        return $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_PENDING)
            ->update(['status' => PaymentOrder::STATUS_CLOSING]);
    }

    /** provider 明确关闭后结束 closing 屏障。 */
    public function markClosedIfClosing(int $paymentOrderId): int
    {
        return $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_CLOSING)
            ->update(['status' => PaymentOrder::STATUS_CLOSED]);
    }

    /**
     * 在 provider create 外呼前占用创建权；元数据与 creating 状态同一 CAS 写入。
     *
     * @param array<string, mixed> $metadata
     */
    public function markCreatingIfPending(int $paymentOrderId, array $metadata = []): int
    {
        unset($metadata['id'], $metadata['order_no'], $metadata['channel'], $metadata['total_amount']);
        $metadata['status'] = PaymentOrder::STATUS_CREATING;

        return $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_PENDING)
            ->update($metadata);
    }

    /** provider create 已返回后释放创建屏障。 */
    public function markPendingIfCreating(
        int $paymentOrderId,
        array $data = [],
        ?string $attemptToken = null
    ): int
    {
        unset($data['id'], $data['order_no'], $data['channel'], $data['total_amount']);
        $data['status'] = PaymentOrder::STATUS_PENDING;
        $query = $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_CREATING);
        if ($attemptToken !== null) {
            $query->where('provider_attempt_token', $attemptToken);
        }
        return $query->update($data);
    }

    /** provider 未创建交易且本地签发凭据仍有效时，释放 closing 供用户继续支付。 */
    public function markPendingIfClosing(int $paymentOrderId): int
    {
        return $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_CLOSING)
            ->update(['status' => PaymentOrder::STATUS_PENDING]);
    }

    /** 业务订单已不可支付时，在渠道关闭成功后结束 creating 屏障。 */
    public function markClosedIfCreating(int $paymentOrderId, ?string $attemptToken = null): int
    {
        $query = $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_CREATING);
        if ($attemptToken !== null) {
            $query->where('provider_attempt_token', $attemptToken);
        }
        return $query->update(['status' => PaymentOrder::STATUS_CLOSED]);
    }

    /**
     * 修复早期已支付但未落第三方交易号的记录，不覆盖已有交易号。
     */
    public function fillPaidTradeNoIfEmpty(int $paymentOrderId, string $tradeNo): int
    {
        return $this->model
            ->where('id', $paymentOrderId)
            ->where('status', PaymentOrder::STATUS_PAID)
            ->where(function ($query) {
                $query->whereNull('trade_no')->whereOr('trade_no', '');
            })
            ->update(['trade_no' => $tradeNo]);
    }

    /**
     * 按主键加行锁读取支付单，序列化同一支付单的退款预占与结算。
     */
    public function findForUpdate(int $paymentOrderId): ?Model
    {
        return $this->model->where('id', $paymentOrderId)->lock(true)->find();
    }

    /**
     * 写入累计退款金额；只有累计全退时才进入 refunded 状态。
     */
    public function recordRefund(int $paymentOrderId, float $refundAmount, bool $fullyRefunded): bool
    {
        $data = [
            'refund_amount' => round($refundAmount, 2),
            'status'        => $fullyRefunded ? PaymentOrder::STATUS_REFUNDED : PaymentOrder::STATUS_PAID,
        ];
        if ($fullyRefunded) {
            $data['refunded_at'] = date('Y-m-d H:i:s');
        }

        return $this->model->where('id', $paymentOrderId)->update($data) > 0;
    }

    /**
     * 创建支付订单并返回模型实例
     */
    public function createOrder(array $data): Model
    {
        return PaymentOrder::create($data);
    }

    /**
     * 根据订单号更新订单数据
     */
    public function updateByOrderNo(string $orderNo, array $data): bool
    {
        return $this->model->where('order_no', $orderNo)->update($data) !== false;
    }
}
