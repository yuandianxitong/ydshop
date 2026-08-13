<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\goods\GoodsSkuRepository;
use app\repository\goods\GoodsSpuRepository;
use app\repository\member\MemberCartRepository;
use core\base\Service;
use core\exception\BusinessException;

class MemberCartService extends Service
{
    protected MemberCartRepository $cartRepository;
    protected GoodsSkuRepository $goodsSkuRepository;
    protected GoodsSpuRepository $goodsSpuRepository;

    /**
     * 获取购物车列表（含 SKU 和 SPU 信息）
     */
    public function getList(int $userId): array
    {
        $items = $this->cartRepository->findByUserWithRelations($userId);
        return array_map(fn(array $item) => $this->enrichCartItem($item), $items);
    }

    /**
     * 加入购物车：若 SKU 已在购物车中则累加数量，否则新建
     */
    public function add(int $userId, int $skuId, int $quantity): array
    {
        if ($quantity <= 0) {
            throw new BusinessException('商品数量必须大于 0');
        }

        $sku = $this->goodsSkuRepository->findActiveById($skuId);
        if (!$sku) {
            throw new BusinessException('商品规格不存在或已下架');
        }

        $spu = $this->goodsSpuRepository->findOnSaleById((int)$sku['spu_id']);
        if (!$spu) {
            throw new BusinessException('商品不存在或已下架');
        }

        $existing = $this->cartRepository->findByUserAndSku($userId, $skuId);
        if ($existing) {
            $this->cartRepository->incQuantity((int)$existing['id'], $quantity);
            return $this->cartRepository->find((int)$existing['id']) ?? $existing;
        }

        return $this->cartRepository->create([
            'user_id'  => $userId,
            'sku_id'   => $skuId,
            'quantity' => $quantity,
            'selected' => 1,
        ]);
    }

    /**
     * 更新购物车商品数量（验证归属）
     */
    public function update(int $cartId, int $userId, int $quantity): array
    {
        if ($quantity <= 0) {
            throw new BusinessException('商品数量必须大于 0');
        }

        $cartItem = $this->getOwned($cartId, $userId);
        $this->cartRepository->update($cartId, ['quantity' => $quantity]);
        return $this->cartRepository->find($cartId) ?? $cartItem;
    }

    /**
     * 删除购物车条目（验证归属）
     */
    public function remove(int $cartId, int $userId): bool
    {
        $this->getOwned($cartId, $userId);
        return $this->cartRepository->delete($cartId);
    }

    /**
     * 切换单条购物车条目的选中状态（验证归属）
     */
    public function toggleSelect(int $cartId, int $userId): array
    {
        $cartItem = $this->getOwned($cartId, $userId);
        $this->cartRepository->toggleSelected($cartId, (int)($cartItem['selected'] ?? 0));
        return $this->cartRepository->find($cartId) ?? $cartItem;
    }

    /**
     * 批量设置用户所有购物车条目的选中状态
     */
    public function selectAll(int $userId, bool $selected): bool
    {
        return $this->cartRepository->setAllSelected($userId, $selected);
    }

    /**
     * 获取已选中的购物车条目（用于结算流程）
     */
    public function getSelectedItems(int $userId): array
    {
        $items = $this->cartRepository->findSelectedByUserWithRelations($userId);
        return array_map(function (array $item) {
            $enriched = $this->enrichCartItem($item);
            // 结算页不需要 sku_status / spu_status（getList 才需要）
            unset($enriched['sku_status'], $enriched['spu_status']);
            return $enriched;
        }, $items);
    }

    /**
     * 清空用户购物车
     */
    public function clear(int $userId): bool
    {
        $this->cartRepository->deleteAllByUserId($userId);
        return true;
    }

    // ========== Private Helpers ==========

    /**
     * 购物车行字段拍平：从 sku.spu 关联拉名称 / 图片 / 价格 / 库存 / 状态 / 配送方式
     *
     * @param array<string, mixed> $item with(['sku.spu']) 注入的 cart 行
     */
    private function enrichCartItem(array $item): array
    {
        $sku = $item['sku'] ?? null;
        $spu = $sku['spu'] ?? null;

        $skuImage = is_array($sku) ? (string)($sku['image'] ?? '') : '';
        $spuImages = (is_array($spu) && !empty($spu['images']))
            ? (is_array($spu['images']) ? $spu['images'] : [])
            : [];
        $fallbackImage = !empty($spuImages) ? (string)($spuImages[0] ?? '') : '';

        $item['spu_name']       = is_array($spu) ? (string)($spu['name'] ?? '') : '';
        $item['image']          = $skuImage !== '' ? $skuImage : $fallbackImage;
        $item['spec_text']      = is_array($sku) ? (string)($sku['spec_text'] ?? '') : '';
        $item['price']          = is_array($sku) ? (float)($sku['price'] ?? 0) : 0.0;
        $item['stock']          = is_array($sku) ? (int)($sku['stock'] ?? 0) : 0;
        $item['delivery_modes'] = (is_array($spu) && !empty($spu['delivery_modes']))
            ? $spu['delivery_modes']
            : ['express'];
        $item['sku_status']     = is_array($sku) ? (int)($sku['status'] ?? 0) : 0;
        $item['spu_status']     = is_array($spu) ? (string)($spu['status'] ?? '') : '';

        return $item;
    }

    /**
     * 验证购物车条目归属并返回（已转 array）
     */
    private function getOwned(int $cartId, int $userId): array
    {
        $cartItem = $this->cartRepository->findByIdAndUser($cartId, $userId);
        if (!$cartItem) {
            throw new BusinessException('购物车条目不存在或无权操作');
        }
        return $cartItem;
    }
}
