<?php
declare(strict_types=1);

namespace plugins\coupon\hook;

use plugins\coupon\service\CouponService;

class CouponDiyHydrateHook
{
    public string $hook = 'diy.hydrate';
    public int $priority = 10;

    public function handle(array $context, mixed $prev): array
    {
        $comp = is_array($prev) ? $prev : [];
        if (($context['type'] ?? '') !== 'coupon-list') {
            return $comp;
        }
        $comp['props'] = is_array($comp['props'] ?? null) ? $comp['props'] : [];
        $comp['props']['coupon_list'] = $this->list((array) ($context['props'] ?? []));
        return $comp;
    }

    /** @param array<string, mixed> $props */
    private function list(array $props): array
    {
        $limit     = (int) ($props['limit'] ?? 8);
        $couponIds = (array) ($props['coupon_ids'] ?? []);
        $rows      = app(CouponService::class)->getReceivableCoupons(0);
        if ($couponIds !== []) {
            $couponIds = array_map('intval', $couponIds);
            $byId      = array_column($rows, null, 'id');
            $rows      = [];
            foreach ($couponIds as $cid) {
                if (isset($byId[$cid])) {
                    $rows[] = $byId[$cid];
                }
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'             => (int) $r['id'],
                'name'           => (string) ($r['name'] ?? ''),
                'type'           => (string) ($r['type'] ?? ''),
                'value'          => (float) ($r['value'] ?? 0),
                'min_amount'     => (float) ($r['min_amount'] ?? 0),
                'amount_text'    => $this->formatAmount($r),
                'threshold_text' => $this->formatThreshold($r),
                'start_at'       => (string) ($r['start_at'] ?? ''),
                'end_at'         => (string) ($r['end_at'] ?? ''),
            ];
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    /** @param array<string, mixed> $coupon */
    private function formatAmount(array $coupon): string
    {
        $type  = (string) ($coupon['type'] ?? '');
        $value = (float) ($coupon['value'] ?? 0);
        if ($type === 'percent') {
            $tenths = $value * 10;
            $str    = fmod($tenths, 1) === 0.0 ? (string) (int) $tenths : (string) $tenths;
            return $str . '折';
        }
        return '¥' . (fmod($value, 1) === 0.0 ? (string) (int) $value : (string) $value);
    }

    /** @param array<string, mixed> $coupon */
    private function formatThreshold(array $coupon): string
    {
        $type = (string) ($coupon['type'] ?? '');
        $min  = (float) ($coupon['min_amount'] ?? 0);
        if ($type === 'no_threshold' || $min <= 0) {
            return '无门槛';
        }
        return '满' . (fmod($min, 1) === 0.0 ? (string) (int) $min : (string) $min) . '可用';
    }
}
