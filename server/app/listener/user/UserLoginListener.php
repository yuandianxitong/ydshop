<?php
declare(strict_types=1);

namespace app\listener\user;

use app\repository\user\UserLoginLogRepository;
use app\repository\user\UserRepository;
use think\facade\Log;

/**
 * 用户登录监听器
 *
 * 事件数据：
 * - user_id: int  用户ID
 * - ip:      string|null  登录IP
 *
 * 职责：
 * 1. 写 user_login_logs（事件流，用于留存计算）
 * 2. 更新 users.last_login_time / last_login_ip / login_count（去规范化缓存，DAU/MAU 快查）
 */
class UserLoginListener
{
    public function __construct(
        protected UserLoginLogRepository $userLoginLogRepository,
        protected UserRepository $userRepository,
    ) {}

    public function handle(array $event): void
    {
        $userId = (int)($event['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $ip  = (string)($event['ip'] ?? '');
        $now = date('Y-m-d H:i:s');

        try {
            $this->userLoginLogRepository->create([
                'user_id'  => $userId,
                'login_at' => $now,
                'login_ip' => $ip ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('UserLoginListener: write log failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }

        try {
            $this->userRepository->recordLogin($userId, $ip);
        } catch (\Throwable $e) {
            Log::warning('UserLoginListener: update user failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
