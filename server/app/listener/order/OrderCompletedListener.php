<?php
declare(strict_types=1);

namespace app\listener\order;

use app\service\member\OrderMemberRewardService;
use think\facade\Log;

class OrderCompletedListener
{
    public function __construct(
        protected OrderMemberRewardService $orderMemberRewardService,
    ) {}

    public function handle(array $event): void
    {
        $orderId = (int)($event['order_id'] ?? 0);
        $userId  = (int)($event['user_id'] ?? 0);

        if (!$orderId || !$userId) {
            return;
        }

        try {
            $this->orderMemberRewardService->handleOrderCompleted($event);
        } catch (\Throwable $e) {
            Log::error('OrderCompletedListener 会员权益发放失败：' . $e->getMessage(), [
                'order_id' => $orderId,
                'user_id'  => $userId,
            ]);
        }
    }
}
