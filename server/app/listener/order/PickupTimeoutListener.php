<?php
declare(strict_types=1);

namespace app\listener\order;

use app\repository\order\OrderOrderRepository;
use app\service\message\MessageService;
use think\facade\Log;

class PickupTimeoutListener
{
    public function __construct(
        protected MessageService $messageService,
        protected OrderOrderRepository $orderOrderRepository,
    ) {
    }

    public function handle(array $payload): void
    {
        $orderId = (int)($payload['order_id'] ?? 0);

        if ($orderId <= 0) {
            return;
        }

        $order = $this->orderOrderRepository->find($orderId);
        if (!$order) {
            return;
        }

        try {
            $this->messageService->sendToUser((int)$order['user_id'], 'pickup_timeout', [
                'order_no' => $order['order_no'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('PickupTimeout 消息发送失败: ' . $e->getMessage(), [
                'order_id' => $orderId,
            ]);
        }
    }
}
