<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

class GoodsSku extends Model
{
    protected $table = 'goods_sku';

    protected $fillable = [
        'spu_id', 'sku_no', 'spec_value_ids', 'spec_text',
        'price', 'cost_price', 'market_price',
        'stock', 'sales_count', 'image', 'weight', 'volume', 'status',
    ];

    protected $type = [
        'spec_value_ids' => 'json',
        'price'          => 'float',
        'cost_price'     => 'float',
        'market_price'   => 'float',
        'stock'          => 'integer',
        'sales_count'    => 'integer',
        'weight'         => 'float',
        'volume'         => 'float',
    ];

    public function spu(): BelongsTo
    {
        return $this->belongsTo(GoodsSpu::class, 'spu_id');
    }

    public function virtualItems(): HasMany
    {
        return $this->hasMany(GoodsVirtualItem::class, 'sku_id');
    }

    /**
     * 扣减库存（原子操作）
     */
    public function deductStock(int $quantity): bool
    {
        return $this->where('id', $this->id)
            ->where('stock', '>=', $quantity)
            ->dec('stock', $quantity)
            ->inc('sales_count', $quantity)
            ->update() > 0;
    }

    /**
     * 恢复库存
     */
    public function restoreStock(int $quantity): void
    {
        $this->where('id', $this->id)
            ->inc('stock', $quantity)
            ->dec('sales_count', $quantity)
            ->update();
    }
}
