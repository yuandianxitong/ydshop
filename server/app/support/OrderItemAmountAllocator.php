<?php
declare(strict_types=1);

namespace app\support;

use InvalidArgumentException;

/**
 * 订单行金额分摊器。
 *
 * 所有计算都使用整数“分”；先按商品小计比例向下取整，
 * 优惠尾差从最后一行向前按容量分配，运费尾差由最后一行承接，
 * 保证汇总严格一致且每行净实付不为负数。
 */
final class OrderItemAmountAllocator
{
    /**
     * @param array<int, int|float|string> $lineTotals
     * @return array<int, array{total_amount:float, discount_amount:float, freight_amount:float, pay_amount:float}>
     */
    public static function allocate(array $lineTotals, mixed $discountAmount, mixed $freightAmount): array
    {
        if ($lineTotals === []) {
            throw new InvalidArgumentException('订单行不能为空');
        }

        $lineCents = array_map([self::class, 'toCents'], array_values($lineTotals));
        foreach ($lineCents as $lineAmount) {
            if ($lineAmount <= 0) {
                throw new InvalidArgumentException('商品行金额必须大于 0');
            }
        }
        $goodsCents = array_sum($lineCents);
        $discountCents = self::toCents($discountAmount);
        $freightCents = self::toCents($freightAmount);

        if ($goodsCents <= 0) {
            throw new InvalidArgumentException('商品总额必须大于 0');
        }
        if ($discountCents < 0 || $discountCents > $goodsCents) {
            throw new InvalidArgumentException('优惠金额不合法');
        }
        if ($freightCents < 0) {
            throw new InvalidArgumentException('运费不能为负数');
        }

        $lastIndex = count($lineCents) - 1;
        $discountShares = [];
        foreach ($lineCents as $totalCents) {
            $discountShares[] = self::proportionalShare($discountCents, $totalCents, $goodsCents);
        }

        // 比例 floor 产生的优惠余分从最后一行向前分配，但不得超过该行小计。
        // 例如 3 行各 1 分、优惠 2 分，将得到 [0, 1, 1]，不会产生负实付。
        $remainingDiscount = $discountCents - array_sum($discountShares);
        for ($index = $lastIndex; $index >= 0 && $remainingDiscount > 0; $index--) {
            $capacity = $lineCents[$index] - $discountShares[$index];
            $extra = min($remainingDiscount, $capacity);
            $discountShares[$index] += $extra;
            $remainingDiscount -= $extra;
        }
        if ($remainingDiscount !== 0) {
            throw new InvalidArgumentException('优惠金额无法分摊');
        }

        $remainingFreight = $freightCents;
        $result = [];

        foreach ($lineCents as $index => $totalCents) {
            $lineDiscount = $discountShares[$index];
            if ($index === $lastIndex) {
                $lineFreight = $remainingFreight;
            } else {
                $lineFreight = self::proportionalShare($freightCents, $totalCents, $goodsCents);
                $remainingFreight -= $lineFreight;
            }

            $result[] = [
                'total_amount'    => self::fromCents($totalCents),
                'discount_amount' => self::fromCents($lineDiscount),
                'freight_amount'  => self::fromCents($lineFreight),
                'pay_amount'      => self::fromCents($totalCents - $lineDiscount + $lineFreight),
            ];
        }

        return $result;
    }

    public static function toCents(mixed $amount): int
    {
        return (int)round((float)$amount * 100);
    }

    public static function fromCents(int $amount): float
    {
        return round($amount / 100, 2);
    }

    private static function proportionalShare(int $amount, int $lineTotal, int $goodsTotal): int
    {
        if ($amount === 0 || $lineTotal === 0) {
            return 0;
        }

        // decimal(10,2) 的分值相乘可能超过 64-bit int。使用十进制
        // 字符串乘法 + 长除法，既保持整数精度，也不引入 ext-bcmath 硬依赖。
        $product = self::multiplyDecimalStrings((string)$amount, (string)$lineTotal);
        return self::divideDecimalStringByInt($product, $goodsTotal);
    }

    private static function multiplyDecimalStrings(string $left, string $right): string
    {
        $a = array_reverse(array_map('intval', str_split($left)));
        $b = array_reverse(array_map('intval', str_split($right)));
        $digits = array_fill(0, count($a) + count($b), 0);

        foreach ($a as $i => $leftDigit) {
            foreach ($b as $j => $rightDigit) {
                $digits[$i + $j] += $leftDigit * $rightDigit;
            }
        }

        for ($i = 0, $count = count($digits) - 1; $i < $count; $i++) {
            $digits[$i + 1] += intdiv($digits[$i], 10);
            $digits[$i] %= 10;
        }
        while (count($digits) > 1 && end($digits) === 0) {
            array_pop($digits);
        }

        return implode('', array_reverse($digits));
    }

    private static function divideDecimalStringByInt(string $number, int $divisor): int
    {
        $quotient = '';
        $remainder = 0;

        foreach (str_split($number) as $digit) {
            // remainder < divisor，且 schema 中金额最多 10 位“分”，此处不会溢出。
            $current = $remainder * 10 + (int)$digit;
            $quotientDigit = intdiv($current, $divisor);
            if ($quotient !== '' || $quotientDigit > 0) {
                $quotient .= (string)$quotientDigit;
            }
            $remainder = $current % $divisor;
        }

        return $quotient === '' ? 0 : (int)$quotient;
    }
}
