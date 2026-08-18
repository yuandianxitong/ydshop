<?php
declare(strict_types=1);

namespace plugins\coupon\listener;

use plugins\coupon\service\CouponService;
use think\facade\Log;

/**
 * 订单创建监听器
 *
 * 当订单创建成功后，若订单使用了优惠券，则将优惠券标记为已使用。
 */
class OrderCreatedListener
{
    /**
     * @param array $data {
     *     order_id: int,
     *     coupon_user_id: int,  // 0表示未使用优惠券
     * }
     */
    public function handle(array $data): void
    {
        $couponUserId = (int)($data['coupon_user_id'] ?? 0);
        if ($couponUserId <= 0) {
            return;
        }

        $orderId = (int)($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            return;
        }

        try {
            $service = app(CouponService::class);
            $service->useCoupon($couponUserId, $orderId);
        } catch (\Throwable $e) {
            Log::error('OrderCreatedListener: useCoupon failed', [
                'coupon_user_id' => $couponUserId,
                'order_id'       => $orderId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
