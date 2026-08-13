<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsSku;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsSkuRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsSku();
    }

    /**
     * 生成 SKU 编号
     */
    public function generateSkuNo(): string
    {
        // uniqid 基于微秒时间戳，加随机后缀避免同一微秒碰撞
        return 'SKU' . date('Ymd') . substr(uniqid('', true), -8);
    }

    /**
     * 按 SPU 获取所有 SKU
     */
    public function getBySpuId(int $spuId): array
    {
        return GoodsSku::where('spu_id', $spuId)->order('id asc')->select()->toArray();
    }

    /**
     * 删除 SPU 下所有 SKU（软删除）
     */
    public function deleteBySpuId(int $spuId): int
    {
        return GoodsSku::where('spu_id', $spuId)->useSoftDelete('deleted_at', date('Y-m-d H:i:s'))->delete();
    }

    /**
     * 按 IDs 批量取轻量字段（选择器 by-ids 端点用）
     * 返回：[{ id, spu_id, sku_no, spec_value_ids, spec_text, image, price }]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->model
            ->whereIn('id', $ids)
            ->field('id, spu_id, sku_no, spec_value_ids, spec_text, image, price, weight, volume')
            ->select()
            ->toArray();
    }

    /**
     * 按 IDs 批量加载 SKU + SPU 关联（替代 N+1 的 GoodsSku::with(['spu'])->find(skuId) 循环）
     *
     * @return array<int, array<string, mixed>> key=sku_id, value=带 spu 的完整 sku 数组
     */
    public function findWithSpuByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $rows = $this->model->with(['spu'])->whereIn('id', $ids)->select()->toArray();
        $out  = [];
        foreach ($rows as $row) {
            $out[(int)$row['id']] = $row;
        }
        return $out;
    }

    /**
     * 按 id 查启用中的 SKU（status=1）；下单时验 SKU 是否上架
     */
    public function findActiveById(int $skuId): ?array
    {
        $sku = $this->model->where('id', $skuId)->where('status', 1)->find();
        return $sku ? $sku->toArray() : null;
    }

    /**
     * 原子扣减库存 + 增加销量（条件：stock >= quantity）
     *
     * @return bool true=扣减成功；false=库存不足
     */
    public function deductStock(int $skuId, int $quantity): bool
    {
        return $this->model->where('id', $skuId)
            ->where('stock', '>=', $quantity)
            ->dec('stock', $quantity)
            ->inc('sales_count', $quantity)
            ->update() > 0;
    }

    /**
     * 取消订单时恢复库存（stock +qty，sales_count -qty）
     */
    public function restoreStock(int $skuId, int $quantity): void
    {
        $this->model->where('id', $skuId)
            ->inc('stock', $quantity)
            ->dec('sales_count', $quantity)
            ->update();
    }

    /**
     * 启用中的 SKU 数（上架前置校验：至少有 1 个有效 SKU）
     */
    public function countActiveBySpuId(int $spuId): int
    {
        return $this->model->where('spu_id', $spuId)->where('status', 1)->count();
    }

    /**
     * 物理删除某 SPU 下全部 SKU（绕过软删除）—— 编辑商品时清理用
     *
     * 注意：Query::delete(true) 在已挂 soft_delete 选项时仍会走软删 UPDATE 分支，
     * 且条件表达式 ['null',''] 无法作为 SET 值，实际等于空操作，导致每次保存 SKU 累积。
     * 正确做法是先 removeOption('soft_delete') 再 delete()。
     */
    public function forceDeleteBySpuId(int $spuId): int
    {
        return $this->model
            ->where('spu_id', $spuId)
            ->removeOption('soft_delete')
            ->delete();
    }

    /**
     * 所有在售 SKU（with spu 关联），AI 补货推荐使用
     */
    public function getAllActiveWithSpu(): array
    {
        return $this->model->with('spu')->where('status', 1)->select()->toArray();
    }
}
