<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsSpu;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsSpuRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsSpu();
    }

    /**
     * SPU 分页列表（带筛选）
     */
    public function getPageList(array $params = [], int $page = 1, int $limit = 15, array $categoryIds = []): array
    {
        $query = GoodsSpu::whereNull('deleted_at')->order('sort desc, id desc');

        if (!empty($params['keyword'])) {
            $query->where('name|spu_no', 'like', "%{$params['keyword']}%");
        }
        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        } elseif (!empty($params['category_id'])) {
            $query->where('category_id', (int)$params['category_id']);
        }
        if (!empty($params['brand_id'])) {
            $query->where('brand_id', (int)$params['brand_id']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }
        if (isset($params['type']) && $params['type'] !== '') {
            $query->where('type', $params['type']);
        }

        $total = $query->count();
        $list = $query->with(['category.parent', 'brand'])
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'list' => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $limit),
            ],
        ];
    }

    /**
     * 软删除 SPU。
     */
    public function delete($id): bool
    {
        try {
            return GoodsSpu::where('id', $id)
                ->whereNull('deleted_at')
                ->useSoftDelete('deleted_at', date('Y-m-d H:i:s'))
                ->delete() > 0;
        } catch (\Exception $e) {
            throw new \core\exception\BusinessException(lang('messages.delete_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * SPU 详情（带所有关联数据）
     */
    public function getDetail(int $id): ?array
    {
        $spu = GoodsSpu::with([
            'category', 'brand', 'unit',
            'skus', 'specNames.values', 'attributeValues.attribute',
            'comboItems.itemSku',
        ])->find($id);

        if (!$spu) {
            return null;
        }

        $data = $spu->toArray();

        // 将 specNames 转换为前端期望的 specs 格式: [{name, values: [string]}]
        $specs = [];
        $specValueIdToName = [];
        // toArray() 使用关联方法名作为 key（驼峰 specNames），兼容两种形式
        $specNamesData = $data['specNames'] ?? $data['spec_names'] ?? [];
        foreach ($specNamesData as $specName) {
            $values = array_map(fn($v) => $v['value'], $specName['values'] ?? []);
            $specs[] = [
                'name'   => $specName['name'],
                'values' => $values,
            ];
            // 建立 spec_value_id → "specName:value" 的映射，供 SKU 反查
            foreach ($specName['values'] ?? [] as $v) {
                $specValueIdToName[(int)$v['id']] = [
                    'spec_name' => $specName['name'],
                    'value'     => $v['value'],
                ];
            }
        }
        $data['specs'] = $specs;
        unset($data['specNames'], $data['spec_names']);

        // 将 SKU 的 spec_value_ids 转换为 spec_values 关联数组 { specName: value }
        if (!empty($data['skus'])) {
            foreach ($data['skus'] as &$sku) {
                $specValues = [];
                $ids = $sku['spec_value_ids'] ?? [];
                if (is_string($ids)) {
                    $ids = json_decode($ids, true) ?: [];
                }
                foreach ($ids as $vid) {
                    if (isset($specValueIdToName[(int)$vid])) {
                        $info = $specValueIdToName[(int)$vid];
                        $specValues[$info['spec_name']] = $info['value'];
                    }
                }
                $sku['spec_values'] = $specValues;
            }
            unset($sku);
        }

        // 将 attributeValues 转换为前端期望的 attrs 格式: [{attr_id, value}]
        $attrs = [];
        $attrValuesData = $data['attributeValues'] ?? $data['attribute_values'] ?? [];
        foreach ($attrValuesData as $av) {
            $attrs[] = [
                'attr_id' => (int)$av['attribute_id'],
                'value'   => $av['value'],
            ];
        }
        $data['attrs'] = $attrs;
        unset($data['attributeValues'], $data['attribute_values']);

        return $data;
    }

    /**
     * 生成 SPU 编号
     */
    public function generateSpuNo(): string
    {
        return 'SPU' . date('Ymd') . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 按 ID 列表获取简要商品信息（DIY 页面组件用）
     */
    public function getByIds(array $ids, int $limit = 20): array
    {
        if (empty($ids)) return [];
        return $this->model->whereIn('id', $ids)
            ->where('status', 'on_sale')
            ->where('deleted_at', null)
            ->field('id, name, images, min_price, max_price, sales_count, is_new, is_hot, is_recommend')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 按分类获取简要商品列表（DIY 页面组件用）
     */
    public function getListByCategory(int $categoryId, int $limit = 8): array
    {
        return $this->model->where('category_id', $categoryId)
            ->where('status', 'on_sale')
            ->where('deleted_at', null)
            ->field('id, name, images, min_price, max_price, sales_count, is_new, is_hot, is_recommend')
            ->order('sort asc, id desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 按 IDs 批量取轻量字段（选择器 by-ids 端点用）
     * 返回：[{ id, name, images, min_price, status }]，未排序、未过滤删除（Model 自动 deleted_at 过滤）
     * 注意：不按 status 过滤（草稿/下架也返回），供选择器回填已保存的 ID 用。
     *      与 getByIds()（仅返回 on_sale）用途不同，请勿混用。
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->model
            ->whereIn('id', $ids)
            ->field('id, name, images, min_price, status, type, freight_template_id')
            ->select()
            ->toArray();
    }

    /**
     * 按 ID 列表查找 SPU 行（含 delivery_modes）
     */
    public function findRowsByIds(array $ids): \think\Collection
    {
        return GoodsSpu::whereIn('id', $ids)->select();
    }

    /**
     * 统计在指定 SPU 集合中、且分类落在 categoryIds 范围内的数量
     * （优惠券 use_scope='category' 的命中判定用）
     */
    public function countByIdsAndCategoryIds(array $spuIds, array $categoryIds): int
    {
        if (empty($spuIds) || empty($categoryIds)) {
            return 0;
        }
        return $this->model
            ->whereIn('id', $spuIds)
            ->whereIn('category_id', $categoryIds)
            ->count();
    }

    /**
     * C 端公开商品列表（status=on_sale，支持 category_id / keyword / brand_id /
     * is_recommend / is_new / is_hot / sort 过滤）
     *
     * @param array $categoryIds 当按分类筛选时由 Service 传入"分类 + 子分类" id 集合
     */
    public function getPublicPageList(array $params, array $categoryIds, int $page, int $limit): array
    {
        $query = $this->model->where('status', 'on_sale');

        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }
        if (!empty($params['keyword'])) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }
        if (!empty($params['brand_id'])) {
            $query->where('brand_id', (int)$params['brand_id']);
        }
        if (!empty($params['is_recommend'])) {
            $query->where('is_recommend', 1);
        }
        if (!empty($params['is_new'])) {
            $query->where('is_new', 1);
        }
        if (!empty($params['is_hot'])) {
            $query->where('is_hot', 1);
        }

        $total = $query->count();
        $sort = (string)($params['sort'] ?? 'default');
        match ($sort) {
            'price_asc' => $query->order('min_price', 'asc')->order('id', 'desc'),
            'price_desc' => $query->order('min_price', 'desc')->order('id', 'desc'),
            'sales', 'sales_desc' => $query->order('sales_count', 'desc')->order('id', 'desc'),
            'newest', 'new' => $query->order('id', 'desc'),
            default => $query->order('sort', 'desc')->order('id', 'desc'),
        };

        $list  = $query->field('id,name,subtitle,images,min_price,max_price,sales_count,type')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * C 端公开详情：on_sale + off_sale 都返回（off_sale 用于订单/购物车回看，
     * 由前端按 status 字段决定能否购买），draft 不返回
     */
    public function findPublicDetail(int $spuId): ?array
    {
        $spu = $this->model->with(['brand', 'skus' => function ($q) {
            $q->where('status', 1);
        }, 'specNames.values', 'attributeValues.attribute'])
            ->whereIn('status', ['on_sale', 'off_sale'])
            ->find($spuId);
        return $spu ? $spu->toArray() : null;
    }

    /**
     * 浏览量 +1（仅在售商品累加；调用方守卫 status）
     */
    public function incViewCount(int $spuId): void
    {
        $this->model->where('id', $spuId)->inc('view_count')->update();
    }

    /**
     * 重算 SPU 的 min_price / max_price / total_stock（基于其启用中的 SKU 聚合）
     *
     * 替代 Model.refreshPriceAndStock —— Service 只用 Repo。
     */
    public function refreshPriceAndStock(int $spuId): void
    {
        $skuModel = new \app\model\goods\GoodsSku();
        $skus     = $skuModel->where('spu_id', $spuId)->where('status', 1)->select();

        if ($skus->isEmpty()) {
            $this->model->where('id', $spuId)->update([
                'min_price'   => 0,
                'max_price'   => 0,
                'total_stock' => 0,
            ]);
            return;
        }

        $prices = $skus->column('price');
        $stocks = $skus->column('stock');

        $this->model->where('id', $spuId)->update([
            'min_price'   => min($prices),
            'max_price'   => max($prices),
            'total_stock' => array_sum($stocks),
        ]);
    }

    /**
     * 批量切换 delivery_modes 中 'pickup' 项（admin 批量操作）
     *
     * action='enable_pickup'：加入 pickup（已含则不变）
     * action='disable_pickup'：移除 pickup（移除后若为空则回落到 ['express']）
     */
    public function batchTogglePickupMode(array $ids, string $action): void
    {
        if (empty($ids)) {
            return;
        }
        $rows = $this->model->whereIn('id', $ids)
            ->field('id, delivery_modes')
            ->select();
        foreach ($rows as $row) {
            $modes = is_array($row->delivery_modes) ? $row->delivery_modes : ['express'];
            if ($action === 'enable_pickup' && !in_array('pickup', $modes, true)) {
                $modes[] = 'pickup';
            } elseif ($action === 'disable_pickup') {
                $modes = array_values(array_filter($modes, fn($m) => $m !== 'pickup'));
                if (empty($modes)) {
                    $modes = ['express'];
                }
            }
            $this->model->where('id', $row->id)->update(['delivery_modes' => $modes]);
        }
    }

    /**
     * 按 id 查在售商品（status='on_sale'）；下单时验 SPU 是否可买
     */
    public function findOnSaleById(int $spuId): ?array
    {
        $spu = $this->model->where('id', $spuId)->where('status', 'on_sale')->find();
        return $spu ? $spu->toArray() : null;
    }

    /**
     * 销量 +qty（下单写入时）
     */
    public function incSalesCount(int $spuId, int $quantity): void
    {
        $this->model->where('id', $spuId)->inc('sales_count', $quantity)->update();
    }

    /**
     * 销量 -qty（取消订单时回退）
     */
    public function decSalesCount(int $spuId, int $quantity): void
    {
        $this->model->where('id', $spuId)->dec('sales_count', $quantity)->update();
    }

    /**
     * 按标签查询商品（用于装修组件）
     */
    public function getListByTag(string $tag, int $limit = 8): array
    {
        $query = $this->model->where('status', 'on_sale')
            ->where('deleted_at', null)
            ->field('id, name, images, min_price, max_price, sales_count, is_new, is_hot, is_recommend');

        if ($tag === 'new') {
            $query->where('is_new', 1);
        } elseif ($tag === 'hot') {
            $query->where('is_hot', 1);
        } elseif ($tag === 'recommend') {
            $query->where('is_recommend', 1);
        }

        return $query->order('sort asc, id desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 已分配计量单位的有效 SPU 数（单位概览 KPI 用）
     */
    public function countWithUnitAssigned(): int
    {
        return $this->model
            ->whereNull('deleted_at')
            ->where('unit_id', '<>', 0)
            ->count();
    }

    /**
     * 滞销商品列表（按 sales_count 升序）
     *
     * @return array<int, array{id:int, name:string, sales_count:int}>
     */
    public function getBottomBySales(int $limit = 10): array
    {
        return $this->model->where('status', 'on_sale')
            ->field('id, name, sales_count')
            ->order('sales_count', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * AI 描述生成用：SPU + category + brand + specNames(.values) + attributeValues(.attribute)
     */
    public function findDetailWithSpecsAttributes(int $spuId): ?array
    {
        $spu = $this->model->with([
            'category',
            'brand',
            'specNames.values',
            'attributeValues.attribute',
        ])->find($spuId);
        return $spu ? $spu->toArray() : null;
    }
}
