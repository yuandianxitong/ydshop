<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsAttributeValueRepository;
use app\repository\goods\GoodsCategoryRepository;
use app\repository\goods\GoodsComboItemRepository;
use app\repository\goods\GoodsSkuRepository;
use app\repository\goods\GoodsSpecNameRepository;
use app\repository\goods\GoodsSpecValueRepository;
use app\repository\goods\GoodsSpuRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsSpuService extends Service
{
    protected GoodsSpuRepository $goodsSpuRepo;
    protected GoodsSkuRepository $goodsSkuRepo;
    protected GoodsCategoryRepository $goodsCategoryRepo;
    protected GoodsSpecNameRepository $goodsSpecNameRepo;
    protected GoodsSpecValueRepository $goodsSpecValueRepo;
    protected GoodsComboItemRepository $goodsComboItemRepo;
    protected GoodsAttributeValueRepository $goodsAttributeValueRepo;

    /**
     * 创建商品（SPU + Specs + SKUs + Attributes）
     */
    public function create(array $data): array
    {
        return $this->runInTransaction(function () use ($data) {
            $spuData = $this->extractSpuData($data);
            $spuData['spu_no'] = $this->goodsSpuRepo->generateSpuNo();
            $spuData['status'] = 'draft';
            $spu = $this->goodsSpuRepo->create($spuData);
            $spuId = (int)$spu['id'];

            if (in_array($data['type'], ['physical', 'virtual'])) {
                $this->saveSpecsAndSkus($spuId, $data['specs'] ?? [], $data['skus'] ?? []);
            }

            if ($data['type'] === 'combo') {
                $this->saveComboItems($spuId, $data['combo_items'] ?? []);
            }

            $this->saveAttributeValues($spuId, $data['attrs'] ?? $data['attributes'] ?? []);

            $this->goodsSpuRepo->refreshPriceAndStock($spuId);

            return $this->goodsSpuRepo->getDetail($spuId);
        });
    }

    /**
     * 更新商品
     */
    public function update(int $id, array $data): array
    {
        $spu = $this->goodsSpuRepo->find($id);
        if (!$spu) {
            throw new BusinessException('商品不存在');
        }

        return $this->runInTransaction(function () use ($id, $data, $spu) {
            $spuData = $this->extractSpuData($data);
            $this->goodsSpuRepo->update($id, $spuData);

            $type = $data['type'] ?? $spu['type'];
            if (in_array($type, ['physical', 'virtual'])) {
                $this->clearSpecs($id);
                $this->clearSkus($id);
                $this->saveSpecsAndSkus($id, $data['specs'] ?? [], $data['skus'] ?? []);
            }

            if ($type === 'combo') {
                $this->goodsComboItemRepo->deleteByComboSpuId($id);
                $this->saveComboItems($id, $data['combo_items'] ?? []);
            }

            $this->goodsAttributeValueRepo->deleteBySpuId($id);
            $this->saveAttributeValues($id, $data['attrs'] ?? $data['attributes'] ?? []);

            $this->goodsSpuRepo->refreshPriceAndStock($id);

            return $this->goodsSpuRepo->getDetail($id);
        });
    }

    /**
     * 商品列表
     */
    public function getList(array $params): array
    {
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        $categoryIds = [];
        if (!empty($params['category_id'])) {
            $categoryId = (int)$params['category_id'];
            $categoryIds = array_merge([$categoryId], $this->goodsCategoryRepo->getDescendantIds($categoryId));
        }

        return $this->goodsSpuRepo->getPageList($params, $page, $limit, $categoryIds);
    }

    /**
     * 商品详情
     */
    public function getDetail(int $id): array
    {
        $detail = $this->goodsSpuRepo->getDetail($id);
        if (!$detail) {
            throw new BusinessException('商品不存在');
        }
        return $detail;
    }

    /**
     * C 端公开商品列表（仅 on_sale + 多维度过滤 + 排序）
     */
    public function getPublicList(array $params): array
    {
        $page  = (int)($params['page_no'] ?? $params['page'] ?? 1);
        $limit = (int)($params['page_size'] ?? $params['limit'] ?? 20);

        // 分类筛选：聚合"父分类 + 全部启用子分类"
        $categoryIds = [];
        if (!empty($params['category_id'])) {
            $categoryId  = (int)$params['category_id'];
            $childIds    = $this->goodsCategoryRepo->getDescendantIds($categoryId, true);
            $categoryIds = array_merge([$categoryId], $childIds);
        }

        return $this->goodsSpuRepo->getPublicPageList($params, $categoryIds, $page, $limit);
    }

    /**
     * C 端商品详情：on_sale + off_sale 都返回；on_sale 时浏览量 +1
     */
    public function getPublicDetail(int $id): array
    {
        $detail = $this->goodsSpuRepo->findPublicDetail($id);
        if (!$detail) {
            throw new BusinessException('商品不存在');
        }
        if (($detail['status'] ?? '') === 'on_sale') {
            $this->goodsSpuRepo->incViewCount($id);
        }
        $promo = \core\plugin\HookManager::apply('goods.detail_promo', [
            'spu_id' => $id,
            'detail' => $detail,
        ], []);
        if (is_array($promo) && $promo !== []) {
            $detail['promo'] = $promo;
        }
        return $detail;
    }

    /**
     * 上下架
     */
    public function updateStatus(int $id, string $status): bool
    {
        $spu = $this->goodsSpuRepo->find($id);
        if (!$spu) {
            throw new BusinessException('商品不存在');
        }
        if (!in_array($status, ['on_sale', 'off_sale'])) {
            throw new BusinessException('无效的状态');
        }
        if ($status === 'on_sale') {
            $skuCount = $this->goodsSkuRepo->countActiveBySpuId($id);
            if ($skuCount === 0) {
                throw new BusinessException('商品至少需要一个有效SKU才能上架');
            }
        }
        return $this->goodsSpuRepo->update($id, ['status' => $status]);
    }

    /**
     * 删除商品（软删除 SPU + SKU）
     */
    public function delete(int $id): bool
    {
        $spu = $this->goodsSpuRepo->find($id);
        if (!$spu) {
            throw new BusinessException('商品不存在');
        }
        if ($spu['status'] === 'on_sale') {
            throw new BusinessException('在售商品不能删除，请先下架');
        }

        return $this->runInTransaction(function () use ($id) {
            $this->goodsSkuRepo->deleteBySpuId($id);
            if (!$this->goodsSpuRepo->delete($id)) {
                throw new BusinessException('删除失败');
            }
            return true;
        });
    }

    /**
     * 批量上架
     */
    public function batchOnSale(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->updateStatus((int)$id, 'on_sale');
                $count++;
            } catch (\Exception) {
                // skip 单条失败，不阻塞批量
            }
        }
        return $count;
    }

    /**
     * 批量更新配送方式（enable_pickup / disable_pickup）
     */
    public function batchDelivery(array $ids, string $action): void
    {
        $this->goodsSpuRepo->batchTogglePickupMode($ids, $action);
    }

    /**
     * 批量下架
     */
    public function batchOffSale(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->updateStatus((int)$id, 'off_sale');
                $count++;
            } catch (\Exception) {
                // skip 单条失败，不阻塞批量
            }
        }
        return $count;
    }

    /**
     * SKU 选择器水合：按 IDs 取轻量字段，顺序与入参一致
     */
    public function getSkusByIds(array $ids): array
    {
        $rows = $this->goodsSkuRepo->findByIds($ids);
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = $r;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[(int)$id])) {
                $ordered[] = $byId[(int)$id];
            }
        }
        return $ordered;
    }

    /**
     * 选择器水合：按 IDs 取轻量字段，返回顺序与入参一致
     */
    public function getByIds(array $ids): array
    {
        $rows = $this->goodsSpuRepo->findByIds($ids);
        $rows = array_map(function (array $r) {
            $images = $r['images'] ?? [];
            $r['thumb'] = is_array($images) && !empty($images) ? (string)$images[0] : '';
            unset($r['images']);
            return $r;
        }, $rows);
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = $r;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[(int)$id])) {
                $ordered[] = $byId[(int)$id];
            }
        }
        return $ordered;
    }

    // ========== Private Helpers ==========

    private function saveSpecsAndSkus(int $spuId, array $specs, array $skus): void
    {
        $valueIdMap = [];

        foreach ($specs as $sortIndex => $spec) {
            $specName = $this->goodsSpecNameRepo->create([
                'spu_id' => $spuId,
                'name'   => $spec['name'],
                'sort'   => $sortIndex,
            ]);

            foreach ($spec['values'] as $valSort => $value) {
                $specValue = $this->goodsSpecValueRepo->create([
                    'spec_name_id' => (int)$specName['id'],
                    'value'        => $value,
                    'sort'         => $valSort,
                ]);
                $valueIdMap[$spec['name'] . ':' . $value] = (int)$specValue['id'];
            }
        }

        foreach ($skus as $skuData) {
            $specValueIds = [];
            $specTexts = [];

            // spec_values 是 { specName: value } 形式的关联数组
            foreach (($skuData['spec_values'] ?? []) as $specName => $value) {
                $key = $specName . ':' . $value;
                if (isset($valueIdMap[$key])) {
                    $specValueIds[] = $valueIdMap[$key];
                    $specTexts[] = $value;
                }
            }

            $this->goodsSkuRepo->create([
                'spu_id'         => $spuId,
                'sku_no'         => $this->goodsSkuRepo->generateSkuNo(),
                'spec_value_ids' => $specValueIds,
                'spec_text'      => implode('/', $specTexts),
                'price'          => $skuData['price'] ?? 0,
                'cost_price'     => $skuData['cost_price'] ?? 0,
                'market_price'   => $skuData['market_price'] ?? 0,
                'stock'          => $skuData['stock'] ?? 0,
                'image'          => $skuData['image'] ?? '',
                'weight'         => $skuData['weight'] ?? 0,
                'volume'         => $skuData['volume'] ?? 0,
                'status'         => 1,
            ]);
        }
    }

    private function saveComboItems(int $spuId, array $comboItems): void
    {
        foreach ($comboItems as $sort => $item) {
            $this->goodsComboItemRepo->create([
                'combo_spu_id' => $spuId,
                'item_sku_id'  => $item['sku_id'],
                'quantity'     => $item['quantity'] ?? 1,
                'sort'         => $sort,
            ]);
        }
    }

    private function saveAttributeValues(int $spuId, array $attributes): void
    {
        foreach ($attributes as $attr) {
            // 兼容前端字段名 attr_id 和旧版 attribute_id
            $attributeId = $attr['attr_id'] ?? $attr['attribute_id'] ?? 0;
            if ($attributeId && isset($attr['value']) && $attr['value'] !== '') {
                $this->goodsAttributeValueRepo->create([
                    'spu_id'       => $spuId,
                    'attribute_id' => (int)$attributeId,
                    'value'        => is_array($attr['value']) ? implode(',', $attr['value']) : (string)$attr['value'],
                ]);
            }
        }
    }

    private function extractSpuData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name', 'subtitle', 'category_id', 'brand_id', 'unit_id', 'type',
            'images', 'video', 'description', 'detail', 'status', 'sort',
            'is_recommend', 'is_new', 'is_hot', 'freight_template_id',
            'delivery_modes',
        ]));
    }

    private function clearSpecs(int $spuId): void
    {
        $specNameIds = $this->goodsSpecNameRepo->getIdsBySpuId($spuId);
        if ($specNameIds) {
            $this->goodsSpecValueRepo->deleteBySpecNameIds($specNameIds);
            $this->goodsSpecNameRepo->deleteBySpuId($spuId);
        }
    }

    private function clearSkus(int $spuId): void
    {
        // 物理删除（绕过软删除），避免编辑时 SKU 不断累积
        $this->goodsSkuRepo->forceDeleteBySpuId($spuId);
    }
}
