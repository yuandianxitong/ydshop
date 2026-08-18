<?php
declare(strict_types=1);

namespace plugins\coupon\hook;

use plugins\coupon\service\CouponService;

class CouponRebindHook
{
    public string $hook = 'order.rebind_coupon';
    public int $priority = 10;

    public function handle(array $context, mixed $prev): mixed
    {
        $from = (int) ($context['from_order_id'] ?? 0);
        $to   = (int) ($context['to_order_id'] ?? 0);
        if ($from > 0 && $to > 0) {
            app(CouponService::class)->rebindOrder($from, $to);
        }
        return $prev;
    }
}
