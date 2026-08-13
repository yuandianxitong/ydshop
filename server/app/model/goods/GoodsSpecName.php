<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

class GoodsSpecName extends Model
{
    protected $table = 'goods_spec_names';
    protected $deleteTime = false;

    protected $fillable = ['spu_id', 'name', 'sort'];

    public function spu(): BelongsTo
    {
        return $this->belongsTo(GoodsSpu::class, 'spu_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(GoodsSpecValue::class, 'spec_name_id')->order('sort asc');
    }
}
