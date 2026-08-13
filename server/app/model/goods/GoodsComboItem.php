<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsComboItem extends Model
{
    protected $table = 'goods_combo_items';
    protected $deleteTime = false;

    protected $fillable = ['combo_spu_id', 'item_sku_id', 'quantity', 'sort'];

    public function comboSpu(): BelongsTo
    {
        return $this->belongsTo(GoodsSpu::class, 'combo_spu_id');
    }

    public function itemSku(): BelongsTo
    {
        return $this->belongsTo(GoodsSku::class, 'item_sku_id');
    }
}
