<?php
declare(strict_types=1);

namespace plugins\new_user_gift\service;

use app\repository\marketing\MarketingCouponRepository;
use plugins\new_user_gift\repository\NewUserGiftRepository;
use core\base\Service;

class NewUserGiftService extends Service
{
    protected NewUserGiftRepository $giftRepository;
    protected MarketingCouponRepository $marketingCouponRepository;

    private const CONDITION_KEYS = ['new_register', 'no_order_7d', 'invited', 'profile_complete'];

    /**
     * 后台分页列表
     */
    public function getList(array $filters, int $page, int $limit): array
    {
        return $this->giftRepository->getPageList($filters, $page, $limit);
    }

    /**
     * Listener 用：当前活跃礼包
     */
    public function getActiveGifts(): array
    {
        return $this->giftRepository->findActive();
    }

    public function create(array $data): array
    {
        $this->validate($data);
        $clean = $this->normalize($data);
        return $this->giftRepository->create($clean);
    }

    public function update(int $id, array $data): array
    {
        $gift = $this->giftRepository->findById($id);
        if (!$gift) {
            $this->throwBusinessException('礼包不存在');
        }
        $this->validate($data, $id);
        $clean = $this->normalize($data);
        $this->giftRepository->updateById($id, $clean);
        return $this->giftRepository->findById($id)?->toArray() ?? [];
    }

    public function delete(int $id): void
    {
        $gift = $this->giftRepository->findById($id);
        if (!$gift) {
            $this->throwBusinessException('礼包不存在');
        }
        $this->giftRepository->deleteById($id);
    }

    /**
     * 后台读全局规则
     */
    public function getRules(): array
    {
        return [
            'new_user_gift.rules.limit_count' => 1,
            'new_user_gift.rules.scenes' => ['register_success'],
            'new_user_gift.rules.delivery_mode' => 'immediate',
            'new_user_gift.rules.risk_controls' => ['account_once'],
        ];
    }

    public function updateRules(array $data): void
    {
        $this->throwBusinessException('新人礼包当前固定为注册后立即发放，每个账号一次');
    }

    /**
     * 校验：name / conditions / coupon_ids / valid period / status+rewards
     */
    private function validate(array $data, ?int $excludeId = null): void
    {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            $this->throwBusinessException('礼包名称不能为空');
        }
        if ($this->giftRepository->existsByName($name, $excludeId)) {
            $this->throwBusinessException('同名礼包已存在');
        }

        $conditions = (array)($data['conditions'] ?? []);
        if (empty($conditions)) {
            $this->throwBusinessException('至少选择 1 个受众标签');
        }
        foreach ($conditions as $c) {
            if (!in_array($c, self::CONDITION_KEYS, true)) {
                $this->throwBusinessException('受众标签包含非法值');
            }
        }

        $couponIds = array_filter(array_map('intval', (array)($data['coupon_ids'] ?? [])));
        if (!empty($couponIds)) {
            $existCount = $this->marketingCouponRepository->countExistingByIds($couponIds);
            if ($existCount !== count($couponIds)) {
                $this->throwBusinessException('选中的优惠券不存在或已删除');
            }
        }

        $start = $data['valid_start'] ?? null;
        $end   = $data['valid_end'] ?? null;
        if ($start && $end && strtotime($start) > strtotime($end)) {
            $this->throwBusinessException('有效期开始时间不能晚于结束时间');
        }

        $status  = (int)($data['status'] ?? 0);
        $points  = (int)($data['points'] ?? 0);
        $balance = (float)($data['balance'] ?? 0);
        if ($status === 1 && $points <= 0 && $balance <= 0 && empty($couponIds)) {
            $this->throwBusinessException('启用礼包至少要配置一项奖励');
        }
    }

    private function normalize(array $data): array
    {
        return [
            'name'        => trim((string)($data['name'] ?? '')),
            'description' => (string)($data['description'] ?? ''),
            'status'      => (int)($data['status'] ?? 0),
            'sort_order'  => (int)($data['sort_order'] ?? 0),
            'conditions'  => array_values((array)($data['conditions'] ?? [])),
            'points'      => max(0, (int)($data['points'] ?? 0)),
            'balance'     => max(0, (float)($data['balance'] ?? 0)),
            'coupon_ids'  => array_values(array_filter(array_map('intval', (array)($data['coupon_ids'] ?? [])))),
            'valid_start' => !empty($data['valid_start']) ? (string)$data['valid_start'] : null,
            'valid_end'   => !empty($data['valid_end']) ? (string)$data['valid_end'] : null,
        ];
    }

}
