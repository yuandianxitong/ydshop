<?php
declare(strict_types=1);

namespace plugins\full_discount\service;

use core\base\Service;
use core\exception\BusinessException;
use plugins\full_discount\repository\MarketingFullDiscountRepository;

/**
 * 满减活动 Service
 */
class FullDiscountService extends Service
{
    protected MarketingFullDiscountRepository $marketingFullDiscountRepository;

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * 满减活动分页列表
     */
    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->marketingFullDiscountRepository->getAdminPageList($params, $page, $limit);
    }

    /**
     * 获取活动详情
     */
    public function getDetail(int $id): array
    {
        $discount = $this->marketingFullDiscountRepository->find($id);
        if (!$discount) {
            throw new BusinessException('满减活动不存在');
        }
        return $discount;
    }

    /**
     * 创建满减活动
     */
    public function create(array $data): array
    {
        $this->validateData($data);
        return $this->marketingFullDiscountRepository->create($data);
    }

    /**
     * 更新满减活动
     */
    public function update(int $id, array $data): array
    {
        $existing = $this->marketingFullDiscountRepository->find($id);
        if (!$existing) {
            throw new BusinessException('满减活动不存在');
        }
        $this->validateData($data, $id);
        $this->marketingFullDiscountRepository->update($id, $data);
        return $this->marketingFullDiscountRepository->find($id) ?? $existing;
    }

    /**
     * 删除满减活动（软删除）
     */
    public function delete(int $id): bool
    {
        if (!$this->marketingFullDiscountRepository->find($id)) {
            throw new BusinessException('满减活动不存在');
        }
        return $this->marketingFullDiscountRepository->delete($id);
    }

    /**
     * 更新活动状态
     */
    public function updateStatus(int $id, int $status): bool
    {
        if (!$this->marketingFullDiscountRepository->find($id)) {
            throw new BusinessException('满减活动不存在');
        }
        return $this->marketingFullDiscountRepository->update($id, ['status' => $status]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 折扣计算逻辑
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * 查找当前有效的最佳满减活动
     *
     * 筛选条件：
     *   - status = 1
     *   - 当前时间在 start_at ~ end_at 之间
     *   - use_scope 匹配传入的 spuIds（all 全量匹配，category/spu 要求 scope_ids 有交集）
     *
     * 对每个命中活动，找到阶梯中 min <= amount 的最高档，
     * 最终返回优惠价值（value）最大的那一个活动。
     *
     * @param float $amount  待匹配的商品金额
     * @param array $spuIds  购物车中的 SPU ID 列表
     * @return array|null    命中的活动数组（含 matched_tier 字段），或 null
     */
    public function getMatchingDiscount(float $amount, array $spuIds): ?array
    {
        $discounts = $this->marketingFullDiscountRepository->getActiveNow();

        $best          = null;
        $bestTierValue = -1.0;

        foreach ($discounts as $discount) {
            if (!$this->matchScope($discount, $spuIds)) {
                continue;
            }

            $tier = $this->findBestTier((array)($discount['rules'] ?? []), $amount);
            if ($tier === null) {
                continue;
            }

            $tierValue = (float)$tier['value'];
            if ($tierValue > $bestTierValue) {
                $bestTierValue = $tierValue;
                $bestArr       = $discount;
                $bestArr['matched_tier'] = $tier;
                $best = $bestArr;
            }
        }

        return $best;
    }

    /**
     * 根据命中的活动和当前金额计算折扣金额（即可减去的金额）
     *
     * - reduce  → 直接返回 tier.value（固定减免金额）
     * - percent → 返回 amount * (1 - tier.value)  (tier.value 为折扣率，如 0.8 表示8折)
     * - freight → 返回 0（运费减免由上层调用方单独处理）
     *
     * @param array $discount  getMatchingDiscount() 返回的活动数组（含 matched_tier）
     * @param float $amount    当前待打折的金额
     * @return float           可减去的金额
     */
    public function calcDiscount(array $discount, float $amount): float
    {
        $tier  = $discount['matched_tier'] ?? null;
        $value = $tier !== null ? (float)$tier['value'] : 0.0;

        return match ($discount['type'] ?? 'reduce') {
            'reduce'  => $value,
            'percent' => $amount * (1.0 - $value),
            'freight' => 0.0,
            default   => 0.0,
        };
    }

    /** 当前订单是否命中包邮类型活动。 */
    public function hasMatchingFreightDiscount(float $amount, array $spuIds): bool
    {
        foreach ($this->marketingFullDiscountRepository->getActiveNow() as $discount) {
            if (($discount['type'] ?? '') !== 'freight' || !$this->matchScope($discount, $spuIds)) {
                continue;
            }
            if ($this->findBestTier((array)($discount['rules'] ?? []), $amount) !== null) {
                return true;
            }
        }
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 内部辅助方法
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * 判断活动范围是否与传入的 SPU ID 列表匹配
     */
    private function matchScope(array $discount, array $spuIds): bool
    {
        if (($discount['use_scope'] ?? '') === 'all') {
            return true;
        }

        $scopeIds = (array)($discount['scope_ids'] ?? []);
        if (empty($scopeIds)) {
            return false;
        }

        return !empty(array_intersect($spuIds, $scopeIds));
    }

    /**
     * 从阶梯规则中找到满足 min <= amount 的最高档
     */
    private function findBestTier(array $rules, float $amount): ?array
    {
        $matched    = null;
        $matchedMin = -1.0;

        foreach ($rules as $tier) {
            $min = (float)($tier['min_amount'] ?? $tier['min'] ?? 0);
            if ($amount >= $min) {
                if ($matched === null || $min > $matchedMin) {
                    $matched    = $tier;
                    $matchedMin = $min;
                }
            }
        }

        return $matched;
    }

    /**
     * 基础数据校验
     */
    private function validateData(array $data, ?int $excludeId = null): void
    {
        if (empty($data['name'])) {
            throw new BusinessException('活动名称不能为空');
        }

        if (!empty($data['rules']) && !is_array($data['rules'])) {
            throw new BusinessException('阶梯规则格式错误');
        }

        if (!empty($data['start_at']) && !empty($data['end_at'])) {
            if ($data['start_at'] >= $data['end_at']) {
                throw new BusinessException('活动结束时间必须晚于开始时间');
            }
        }
    }

    /**
     * 查询某 SPU 命中的所有当前进行中满减活动（用于详情页展示）
     */
    public function getActiveRulesForSpu(int $spuId): array
    {
        $discounts = $this->marketingFullDiscountRepository->getActiveNow();
        $matched   = [];
        foreach ($discounts as $d) {
            if (!$this->matchScope($d, [$spuId])) {
                continue;
            }
            $matched[] = $d;
        }
        return $matched;
    }
}
