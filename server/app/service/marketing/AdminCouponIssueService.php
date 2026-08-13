<?php
declare(strict_types=1);

namespace app\service\marketing;

use app\repository\marketing\MarketingCouponRepository;
use app\repository\marketing\MarketingCouponUserRepository;
use app\repository\user\UserRepository;
use app\service\user\UserOperationLogService;
use core\base\Service;
use core\exception\BusinessException;
use core\plugin\PluginManager;
use think\facade\Db;

/**
 * 后台手动发券给指定用户（会员详情页用）
 *
 * 与前台领券流程区别：不受 per_user_limit 限制，但仍校验：
 *  - 优惠券存在 + 启用
 *  - 未结束
 *  - 总量未发完（如配置 total_count）
 *
 * 优惠券插件未启用时软失败，避免会员页因缺表/缺插件 500。
 */
class AdminCouponIssueService extends Service
{
    protected MarketingCouponRepository     $couponRepository;
    protected MarketingCouponUserRepository $couponUserRepository;
    protected UserRepository                $userRepository;
    protected UserOperationLogService       $opLog;

    /**
     * 可发放的优惠券（用于发券弹窗下拉）
     */
    public function getIssuableCoupons(): array
    {
        $this->assertCouponAvailable();
        try {
            return $this->couponRepository->getIssuableList(100);
        } catch (\Throwable $e) {
            throw new BusinessException('优惠券数据不可用：' . $e->getMessage());
        }
    }

    /**
     * 发放给单个用户（默认 1 张）
     */
    public function issueToUser(int $userId, int $couponId, int $count = 1): array
    {
        $this->assertCouponAvailable();

        if ($count < 1) {
            throw new BusinessException('发放数量必须 ≥ 1');
        }

        $user = $this->userRepository->findModel($userId);
        if (!$user) {
            throw new BusinessException('用户不存在');
        }

        try {
            $coupon = $this->couponRepository->findModel($couponId);
        } catch (\Throwable $e) {
            throw new BusinessException('优惠券数据不可用：' . $e->getMessage());
        }
        if (!$coupon) {
            throw new BusinessException('优惠券不存在');
        }
        if ((int)$coupon->status !== 1) {
            throw new BusinessException('该优惠券已禁用');
        }
        if ($coupon->end_at && strtotime((string)$coupon->end_at) < time()) {
            throw new BusinessException('该优惠券已过期');
        }
        if ($coupon->total_count && ((int)$coupon->used_count + $count) > (int)$coupon->total_count) {
            throw new BusinessException('优惠券剩余库存不足');
        }

        return Db::transaction(function () use ($userId, $couponId, $count, $coupon) {
            $now = date('Y-m-d H:i:s');
            for ($i = 0; $i < $count; $i++) {
                $this->couponUserRepository->create([
                    'coupon_id'  => $couponId,
                    'user_id'    => $userId,
                    'status'     => 'unused',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $this->couponRepository->incrementUsed($couponId, $count);

            $this->opLog->recordAsset(
                $userId,
                'coupon.granted',
                '运营发券',
                sprintf('收到「%s」x %d', (string)$coupon->name, $count),
                ['coupon_id' => $couponId, 'count' => $count]
            );

            return ['issued' => $count];
        });
    }

    /**
     * 用户领取记录列表
     */
    public function getUserCoupons(int $userId, ?string $status, int $page, int $limit): array
    {
        $this->assertCouponAvailable();
        try {
            return $this->couponUserRepository->getUserCoupons($userId, $status, $page, $limit);
        } catch (\Throwable $e) {
            throw new BusinessException('优惠券数据不可用：' . $e->getMessage());
        }
    }

    private function assertCouponAvailable(): void
    {
        if (!PluginManager::isInstalled('coupon')) {
            throw new BusinessException('优惠券插件未启用');
        }
    }
}
