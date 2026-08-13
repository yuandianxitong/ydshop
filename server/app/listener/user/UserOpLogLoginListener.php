<?php
declare(strict_types=1);

namespace app\listener\user;

use app\service\user\UserOperationLogService;

/**
 * user.login 事件 → 写入 user_operation_logs（会员详情用）
 *
 * 与现有 UserLoginListener 并行运行：UserLoginListener 写 user_login_logs（留存计算），
 * 本 Listener 写聚合表 user_operation_logs（会员详情可视化）。
 */
class UserOpLogLoginListener
{
    public function handle(array $event): void
    {
        $userId = (int)($event['user_id'] ?? 0);
        if ($userId <= 0) return;

        $ip       = (string)($event['ip']       ?? '');
        $platform = (string)($event['platform'] ?? '');
        $desc     = trim(($platform ? $platform . ' · ' : '') . ($ip ? 'IP ' . $this->maskIp($ip) : ''));

        app(UserOperationLogService::class)->recordLogin(
            $userId,
            $platform ? $platform . '登录' : '账户登录',
            $desc,
            ['ip' => $ip, 'platform' => $platform]
        );
    }

    private function maskIp(string $ip): string
    {
        if (strpos($ip, '.') !== false) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[2] = '**';
                return implode('.', $parts);
            }
        }
        return $ip;
    }
}
