<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsFreightTemplateFreeRuleRepository;
use app\repository\goods\GoodsFreightTemplateRepository;
use app\repository\goods\GoodsFreightTemplateRuleRepository;
use app\repository\region\RegionRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsFreightTemplateService extends Service
{
    protected GoodsFreightTemplateRepository $goodsFreightTemplateRepo;
    protected GoodsFreightTemplateRuleRepository $goodsFreightTemplateRuleRepo;
    protected GoodsFreightTemplateFreeRuleRepository $goodsFreightTemplateFreeRuleRepo;
    protected RegionRepository $regionRepository;

    /**
     * 根据订单商品与收货区域计算快递运费。
     *
     * @param array<int,array{sku:array,spu:array,quantity:int,total:string|float}> $items
     */
    public function calculateOrderFreight(array $items, array $address): string
    {
        $groups = [];
        foreach ($items as $item) {
            $spu = (array)($item['spu'] ?? []);
            if (($spu['type'] ?? 'physical') !== 'physical') {
                continue;
            }
            $templateId = (int)($spu['freight_template_id'] ?? 0);
            if ($templateId <= 0) {
                continue;
            }
            $groups[$templateId][] = $item;
        }
        if (!$groups) {
            return '0.00';
        }

        $provinceId = $this->regionRepository->resolveProvinceId(
            (string)($address['region_code'] ?? ''),
            (string)($address['province'] ?? '')
        );
        if ($provinceId <= 0) {
            throw new BusinessException('收货地址地区信息不完整，请重新选择省市区');
        }

        $totalFreight = 0.0;
        foreach ($groups as $templateId => $groupItems) {
            $template = $this->getDetail((int)$templateId);
            if ((int)($template['is_free'] ?? 0) === 1) {
                continue;
            }
            if (in_array($provinceId, array_map('intval', (array)($template['no_delivery_region_ids'] ?? [])), true)) {
                throw new BusinessException('当前收货地区暂不支持配送');
            }

            $quantity = 0;
            $amount   = 0.0;
            $unit     = 0.0;
            foreach ($groupItems as $item) {
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $sku = (array)($item['sku'] ?? []);
                $quantity += $qty;
                $amount   += (float)($item['total'] ?? 0);
                $unit += match ((string)($template['charge_type'] ?? 'piece')) {
                    'weight' => (float)($sku['weight'] ?? 0) * $qty,
                    'volume' => (float)($sku['volume'] ?? 0) * $qty,
                    default  => $qty,
                };
            }

            if ($this->matchesFreeRule((array)($template['free_rules'] ?? []), $provinceId, $quantity, $amount)) {
                continue;
            }

            $rule = $this->findRegionRule((array)($template['rules'] ?? []), $provinceId);
            if (!$rule) {
                throw new BusinessException('当前收货地区未配置配送运费');
            }
            $firstUnit     = max(0.0, (float)($rule['first_unit'] ?? 0));
            $firstPrice    = max(0.0, (float)($rule['first_price'] ?? 0));
            $continueUnit  = max(0.0, (float)($rule['continue_unit'] ?? 0));
            $continuePrice = max(0.0, (float)($rule['continue_price'] ?? 0));
            $fee = $firstPrice;
            if ($unit > $firstUnit && $continueUnit > 0) {
                $fee += ceil(($unit - $firstUnit) / $continueUnit) * $continuePrice;
            }
            $totalFreight += $fee;
        }

        return number_format($totalFreight, 2, '.', '');
    }

    private function findRegionRule(array $rules, int $provinceId): ?array
    {
        foreach ($rules as $rule) {
            if (in_array($provinceId, array_map('intval', (array)($rule['region_ids'] ?? [])), true)) {
                return $rule;
            }
        }
        return null;
    }

    private function matchesFreeRule(array $rules, int $provinceId, int $quantity, float $amount): bool
    {
        foreach ($rules as $rule) {
            if (!in_array($provinceId, array_map('intval', (array)($rule['region_ids'] ?? [])), true)) {
                continue;
            }
            $freeNum    = (int)($rule['free_num'] ?? 0);
            $freeAmount = (float)($rule['free_amount'] ?? 0);
            if (($freeNum <= 0 || $quantity >= $freeNum) && ($freeAmount <= 0 || $amount >= $freeAmount)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取列表
     */
    public function getList(array $params): array
    {
        $where = [];

        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }

        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        return $this->goodsFreightTemplateRepo->getPageList($where, $page, $limit);
    }

    /**
     * 获取详情
     */
    public function getDetail(int $id): array
    {
        $template = $this->goodsFreightTemplateRepo->find($id);
        if (!$template) {
            throw new BusinessException('运费模板不存在');
        }
        $template['rules']      = $this->goodsFreightTemplateRuleRepo->findByTemplateId($id);
        $template['free_rules'] = $this->goodsFreightTemplateFreeRuleRepo->findByTemplateId($id);
        return $template;
    }

    /**
     * 创建
     */
    public function create(array $data): array
    {
        return $this->runInTransaction(function () use ($data) {
            $rules     = $data['rules'] ?? [];
            $freeRules = $data['free_rules'] ?? [];
            unset($data['rules'], $data['free_rules']);

            $template = $this->goodsFreightTemplateRepo->create([
                'name'                   => $data['name'] ?? '',
                'charge_type'            => $data['charge_type'] ?? 'piece',
                'is_free'                => $data['is_free'] ?? 0,
                'sort'                   => $data['sort'] ?? 0,
                'no_delivery_region_ids' => $data['no_delivery_region_ids'] ?? [],
            ]);

            $templateId = (int)$template['id'];
            foreach ($rules as $rule) {
                $rule['template_id'] = $templateId;
                $this->goodsFreightTemplateRuleRepo->create($rule);
            }
            foreach ($freeRules as $freeRule) {
                $freeRule['template_id'] = $templateId;
                $this->goodsFreightTemplateFreeRuleRepo->create($freeRule);
            }

            return $template;
        });
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        return $this->runInTransaction(function () use ($id, $data) {
            $rules     = $data['rules'] ?? [];
            $freeRules = $data['free_rules'] ?? [];
            unset($data['rules'], $data['free_rules']);

            if (!$this->goodsFreightTemplateRepo->find($id)) {
                throw new BusinessException(lang('business.record_not_found'));
            }

            $updateData = array_filter([
                'name'                   => $data['name'] ?? null,
                'charge_type'            => $data['charge_type'] ?? null,
                'is_free'                => $data['is_free'] ?? null,
                'sort'                   => $data['sort'] ?? null,
                'no_delivery_region_ids' => $data['no_delivery_region_ids'] ?? null,
            ], fn($v) => $v !== null);

            $this->goodsFreightTemplateRepo->update($id, $updateData);

            // 删旧明细 + 重新插入
            $this->goodsFreightTemplateRuleRepo->deleteByTemplateId($id);
            $this->goodsFreightTemplateFreeRuleRepo->deleteByTemplateId($id);

            foreach ($rules as $rule) {
                $rule['template_id'] = $id;
                $this->goodsFreightTemplateRuleRepo->create($rule);
            }
            foreach ($freeRules as $freeRule) {
                $freeRule['template_id'] = $id;
                $this->goodsFreightTemplateFreeRuleRepo->create($freeRule);
            }

            return true;
        });
    }

    /**
     * 删除
     */
    public function delete(int $id): bool
    {
        if (!$this->goodsFreightTemplateRepo->find($id)) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        $this->goodsFreightTemplateRuleRepo->deleteByTemplateId($id);
        $this->goodsFreightTemplateFreeRuleRepo->deleteByTemplateId($id);
        return $this->goodsFreightTemplateRepo->delete($id);
    }
}
