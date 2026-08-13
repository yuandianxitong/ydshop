<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

class GoodsSpu extends Model
{
    protected $table = 'goods_spu';

    protected $fillable = [
        'spu_no', 'name', 'subtitle', 'category_id', 'brand_id', 'unit_id',
        'type', 'images', 'video', 'description', 'detail',
        'min_price', 'max_price', 'total_stock', 'sales_count', 'view_count',
        'status', 'sort', 'is_recommend', 'is_new', 'is_hot', 'freight_template_id',
        'delivery_modes',
    ];

    protected $type = [
        'images'         => 'json',
        'delivery_modes' => 'json',
        'min_price'    => 'float',
        'max_price'    => 'float',
        'total_stock'  => 'integer',
        'sales_count'  => 'integer',
        'view_count'   => 'integer',
        'sort'         => 'integer',
        'is_recommend' => 'integer',
        'is_new'       => 'integer',
        'is_hot'       => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoodsCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(GoodsBrand::class, 'brand_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(GoodsUnit::class, 'unit_id');
    }

    public function freightTemplate(): BelongsTo
    {
        return $this->belongsTo(GoodsFreightTemplate::class, 'freight_template_id');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(GoodsSku::class, 'spu_id');
    }

    public function specNames(): HasMany
    {
        return $this->hasMany(GoodsSpecName::class, 'spu_id')->order('sort asc');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(GoodsAttributeValue::class, 'spu_id');
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(GoodsComboItem::class, 'combo_spu_id');
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = ['draft' => '草稿', 'on_sale' => '在售', 'off_sale' => '已下架'];
        return $map[$data['status']] ?? '未知';
    }

    public function getTypeTextAttr($value, $data): string
    {
        unset($value);
        $map = ['physical' => '实物', 'virtual' => '虚拟', 'combo' => '组合'];
        return $map[$data['type']] ?? '未知';
    }

    /**
     * 根据 SKU 列表刷新价格和库存冗余字段
     */
    public function refreshPriceAndStock(): void
    {
        $skus = $this->skus()->where('status', 1)->select();
        if ($skus->isEmpty()) {
            $this->save(['min_price' => 0, 'max_price' => 0, 'total_stock' => 0]);
            return;
        }
        $prices = $skus->column('price');
        $stocks = $skus->column('stock');
        $this->save([
            'min_price'   => min($prices),
            'max_price'   => max($prices),
            'total_stock' => array_sum($stocks),
        ]);
    }
}
