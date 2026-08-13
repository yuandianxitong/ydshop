<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsVirtualItem extends Model
{
    protected $table = 'goods_virtual_items';
    protected $deleteTime = false;

    protected $fillable = ['sku_id', 'content', 'order_item_id', 'status'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(GoodsSku::class, 'sku_id');
    }
}
