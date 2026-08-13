<?php
declare(strict_types=1);

namespace app\support;

/**
 * 轻量 5 段 cron 表达式匹配器（分 时 日 月 周）。
 *
 * 支持 `*`、`*\/n`、`a`、`a-b`、`a,b,c` 及组合（如 `1-5,10`、`a-b/n`）；
 * 周字段 0 和 7 都视为周日；非法表达式一律返回 false，不抛异常。
 * 纯静态无状态，不依赖框架容器。
 */
final class CronExpressionMatcher
{
    /**
     * 判断表达式在给定时间戳（按所在分钟）是否命中
     */
    public static function isDue(string $expression, int $timestamp): bool
    {
        $fields = preg_split('/\s+/', trim($expression));
        if (!is_array($fields) || count($fields) !== 5) {
            return false;
        }

        [$minute, $hour, $day, $month, $weekday] = $fields;

        return self::matchField($minute, (int) date('i', $timestamp), 0, 59)
            && self::matchField($hour, (int) date('G', $timestamp), 0, 23)
            && self::matchField($day, (int) date('j', $timestamp), 1, 31)
            && self::matchField($month, (int) date('n', $timestamp), 1, 12)
            && self::matchWeekday($weekday, (int) date('w', $timestamp));
    }

    /**
     * 周字段匹配：0 和 7 都算周日
     */
    private static function matchWeekday(string $field, int $weekday): bool
    {
        if (self::matchField($field, $weekday, 0, 7)) {
            return true;
        }
        return $weekday === 0 && self::matchField($field, 7, 0, 7);
    }

    /**
     * 匹配单个字段（可含逗号分隔的多个片段）
     *
     * 任一片段非法则整个字段视为非法，直接返回 false。
     */
    private static function matchField(string $field, int $value, int $min, int $max): bool
    {
        $matched = false;
        foreach (explode(',', trim($field)) as $part) {
            $result = self::matchPart(trim($part), $value, $min, $max);
            if ($result === null) {
                return false;
            }
            $matched = $matched || $result;
        }
        return $matched;
    }

    /**
     * 匹配单个片段：`*`、`*\/n`、`a`、`a-b`、`a-b/n`
     *
     * @return bool|null true 命中、false 未命中、null 片段非法
     */
    private static function matchPart(string $part, int $value, int $min, int $max): ?bool
    {
        if ($part === '') {
            return null;
        }

        $step = 1;
        if (str_contains($part, '/')) {
            [$part, $stepStr] = explode('/', $part, 2);
            if (!ctype_digit($stepStr) || (int) $stepStr < 1) {
                return null;
            }
            $step = (int) $stepStr;
        }

        if ($part === '*') {
            $start = $min;
            $end = $max;
        } elseif (str_contains($part, '-')) {
            [$a, $b] = explode('-', $part, 2);
            if (!ctype_digit($a) || !ctype_digit($b)) {
                return null;
            }
            $start = (int) $a;
            $end = (int) $b;
            if ($start > $end || $start < $min || $end > $max) {
                return null;
            }
        } elseif (ctype_digit($part)) {
            $start = (int) $part;
            if ($start < $min || $start > $max) {
                return null;
            }
            // 标准 cron 中 `a/n` 等价于 `a-max/n`；单值无步长时区间收缩为自身
            $end = $step > 1 ? $max : $start;
        } else {
            return null;
        }

        if ($value < $start || $value > $end) {
            return false;
        }
        return ($value - $start) % $step === 0;
    }
}
