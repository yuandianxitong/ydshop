<?php
declare(strict_types=1);

namespace plugins\coupon\listener;

use plugins\coupon\service\CouponService;
use think\facade\Log;

/**
 * 订单取消监听器
 *
 * 当订单取消后，将该订单绑定的优惠券退还给用户。
 */
class OrderCancelledListener
{
    /**
     * @param array $data {
     *     order_id: int,
     * }
     */
    public function handle(array $data): void
    {
        $orderId = (int)($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            return;
        }

        try {
            $service = app(CouponService::class);
            $service->returnCoupon($orderId);
        } catch (\Throwable $e) {
            Log::error('OrderCancelledListener: returnCoupon failed', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
