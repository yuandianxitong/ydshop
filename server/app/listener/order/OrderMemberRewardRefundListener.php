<?php

declare(strict_types=1);

namespace app\listener\order;

use app\service\member\OrderMemberRewardService;
use think\facade\Log;

class OrderMemberRewardRefundListener
{
    public function __construct(
        protected OrderMemberRewardService $orderMemberRewardService,
    ) {
    }

    public function handle(array $event): void
    {
        try {
            $this->orderMemberRewardService->handleRefundCompleted($event);
        } catch (\Throwable $e) {
            Log::error('订单退款会员权益冲正失败', [
                'refund_id' => (int)($event['refund_id'] ?? 0),
                'order_id' => (int)($event['order_id'] ?? 0),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
