<?php
declare(strict_types=1);

namespace core\license;

/**
 * 本地授权状态持久化（runtime 文件，不进业务库）
 */
class LicenseState
{
    public static function path(): string
    {
        $rel = (string) config('license.state_file', 'license/state.json');
        $dir = runtime_path() . dirname($rel);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return runtime_path() . $rel;
    }

    public static function load(): array
    {
        $file = self::path();
        if (!is_file($file)) {
            return [];
        }
        $raw = file_get_contents($file);
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : [];
    }

    public static function save(array $data): void
    {
        $file = self::path();
        file_put_contents(
            $file,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    public static function clear(): void
    {
        $file = self::path();
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
