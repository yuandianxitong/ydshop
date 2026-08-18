<?php
declare(strict_types=1);

namespace core\plugin;

/**
 * Paid plugin → project-root relative frontend paths (admin / PC / uniapp).
 * Kept in core so public-repo pack/install works without reading gitignored plugin.json.
 */
class PluginFrontendMap
{
    /** @var list<string> */
    public const ALLOWED_PREFIXES = [
        'admin/src/',
        'pc/pages/',
        'pc/api/',
        'pc/components/',
        'uniapp/src/',
    ];

    /**
     * @return list<string>
     */
    public static function relativePaths(string $code): array
    {
        return match ($code) {
            'flash_sale' => [
                'admin/src/views/marketing/flash-sale',
                'pc/pages/marketing/flash-sale.vue',
                'uniapp/src/modules/marketing/pages/flash-sale.vue',
            ],
            'group_buy' => [
                'admin/src/views/marketing/group-buy',
                'pc/pages/marketing/group-buy.vue',
                'uniapp/src/modules/marketing/pages/group-buy.vue',
            ],
            'lottery' => [
                'admin/src/views/marketing/lottery',
                'admin/src/views/marketing/lottery-shipments',
                'uniapp/src/modules/marketing/pages/lottery.vue',
                'uniapp/src/modules/marketing/pages/lottery-detail.vue',
                'uniapp/src/modules/marketing/pages/lottery-records.vue',
                'uniapp/src/modules/marketing/pages/lottery-shipment-detail.vue',
                'uniapp/src/modules/marketing/pages/lottery-shipments.vue',
            ],
            'points_product' => [
                'admin/src/views/marketing/points-product',
                'admin/src/views/marketing/points-order',
                'pc/pages/marketing/points-mall.vue',
                'uniapp/src/modules/marketing/pages/points-mall.vue',
            ],
            'distribution' => [
                'admin/src/views/distribution',
                'pc/pages/user/distribution.vue',
                'uniapp/src/modules/distribution',
            ],
            'ai_assistant' => [
                'admin/src/views/ai',
                'admin/src/components/ai',
            ],
            default => [],
        };
    }

    public static function isAllowedRelative(string $rel): bool
    {
        $rel = str_replace('\\', '/', ltrim($rel, '/'));
        if ($rel === '' || str_contains($rel, '..')) {
            return false;
        }
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($rel, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
