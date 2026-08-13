<?php
declare(strict_types=1);

namespace app\listener\user;

use app\service\user\UserOperationLogService;

/**
 * payment.success 事件 → 写入 user_operation_logs
 *
 * 事件 payload（来自 PaymentService）：
 *   - order_no, trade_no, amount, channel, user_id, biz_type
 *
 * biz_type 区分：order_payment（普通订单） / recharge（充值）
 */
class UserOpLogPaymentListener
{
    public function handle(array $event): void
    {
        $userId = (int)($event['user_id'] ?? 0);
        if ($userId <= 0) return;

        $orderNo = (string)($event['order_no'] ?? '');
        $amount  = (float)($event['amount']    ?? 0);
        $channel = (string)($event['channel']  ?? '');
        $bizType = (string)($event['biz_type'] ?? '');
        $eventKey = trim((string)($event['event_key'] ?? ''));
        if ($eventKey === '') return;

        $isRecharge = $bizType === 'recharge';

        $svc = app(UserOperationLogService::class);
        if ($isRecharge) {
            $svc->recordAsset(
                $userId,
                'balance.recharge',
                '账户充值成功',
                sprintf('单号 %s · +¥ %s%s', $orderNo, number_format($amount, 2), $channel ? ' · ' . $channel : ''),
                ['order_no' => $orderNo, 'amount' => $amount, 'channel' => $channel],
                $eventKey
            );
        } else {
            $svc->recordOrder(
                $userId,
                'order.paid',
                '订单支付成功',
                sprintf('订单 %s · ¥ %s%s', $orderNo, number_format($amount, 2), $channel ? ' · ' . $channel : ''),
                ['order_no' => $orderNo, 'amount' => $amount, 'channel' => $channel],
                $eventKey
            );
        }
    }
}
