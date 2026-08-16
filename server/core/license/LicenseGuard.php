<?php
declare(strict_types=1);

namespace core\license;

/**
 * 商业能力软降级守卫：授权无效时返回 false，不阻断核心交易。
 */
class LicenseGuard
{
    protected static ?LicenseClient $client = null;

    protected static function client(): LicenseClient
    {
        return self::$client ??= new LicenseClient();
    }

    public static function status(): array
    {
        return self::client()->evaluate();
    }

    public static function isProEnabled(): bool
    {
        return self::client()->isProEnabled();
    }

    /**
     * 付费商城组件 code。未连接官方市场时，已安装插件仍可用（zip 即交付物）。
     */
    public static function proPluginCodes(): array
    {
        return [
            'flash_sale',
            'group_buy',
            'lottery',
            'points_product',
            'distribution',
            'ai_assistant',
        ];
    }

    public static function canUsePlugin(string $code): bool
    {
        if (!in_array($code, self::proPluginCodes(), true)) {
            return true;
        }
        $entitlements = MarketplaceEntitlement::all();
        if ($entitlements === null) {
            return true;
        }
        $row = $entitlements[$code] ?? null;
        if (!is_array($row) && $code === 'points_product') {
            $row = $entitlements['points_order'] ?? null;
        }
        if (!is_array($row)) {
            return false;
        }
        $status = (string) ($row['status'] ?? '');
        return in_array($status, ['active', 'grace'], true);
    }
}
