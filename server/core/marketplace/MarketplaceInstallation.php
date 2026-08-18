<?php
declare(strict_types=1);

namespace core\marketplace;

/**
 * 稳定的官网市场实例 UUID。断开账号后仍复用，避免 Site 侧产生重复实例。
 */
class MarketplaceInstallation
{
    public static function filePath(): string
    {
        return rtrim((string) runtime_path(), DIRECTORY_SEPARATOR) . '/marketplace/installation.json';
    }

    public static function uuid(): string
    {
        $row = self::read();
        $uuid = is_array($row) ? (string) ($row['uuid'] ?? '') : '';
        if (self::isUuid($uuid)) {
            return $uuid;
        }
        $uuid = self::uuidV4();
        $dir = dirname(self::filePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::filePath(), json_encode([
            'uuid'       => $uuid,
            'created_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $uuid;
    }

    private static function read(): ?array
    {
        $path = self::filePath();
        if (!is_file($path)) {
            return null;
        }
        $raw = json_decode((string) file_get_contents($path), true);
        return is_array($raw) ? $raw : null;
    }

    private static function isUuid(string $uuid): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    private static function uuidV4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
