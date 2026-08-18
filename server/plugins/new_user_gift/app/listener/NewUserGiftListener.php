<?php
declare(strict_types=1);

namespace plugins\new_user_gift\listener;

use app\model\user\BalanceLog;
use app\model\user\PointsLog;
use plugins\new_user_gift\repository\NewUserGiftLogRepository;
use app\repository\user\UserRepository;
use plugins\new_user_gift\service\NewUserGiftService;
use app\service\user\UserManageService;
use think\facade\Log;

/**
 * 新人礼包监听器
 *
 * 触发事件：user.register
 * 事件数据：['user_id' => int]
 *
 * 幂等：通过 users.new_user_gift_claimed_at 列保证（O(1)，不依赖日志表）
 */
class NewUserGiftListener
{
    public function __construct(
        protected UserRepository $userRepository,
        protected NewUserGiftLogRepository $newUserGiftLogRepository,
    ) {}

    public function handle(array $event): void
    {
        $userId = (int)($event['user_id'] ?? 0);
        if (!$userId) {
            return;
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            return;
        }

        // 幂等：已发放过就跳过
        if (!empty($user['new_user_gift_claimed_at'])) {
            return;
        }

        $gifts = app(NewUserGiftService::class)->getActiveGifts();
        if (empty($gifts)) {
            // 没有可发放礼包也要标记，防止后续配置变化导致补发
            $this->userRepository->markNewUserGiftClaimed($userId);
            return;
        }

        $service = app(UserManageService::class);

        foreach ($gifts as $gift) {
            $giftName  = (string)($gift['name'] ?? '新人礼包');
            $giftId    = (int)($gift['id'] ?? 0);
            $points    = (int)($gift['points'] ?? 0);
            $balance   = (float)($gift['balance'] ?? 0);
            $couponIds = (array)($gift['coupon_ids'] ?? []);
            $source    = 'new_user_gift:' . $userId . ':' . $giftId;
            $awardedPoints = 0;
            $awardedBalance = 0.0;
            $awardedCouponIds = [];

            $conditions = (array)($gift['conditions'] ?? []);
            if (!$this->matchesConditions($user, $conditions)) {
                continue;
            }

            if ($points > 0) {
                try {
                    if ($service->adjustPoints(
                        $userId,
                        $points,
                        '新人礼包-' . $giftName,
                        PointsLog::TYPE_REGISTER,
                        $source
                    )) {
                        $awardedPoints = $points;
                    }
                } catch (\Throwable $e) {
                    Log::warning('New user gift: points award failed', [
                        'user_id' => $userId,
                        'gift_id' => $gift['id'] ?? null,
                        'points'  => $points,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }

            if ($balance > 0) {
                try {
                    if ($service->adjustBalance(
                        $userId,
                        $balance,
                        '新人礼包-' . $giftName,
                        BalanceLog::TYPE_ADMIN_ADJUST,
                        $source
                    )) {
                        $awardedBalance = $balance;
                    }
                } catch (\Throwable $e) {
                    Log::warning('New user gift: balance award failed', [
                        'user_id' => $userId,
                        'gift_id' => $gift['id'] ?? null,
                        'balance' => $balance,
                        'error'   => $e->getMessage(),
                    ]);
                }
            }

            foreach ($couponIds as $couponId) {
                try {
                    app(\plugins\coupon\service\CouponService::class)->claim($userId, (int)$couponId);
                    $awardedCouponIds[] = (int)$couponId;
                } catch (\Throwable $e) {
                    Log::warning('New user gift: coupon claim failed', [
                        'user_id'   => $userId,
                        'gift_id'   => $gift['id'] ?? null,
                        'coupon_id' => $couponId,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            // 写入实际发放结果，避免后台将失败奖励统计为已发放
            try {
                $this->newUserGiftLogRepository->create([
                    'user_id'         => $userId,
                    'gift_id'         => $giftId,
                    'gift_name'       => $giftName,
                    'points_awarded'  => $awardedPoints,
                    'balance_awarded' => $awardedBalance,
                    'coupon_ids'      => $awardedCouponIds,
                ]);
            } catch (\Throwable $e) {
                Log::warning('New user gift: log write failed', [
                    'user_id' => $userId,
                    'gift_id' => $gift['id'] ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // 标记已发放（即使部分失败也写，防 register 重放）
        $this->userRepository->markNewUserGiftClaimed($userId);
    }

    /**
     * 判断当前用户是否匹配礼包的 conditions（AND 语义）
     *
     * @param array<string, mixed> $user
     * @param array<int, string>   $conditions  受众标签数组，如 ['new_register', 'invited']
     * @return bool 全部匹配返回 true；任一不匹配返回 false；空数组视为全匹配
     */
    private function matchesConditions(array $user, array $conditions): bool
    {
        foreach ($conditions as $cond) {
            $matched = match ($cond) {
                'new_register'     => true,
                'no_order_7d'      => true,
                'invited'          => (int)($user['inviter_id'] ?? 0) > 0,
                'profile_complete' => !empty($user['nickname']) && !empty($user['avatar']),
                default            => false,
            };
            if (!$matched) {
                return false;
            }
        }
        return true;
    }
}
