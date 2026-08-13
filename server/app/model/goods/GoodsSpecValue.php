<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsSpecValue extends Model
{
    protected $table = 'goods_spec_values';
    protected $deleteTime = false;

    protected $fillable = ['spec_name_id', 'value', 'sort'];

    public function specName(): BelongsTo
    {
        return $this->belongsTo(GoodsSpecName::class, 'spec_name_id');
    }
}
