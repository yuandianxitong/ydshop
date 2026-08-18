<?php
declare(strict_types=1);

namespace plugins\full_discount\hook;

use plugins\full_discount\service\FullDiscountService;

/**
 * 满减折扣 Hook
 *
 * 注册到 order.calc_discount 钩子，priority=10（先于优惠券执行）。
 *
 * Context 结构：
 *   - goods_amount float   商品总金额
 *   - spu_ids      int[]   购物车中的 SPU ID 列表
 *   - freight      float   运费（freight 类型活动由上层处理）
 */
class FullDiscountHook
{
    /** 钩子标识，由 PluginManager 读取 */
    public string $hook = 'order.calc_discount';

    /** 优先级：10 = 先于优惠券（通常为 20）执行 */
    public int $priority = 10;

    /**
     * 计算并累加满减折扣金额
     *
     * @param array $context        钩子上下文（goods_amount, spu_ids, freight, ...）
     * @param mixed $currentDiscount 当前已累计的折扣金额
     * @return float                 累计折扣金额（含本次满减）
     */
    public function handle(array $context, $currentDiscount): float
    {
        /** @var FullDiscountService $service */
        $service  = app(FullDiscountService::class);
        $discount = $service->getMatchingDiscount(
            (float)($context['goods_amount'] ?? 0),
            $context['spu_ids'] ?? []
        );

        if (!$discount) {
            return (float)$currentDiscount;
        }

        // 在已累计折扣后的余额基础上再计算满减
        $remaining = (float)($context['goods_amount'] ?? 0) - (float)$currentDiscount;
        $amount    = $service->calcDiscount($discount, $remaining);

        return (float)$currentDiscount + $amount;
    }
}
