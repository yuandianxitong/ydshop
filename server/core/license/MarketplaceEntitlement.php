<?php
declare(strict_types=1);

namespace core\license;

/**
 * 官方市场组件权益缓存（runtime/marketplace/entitlements.json）。
 * 文件不存在视为尚未连接市场，付费插件不强制拦截。
 */
class MarketplaceEntitlement
{
    public static function filePath(): string
    {
        return rtrim((string) runtime_path(), DIRECTORY_SEPARATOR) . '/marketplace/entitlements.json';
    }

    /** @return array<string, array<string, mixed>>|null */
    public static function all(): ?array
    {
        $path = self::filePath();
        if (!is_file($path)) {
            return null;
        }
        $raw = json_decode((string) file_get_contents($path), true);
        return is_array($raw) ? $raw : [];
    }

    /** @param array<string, array<string, mixed>> $rows */
    public static function save(array $rows): void
    {
        $dir = dirname(self::filePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::filePath(), json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
