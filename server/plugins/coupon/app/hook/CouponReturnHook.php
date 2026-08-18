<?php
declare(strict_types=1);

namespace plugins\coupon\hook;

use plugins\coupon\service\CouponService;

class CouponReturnHook
{
    public string $hook = 'order.return_coupon';
    public int $priority = 10;

    public function handle(array $context, mixed $prev): mixed
    {
        $orderId = (int) ($context['order_id'] ?? 0);
        if ($orderId > 0) {
            app(CouponService::class)->returnCoupon($orderId);
        }
        return $prev;
    }
}
