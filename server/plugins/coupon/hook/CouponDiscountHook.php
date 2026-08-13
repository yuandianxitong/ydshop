<?php
declare(strict_types=1);

namespace plugins\coupon\hook;

use plugins\coupon\service\CouponService;

/**
 * 优惠券折扣钩子
 *
 * 在订单金额计算管道中，将优惠券折扣追加到已有折扣之后执行。
 * priority = 20，排在满减（priority=10）之后。
 */
class CouponDiscountHook
{
    /** 注册到的钩子名称 */
    public string $hook = 'order.calc_discount';

    /** 执行优先级（数字越小越先执行） */
    public int $priority = 20;

    /**
     * @param array $context {
     *     goods_amount: float,   // 商品总金额
     *     coupon_user_id: int,   // 用户优惠券ID（0表示未使用）
     *     spu_ids: int[],        // 购买的SPU列表
     * }
     * @param mixed $currentDiscount 前序钩子累计的折扣金额
     * @return float 累计折扣金额
     */
    public function handle(array $context, $currentDiscount): float
    {
        $couponUserId = (int)($context['coupon_user_id'] ?? 0);
        if ($couponUserId <= 0) {
            return (float)$currentDiscount;
        }

        $service         = app(CouponService::class);
        $remaining       = (float)($context['goods_amount'] ?? 0) - (float)$currentDiscount;
        $couponDiscount  = $service->calcOrderDiscount(
            $couponUserId,
            (int)($context['user_id'] ?? 0),
            $remaining,
            (array)($context['spu_ids'] ?? [])
        );

        return (float)$currentDiscount + $couponDiscount;
    }
}
