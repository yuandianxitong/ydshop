<?php
declare(strict_types=1);

namespace core\marketplace;

use core\utils\Encryption;

/**
 * Encrypted official-site user session (JWT) for Shop marketplace download.
 */
class OfficialAccountSession
{
    public static function filePath(): string
    {
        return rtrim((string) runtime_path(), DIRECTORY_SEPARATOR) . '/marketplace/site_session.json';
    }

    /**
     * @param array{id?: int|string, nickname?: string, mobile?: string} $user
     */
    public static function save(string $token, array $user): void
    {
        $dir = dirname(self::filePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::filePath(), json_encode([
            'token'        => Encryption::encrypt($token),
            'account'      => (string) ($user['mobile'] ?? $user['nickname'] ?? ''),
            'nickname'     => (string) ($user['nickname'] ?? ''),
            'user_id'      => (int) ($user['id'] ?? 0),
            'connected_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public static function token(): ?string
    {
        $row = self::read();
        if ($row === null) {
            return null;
        }
        $cipher = (string) ($row['token'] ?? '');
        if ($cipher === '') {
            return null;
        }
        $plain = Encryption::decrypt($cipher);
        return is_string($plain) && $plain !== '' ? $plain : null;
    }

    /** @return array{connected: bool, account: string, nickname: string, connected_at: string}|null */
    public static function publicInfo(): ?array
    {
        $row = self::read();
        if ($row === null) {
            return null;
        }
        return [
            'connected'    => true,
            'account'      => (string) ($row['account'] ?? ''),
            'nickname'     => (string) ($row['nickname'] ?? ''),
            'connected_at' => (string) ($row['connected_at'] ?? ''),
        ];
    }

    public static function clear(): void
    {
        if (is_file(self::filePath())) {
            @unlink(self::filePath());
        }
    }

    /** @return array<string, mixed>|null */
    private static function read(): ?array
    {
        $path = self::filePath();
        if (!is_file($path)) {
            return null;
        }
        $raw = json_decode((string) file_get_contents($path), true);
        return is_array($raw) ? $raw : null;
    }
}
