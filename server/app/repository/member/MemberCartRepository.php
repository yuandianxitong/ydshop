<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberCart;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberCartRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberCart();
    }

    /**
     * 用户购物车全集，with sku.spu 关联（C 端列表用）
     */
    public function findByUserWithRelations(int $userId): array
    {
        return $this->model->where('user_id', $userId)
            ->with(['sku.spu'])
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 用户购物车中"已选中"的条目（结算时用）
     */
    public function findSelectedByUserWithRelations(int $userId): array
    {
        return $this->model->where('user_id', $userId)
            ->where('selected', 1)
            ->with(['sku.spu'])
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 用户购物车中 SKU 已存在的条目（add-to-cart 时合并数量用）
     */
    public function findByUserAndSku(int $userId, int $skuId): ?array
    {
        $row = $this->model->where('user_id', $userId)
            ->where('sku_id', $skuId)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 按 id + user_id 双约束查找（防越权）
     */
    public function findByIdAndUser(int $cartId, int $userId): ?array
    {
        $row = $this->model->where('id', $cartId)
            ->where('user_id', $userId)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 累加现有条目的数量（不覆盖）
     */
    public function incQuantity(int $cartId, int $quantity): bool
    {
        return $this->model->where('id', $cartId)
            ->inc('quantity', $quantity)
            ->update() > 0;
    }

    /**
     * 批量更新某用户所有条目的 selected 字段
     */
    public function setAllSelected(int $userId, bool $selected): bool
    {
        $this->model->where('user_id', $userId)
            ->update(['selected' => $selected ? 1 : 0]);
        return true;
    }

    /**
     * 切换条目选中状态（0 ↔ 1）
     */
    public function toggleSelected(int $cartId, int $currentSelected): bool
    {
        return $this->model->where('id', $cartId)
            ->update(['selected' => $currentSelected ? 0 : 1]) > 0;
    }

    /**
     * 清空用户购物车
     */
    public function deleteAllByUserId(int $userId): int
    {
        return $this->model->where('user_id', $userId)->delete();
    }
}
