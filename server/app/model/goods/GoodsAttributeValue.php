<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsAttributeValue extends Model
{
    protected $table = 'goods_attribute_values';
    protected $deleteTime = false;

    protected $fillable = ['spu_id', 'attribute_id', 'value'];

    public function spu(): BelongsTo
    {
        return $this->belongsTo(GoodsSpu::class, 'spu_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(GoodsAttribute::class, 'attribute_id');
    }
}
