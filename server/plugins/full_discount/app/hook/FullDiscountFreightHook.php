<?php
declare(strict_types=1);

namespace plugins\full_discount\hook;

use plugins\full_discount\service\FullDiscountService;

class FullDiscountFreightHook
{
    public string $hook = 'order.freight_benefit';
    public int $priority = 10;

    public function handle(array $context, mixed $prev): string
    {
        $freight = is_string($prev) ? $prev : (string) $prev;
        if ((float) $freight <= 0) {
            return '0.00';
        }
        $goodsAmount = (float) ($context['goods_amount'] ?? 0);
        $spuIds      = array_map('intval', (array) ($context['spu_ids'] ?? []));
        if (app(FullDiscountService::class)->hasMatchingFreightDiscount($goodsAmount, $spuIds)) {
            return '0.00';
        }
        return $freight;
    }
}
