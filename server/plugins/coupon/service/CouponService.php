<?php
declare(strict_types=1);

namespace plugins\coupon\service;

use app\repository\goods\GoodsSpuRepository;
use app\repository\marketing\MarketingCouponUserRepository;
use core\base\Service;
use core\exception\BusinessException;
use plugins\coupon\repository\MarketingCouponRepository;

class CouponService extends Service
{
    protected MarketingCouponRepository $couponRepository;
    protected MarketingCouponUserRepository $couponUserRepository;
    protected GoodsSpuRepository $goodsSpuRepository;

    /**
     * 获取优惠券分页列表（管理端）
     */
    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? $params['page_size'] ?? 20);
        return $this->couponRepository->getAdminPageList($params, $page, $limit);
    }

    /**
     * 创建优惠券
     */
    public function create(array $data): array
    {
        return $this->couponRepository->create($data);
    }

    /**
     * 更新优惠券
     */
    public function update(int $id, array $data): array
    {
        $existing = $this->couponRepository->find($id);
        if (!$existing) {
            throw new BusinessException('优惠券不存在');
        }

        $this->couponRepository->update($id, $data);
        return $this->couponRepository->find($id) ?? $existing;
    }

    /**
     * 删除优惠券（软删除）
     */
    public function delete(int $id): void
    {
        $existing = $this->couponRepository->find($id);
        if (!$existing) {
            throw new BusinessException('优惠券不存在');
        }

        $this->couponRepository->delete($id);
    }

    /**
     * 用户领取优惠券
     */
    public function claim(int $userId, int $couponId): array
    {
        $coupon = $this->couponRepository->find($couponId);
        if (!$coupon) {
            throw new BusinessException('优惠券不存在');
        }

        if (!$this->isCouponAvailable($coupon)) {
            throw new BusinessException('优惠券已不可领取');
        }

        // total_count = 0 / null 表示不限发放总量
        $totalCount = (int)($coupon['total_count'] ?? 0);
        if ($totalCount > 0 && (int)($coupon['used_count'] ?? 0) >= $totalCount) {
            throw new BusinessException('优惠券已领完');
        }

        // 检查用户已领取数量（per_user_limit = 0 表示每人不限领取次数）
        $perUserLimit = (int)($coupon['per_user_limit'] ?? 0);
        if ($perUserLimit > 0) {
            $userClaimedCount = $this->couponUserRepository->countByCouponAndUser($couponId, $userId);
            if ($userClaimedCount >= $perUserLimit) {
                throw new BusinessException('您已达到该优惠券的领取上限');
            }
        }

        return $this->runInTransaction(function () use ($couponId, $userId) {
            $couponUser = $this->couponUserRepository->create([
                'coupon_id'     => $couponId,
                'user_id'       => $userId,
                'status'        => 'unused',
                'used_order_id' => 0,
                'used_at'       => null,
            ]);

            $this->couponRepository->incUsedCount($couponId);

            return $couponUser;
        });
    }

    /**
     * 获取用户可用优惠券（结算时筛选）
     *
     * @param int   $userId      用户ID
     * @param float $orderAmount 订单金额
     * @param array $spuIds      购买的SPU ID列表
     */
    public function getAvailableCoupons(int $userId, float $orderAmount, array $spuIds): array
    {
        $now         = date('Y-m-d H:i:s');
        $couponUsers = $this->couponUserRepository->findUnusedByUserWithCoupon($userId);

        $available = [];
        foreach ($couponUsers as $couponUser) {
            $coupon = $couponUser['coupon'] ?? null;
            if (!$coupon) {
                continue;
            }

            // 检查有效期
            if (!empty($coupon['end_at']) && $coupon['end_at'] < $now) {
                continue;
            }
            if (!empty($coupon['start_at']) && $coupon['start_at'] > $now) {
                continue;
            }
            if ((int)($coupon['status'] ?? 0) !== 1) {
                continue;
            }

            // 检查最低金额
            if ($orderAmount < (float)($coupon['min_amount'] ?? 0)) {
                continue;
            }

            // 检查使用范围
            $useScope = (string)($coupon['use_scope'] ?? 'all');
            if ($useScope !== 'all') {
                $scopeIds = is_array($coupon['scope_ids'] ?? null) ? $coupon['scope_ids'] : [];

                if ($useScope === 'spu') {
                    // 检查购买SPU是否在范围内
                    if (empty(array_intersect($spuIds, $scopeIds))) {
                        continue;
                    }
                } elseif ($useScope === 'category') {
                    // 检查购买SPU的分类是否在范围内
                    if (!$this->spuMatchCategory($spuIds, $scopeIds)) {
                        continue;
                    }
                }
            }

            $couponUser['discount'] = $this->calcDiscount((int)$couponUser['id'], $orderAmount);
            $available[] = $couponUser;
        }

        return $available;
    }

    /**
     * 获取"可领取优惠券"列表（与结算页可用券不同：不依赖订单金额/SPU 列表）
     *
     * 过滤条件：
     *  - status = 1 (启用)
     *  - 在有效期内 (start_at <= now <= end_at)
     *  - 库存够 (used_count < total_count，total_count = null 表示不限)
     *  - 用户领取次数未达上限 (per_user_limit)
     *
     * @return array  数组，每项含 coupon 详情 + has_claimed 标记 + claimed_count
     */
    public function getReceivableCoupons(int $userId): array
    {
        return $this->couponRepository->getReceivable($userId);
    }

    /**
     * 获取用户全部优惠券
     */
    public function getUserCoupons(int $userId, array $params = []): array
    {
        [$page, $limit] = $this->extractPagination($params);
        $status = (string)($params['status'] ?? '');
        return $this->couponUserRepository->getUserCoupons($userId, $status ?: null, $page, $limit);
    }

    /**
     * 使用优惠券（下单时锁定）
     */
    public function useCoupon(int $couponUserId, int $orderId, int $userId = 0): void
    {
        $couponUser = $this->couponUserRepository->find($couponUserId);
        if (!$couponUser) {
            throw new BusinessException('优惠券记录不存在');
        }

        if (($couponUser['status'] ?? '') !== 'unused') {
            throw new BusinessException('优惠券不可用');
        }
        if ($userId > 0 && (int)($couponUser['user_id'] ?? 0) !== $userId) {
            throw new BusinessException('优惠券不属于当前用户');
        }

        $this->couponUserRepository->markUsed($couponUserId, $orderId);
    }

    /**
     * 退还优惠券（订单取消时归还）
     */
    public function returnCoupon(int $orderId): void
    {
        // 合单后存活单可能绑定多张已用券（rebindOrder 改绑），必须全部退还
        $couponUsers = $this->couponUserRepository->findAllUsedByOrderId($orderId);
        if (empty($couponUsers)) {
            return;
        }

        $ids = array_map(static fn($row) => (int)$row['id'], $couponUsers);

        $this->runInTransaction(function () use ($ids) {
            foreach ($ids as $couponUserId) {
                $this->couponUserRepository->markUnused($couponUserId);
            }
        });
    }

    /**
     * 合单时把被合并订单占用的优惠券改绑到存活订单
     *
     * 存活单后续取消/退款时，returnCoupon 按 used_order_id 反查即可正确退还。
     * 调用方（OrderMergeService）在合单主事务内调用，随主事务一起提交/回滚。
     */
    public function rebindOrder(int $fromOrderId, int $toOrderId): void
    {
        if ($fromOrderId <= 0 || $toOrderId <= 0 || $fromOrderId === $toOrderId) {
            return;
        }
        $this->couponUserRepository->rebindUsedOrder($fromOrderId, $toOrderId);
    }

    /**
     * 计算优惠券折扣金额
     *
     * @param int   $couponUserId 用户优惠券ID
     * @param float $amount       待优惠金额
     * @return float 优惠金额
     */
    public function calcDiscount(int $couponUserId, float $amount): float
    {
        $row = $this->couponUserRepository->findByIdWithCoupon($couponUserId);
        if (!$row || empty($row['coupon'])) {
            return 0.0;
        }

        $coupon      = $row['coupon'];
        $value       = (float)($coupon['value'] ?? 0);
        $maxDiscount = (float)($coupon['max_discount'] ?? 0);
        $type        = (string)($coupon['type'] ?? '');

        switch ($type) {
            case 'fixed':
                // 固定减：最多减到0
                return min($value, $amount);

            case 'percent':
                // 折扣：value为折扣率（如0.8表示8折，discount = amount * (1 - value)）
                $discount = $amount * (1 - $value);
                if ($maxDiscount > 0) {
                    $discount = min($discount, $maxDiscount);
                }
                return max(0.0, $discount);

            case 'no_threshold':
                // 无门槛：直接减value，最多减到0
                return min($value, $amount);

            default:
                return 0.0;
        }
    }

    /**
     * 下单试算专用：验证归属、状态、有效期、门槛和商品范围后返回优惠金额。
     */
    public function calcOrderDiscount(int $couponUserId, int $userId, float $amount, array $spuIds): float
    {
        foreach ($this->getAvailableCoupons($userId, $amount, $spuIds) as $row) {
            if ((int)($row['id'] ?? 0) === $couponUserId) {
                return (float)($row['discount'] ?? 0);
            }
        }
        throw new BusinessException('该优惠券不适用于当前订单');
    }

    /**
     * 判断优惠券是否在可领取/可使用窗口内（status=1 + start_at/end_at）
     *
     * @param array<string, mixed> $coupon
     */
    private function isCouponAvailable(array $coupon): bool
    {
        if ((int)($coupon['status'] ?? 0) !== 1) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if (!empty($coupon['start_at']) && $coupon['start_at'] > $now) {
            return false;
        }
        if (!empty($coupon['end_at']) && $coupon['end_at'] < $now) {
            return false;
        }
        return true;
    }

    /**
     * 检查SPU是否匹配分类范围
     */
    private function spuMatchCategory(array $spuIds, array $categoryIds): bool
    {
        return $this->goodsSpuRepository->countByIdsAndCategoryIds($spuIds, $categoryIds) > 0;
    }
}
