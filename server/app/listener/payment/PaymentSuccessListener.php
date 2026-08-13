<?php
declare(strict_types=1);
namespace app\listener\payment;

use app\repository\user\UserRepository;
use app\service\member\MemberRechargeService;
use app\service\message\MessageService;
use core\exception\BusinessException;
use think\facade\Log;

class PaymentSuccessListener
{
    public function handle(array $event): void
    {
        $this->reportInfo('支付成功', [
            'order_no' => $event['order_no'],
            'trade_no' => $event['trade_no'],
            'amount'   => $event['amount'],
            'channel'  => $event['channel'],
        ]);

        $bizType = $event['biz_type'] ?? '';

        try {
            match ($bizType) {
                'recharge' => $this->handleRecharge($event),
                'order' => $this->handleOrder($event),
                default => throw new BusinessException('未识别的支付业务类型'),
            };
        } catch (\Throwable $e) {
            // 结算属于 payment.success 的核心同步消费者。记录后必须上抛：在线
            // query/notify 由 PaymentService 的软边界吞并，手工 resync 的严格边界
            // 则会把本次重放标为 failed，避免“日志报错但命令显示成功”。
            $this->reportError('支付业务结算失败: ' . $e->getMessage(), [
                'event_key' => $event['event_key'] ?? '',
                'order_no'  => $event['order_no'] ?? '',
            ]);
            throw $e;
        }

        // 通知不影响资金/订单结算，保持 best-effort。
        try {
            $this->sendPaymentNotification($event);
        } catch (\Throwable $e) {
            $this->reportError('支付成功通知发送失败: ' . $e->getMessage(), [
                'event_key' => $event['event_key'] ?? '',
                'order_no'  => $event['order_no'] ?? '',
            ]);
        }
    }

    protected function handleOrder(array $data): void
    {
        $orderPayService = app(\app\service\order\OrderPayService::class);
        $orderPayService->handlePaid(
            (string)($data['business_order_no'] ?? $data['order_no']),
            (string)($data['trade_no'] ?? ''),
            (string)$data['amount'],
            (string)$data['channel'],
            (int)($data['payment_order_id'] ?? 0),
            (string)($data['event_key'] ?? '')
        );
    }

    protected function handleRecharge(array $event): void
    {
        $userId = (int)($event['user_id'] ?? 0);
        $orderNo = trim((string)($event['order_no'] ?? ''));
        $channel = trim((string)($event['channel'] ?? ''));
        $amount = trim((string)($event['amount'] ?? ''));
        $paymentOrderId = (int)($event['payment_order_id'] ?? 0);
        $eventKey = trim((string)($event['event_key'] ?? ''));

        if (
            $userId <= 0
            || $amount === ''
            || $orderNo === ''
            || $channel === ''
            || $paymentOrderId <= 0
            || $eventKey === ''
        ) {
            throw new BusinessException('充值支付事件参数不完整');
        }

        // 所有资产与最终状态由 MemberRechargeService 在同一事务处理；这里不再提供
        // “只充实付余额”的降级路径，避免把半成品永久标记为已完成。
        $service = app(MemberRechargeService::class);
        $service->handleRechargeSuccess(
            $orderNo,
            $amount,
            $channel,
            $paymentOrderId,
            $eventKey
        );
    }

    protected function sendPaymentNotification(array $event): void
    {
        $userId = (int) ($event['user_id'] ?? 0);
        if (!$userId) {
            return;
        }

        $user = app(UserRepository::class)->findModel($userId);
        if (!$user) {
            return;
        }

        $receivers = array_filter([
            'phone'       => $user->mobile ?? '',
            'openid'      => $user->oa_openid ?? '',
            'mini_openid' => $user->mini_openid ?? '',
        ]);

        if (!empty($receivers)) {
            app(MessageService::class)->trySend('payment_success', $receivers, [
                'amount'   => (string) ($event['amount'] ?? ''),
                'order_no' => $event['order_no'] ?? '',
            ], (string)($event['event_key'] ?? ''));
        }
    }

    /** 记录器故障不能覆盖原始结算异常。 */
    protected function reportInfo(string $message, array $context = []): void
    {
        try {
            Log::info($message, $context);
        } catch (\Throwable) {
        }
    }

    /** 记录器故障不能覆盖原始结算异常。 */
    protected function reportError(string $message, array $context = []): void
    {
        try {
            Log::error($message, $context);
        } catch (\Throwable) {
        }
    }
}
