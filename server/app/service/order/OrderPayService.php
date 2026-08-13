<?php
declare(strict_types=1);

namespace app\service\order;

use app\model\order\OrderOrder;
use app\repository\order\OrderOrderRepository;
use app\repository\order\OrderPaymentRepository;
use app\repository\order\OrderItemRepository;
use core\exception\BusinessException;
use core\base\Service;

class OrderPayService extends Service
{
    protected OrderOrderRepository $orderOrderRepository;
    protected OrderPaymentRepository $orderPaymentRepository;
    protected OrderItemRepository $orderItemRepository;

    /**
     * 处理支付成功回调
     *
     * @param string $orderNo  支付单的 out_trade_no（含 ORD_ 前缀）
     * @param string $tradeNo  三方支付交易号
     * @param string|int|float $amount 支付金额
     * @param string $channel  支付渠道
     */
    public function handlePaid(
        string $orderNo,
        string $tradeNo,
        string|int|float $amount,
        string $channel,
        int $paymentOrderId,
        string $eventKey
    ): void {
        // 去除前缀，还原业务订单号
        $bizOrderNo = str_starts_with($orderNo, 'ORD_')
            ? substr($orderNo, 4)
            : $orderNo;

        if ($paymentOrderId <= 0 || $eventKey === '' || $tradeNo === '') {
            throw new BusinessException('支付成功事件缺少可信标识');
        }
        $paidCents = $this->moneyToCents($amount);

        $event = $this->runInTransaction(function () use (
            $bizOrderNo,
            $tradeNo,
            $channel,
            $paymentOrderId,
            $eventKey,
            $paidCents
        ) {
            // 不再反查/锁 payment_orders，固定锁序为 order_orders → order_payments。
            $order = $this->orderOrderRepository->findByOrderNoForUpdate($bizOrderNo);
            if (!$order) {
                throw new BusinessException('业务订单不存在');
            }

            $localCents = $this->moneyToCents($order['pay_amount'] ?? 0);
            if ($localCents !== $paidCents) {
                throw new BusinessException('支付金额与业务订单不匹配');
            }

            $orderId = (int)$order['id'];
            $status = (string)($order['status'] ?? '');
            $transitioned = false;
            if ($status === OrderOrder::STATUS_PENDING) {
                if ($this->orderOrderRepository->markPaidIfPending($orderId, $channel) !== 1) {
                    throw new BusinessException('业务订单状态已变化，请重试');
                }
                $transitioned = true;
            } elseif (!in_array($status, [
                OrderOrder::STATUS_PAID,
                OrderOrder::STATUS_SHIPPED,
                OrderOrder::STATUS_COMPLETED,
            ], true)) {
                throw new BusinessException('当前业务订单状态不可确认付款');
            }

            $paymentSnapshot = $this->orderPaymentRepository->findByPaymentOrderId($paymentOrderId);
            $payment = $paymentSnapshot
                ? $this->orderPaymentRepository->findByIdForUpdate((int)$paymentSnapshot['id'])
                : null;
            if (!$payment) {
                $legacySnapshots = $this->orderPaymentRepository->findByOrderId($orderId);
                $legacyRows = $this->orderPaymentRepository->findByIdsForUpdate(
                    array_column($legacySnapshots, 'id')
                );
                if (count($legacyRows) > 1) {
                    throw new BusinessException('业务订单存在多条历史支付记录，需人工核对');
                }
                if ($legacyRows !== []) {
                    $payment = $legacyRows[0];
                    $boundPaymentOrderId = (int)($payment['payment_order_id'] ?? 0);
                    if ($boundPaymentOrderId > 0 && $boundPaymentOrderId !== $paymentOrderId) {
                        throw new BusinessException('历史支付记录已绑定其他支付单');
                    }
                    if ($boundPaymentOrderId === 0
                        && $this->orderPaymentRepository->bindPaymentOrderIdIfEmpty(
                            (int)$payment['id'],
                            $paymentOrderId
                        ) !== 1) {
                        throw new BusinessException('历史支付记录绑定真实支付单失败');
                    }
                    $payment['payment_order_id'] = $paymentOrderId;
                }
            }
            if ($payment) {
                if ((int)$payment['order_id'] !== $orderId) {
                    throw new BusinessException('支付记录已绑定其他业务订单');
                }
                if ($this->moneyToCents($payment['amount'] ?? 0) !== $localCents) {
                    throw new BusinessException('业务支付记录金额不一致');
                }
                $savedTradeNo = trim((string)($payment['trade_no'] ?? ''));
                if ($savedTradeNo !== '' && !hash_equals($savedTradeNo, $tradeNo)) {
                    throw new BusinessException('业务支付记录交易号不一致');
                }
                $savedChannel = trim((string)($payment['pay_type'] ?? ''));
                if ($savedChannel !== '' && !hash_equals($savedChannel, $channel)) {
                    throw new BusinessException('业务支付记录渠道不一致');
                }
                $paymentStatus = (int)($payment['status'] ?? 0);
                if ($paymentStatus === 0) {
                    if ($this->orderPaymentRepository->markPaidByIdIfPending(
                        (int)$payment['id'],
                        $channel,
                        $tradeNo
                    ) !== 1) {
                        throw new BusinessException('业务支付记录状态已变化，请重试');
                    }
                } elseif ($paymentStatus !== 1) {
                    throw new BusinessException('业务支付记录状态不可确认付款');
                } elseif ($savedChannel === '' || $savedTradeNo === '' || empty($payment['paid_at'])) {
                    $this->orderPaymentRepository->fillPaidMetadataIfMissing(
                        (int)$payment['id'],
                        $channel,
                        $tradeNo,
                        empty($payment['paid_at'])
                    );
                }
            } else {
                $this->orderPaymentRepository->create([
                    'order_id'         => $orderId,
                    'payment_order_id' => $paymentOrderId,
                    'pay_type'         => $channel,
                    'amount'           => $this->formatCents($localCents),
                    'status'           => 1,
                    'trade_no'         => $tradeNo,
                    'paid_at'          => date('Y-m-d H:i:s'),
                ]);
            }

            // 在 order.paid 事件发出前固化虚拟履约待处理标记。即使进程随后
            // 崩溃，payment:reconcile 也能发现并重放卡密发放。
            if (in_array($status, [OrderOrder::STATUS_PENDING, OrderOrder::STATUS_PAID], true)
                && $this->orderItemRepository->isFullyVirtualOrder($orderId)) {
                $this->orderOrderRepository->markVirtualFulfillmentStatus($orderId, 'pending');
            }

            return [
                'transitioned' => $transitioned,
                'order_id'     => $orderId,
                'user_id'      => (int)$order['user_id'],
                'event_key'    => $eventKey,
            ];
        });

        // 合法 paid 重放也再次触发，修复“业务事务已提交、进程在首次 trigger 前
        // 崩溃”的窗口；OrderPaidListener 的各下游以订单状态/唯一键自身幂等。
        $this->trigger('order.paid', [
            'order_id' => $event['order_id'],
            'order_no' => $bizOrderNo,
            'trade_no' => $tradeNo,
            'amount'   => $amount,
            'channel'  => $channel,
            'user_id'  => $event['user_id'],
            'event_key' => $event['event_key'] . ':order.paid',
            'replayed'  => !$event['transitioned'],
        ]);
    }

    /**
     * 零元订单完成支付（须在事务内、已持有业务订单行锁场景下调用，或调用方自行包事务）。
     * 不触发 order.paid；调用方在 commit 后使用 {@see dispatchZeroPaidEvent()}。
     *
     * @return array{
     *   transitioned: bool,
     *   order_id: int,
     *   order_no: string,
     *   user_id: int,
     *   trade_no: string,
     *   amount: string,
     *   channel: string,
     *   event_key: string
     * }
     */
    public function completeZeroPay(int $orderId): array
    {
        $order = $this->orderOrderRepository->findForUpdate($orderId);
        if (!$order) {
            throw new BusinessException('业务订单不存在');
        }

        $payCents = $this->moneyToCents($order['pay_amount'] ?? 0);
        if ($payCents !== 0) {
            throw new BusinessException('订单金额不为零，无法零元完成支付');
        }

        $orderNo = (string)($order['order_no'] ?? '');
        $channel = 'free';
        $tradeNo = 'FREE_' . $orderNo;
        $status = (string)($order['status'] ?? '');
        $transitioned = false;

        if ($status === OrderOrder::STATUS_PENDING) {
            if ($this->orderOrderRepository->markPaidIfPending($orderId, $channel) !== 1) {
                throw new BusinessException('业务订单状态已变化，请重试');
            }
            $transitioned = true;
        } elseif (!in_array($status, [
            OrderOrder::STATUS_PAID,
            OrderOrder::STATUS_SHIPPED,
            OrderOrder::STATUS_COMPLETED,
        ], true)) {
            throw new BusinessException('当前业务订单状态不可确认付款');
        }

        $existingPaid = $this->orderPaymentRepository->findSuccessByOrderId($orderId);
        if ($existingPaid) {
            $savedTradeNo = trim((string)($existingPaid['trade_no'] ?? ''));
            if ($savedTradeNo !== '') {
                $tradeNo = $savedTradeNo;
            }
            $savedChannel = trim((string)($existingPaid['pay_type'] ?? ''));
            if ($savedChannel !== '') {
                $channel = $savedChannel;
            }
        } else {
            $this->orderPaymentRepository->create([
                'order_id'         => $orderId,
                'payment_order_id' => null,
                'pay_type'         => $channel,
                'amount'           => '0.00',
                'status'           => 1,
                'trade_no'         => $tradeNo,
                'paid_at'          => date('Y-m-d H:i:s'),
            ]);
        }

        if (in_array($status, [OrderOrder::STATUS_PENDING, OrderOrder::STATUS_PAID], true)
            && $this->orderItemRepository->isFullyVirtualOrder($orderId)
        ) {
            $this->orderOrderRepository->markVirtualFulfillmentStatus($orderId, 'pending');
        }

        return [
            'transitioned' => $transitioned,
            'order_id'     => $orderId,
            'order_no'     => $orderNo,
            'user_id'      => (int)($order['user_id'] ?? 0),
            'trade_no'     => $tradeNo,
            'amount'       => '0.00',
            'channel'      => $channel,
            'event_key'    => 'free:' . $orderNo,
        ];
    }

    /**
     * 事务提交后派发零元支付的 order.paid。
     *
     * @param array<string, mixed> $payload {@see completeZeroPay()} 返回值
     */
    public function dispatchZeroPaidEvent(array $payload): void
    {
        $this->trigger('order.paid', [
            'order_id'  => (int)($payload['order_id'] ?? 0),
            'order_no'  => (string)($payload['order_no'] ?? ''),
            'trade_no'  => (string)($payload['trade_no'] ?? ''),
            'amount'    => (string)($payload['amount'] ?? '0.00'),
            'channel'   => (string)($payload['channel'] ?? 'free'),
            'user_id'   => (int)($payload['user_id'] ?? 0),
            'event_key' => (string)($payload['event_key'] ?? 'free') . ':order.paid',
            'replayed'  => !((bool)($payload['transitioned'] ?? false)),
        ]);
    }

    protected function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new BusinessException('支付金额格式非法');
        }
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        return ((int)$integer * 100) + (int)str_pad($decimal, 2, '0');
    }

    protected function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
