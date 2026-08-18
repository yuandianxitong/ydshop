<?php
declare(strict_types=1);

namespace plugins\coupon\hook;

use plugins\coupon\service\CouponService;

class CouponAfterCreateHook
{
    public string $hook = 'order.after_create';
    public int $priority = 10;

    public function handle(array $context, mixed $prev): mixed
    {
        $couponUserId = (int) ($context['coupon_user_id'] ?? 0);
        $userId       = (int) ($context['user_id'] ?? 0);
        $orderId      = (int) ($context['order_id'] ?? 0);
        if ($couponUserId > 0 && $orderId > 0) {
            app(CouponService::class)->useCoupon($couponUserId, $orderId, $userId);
        }
        return $prev;
    }
}
