<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\HasMany;
use think\model\relation\BelongsTo;

class GoodsAttributeGroup extends Model
{
    protected $table = 'goods_attribute_groups';

    protected $fillable = ['name', 'category_id', 'sort'];

    protected $type = [
        'category_id' => 'integer',
        'sort' => 'integer',
    ];

    public function attributes(): HasMany
    {
        return $this->hasMany(GoodsAttribute::class, 'group_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\app\model\goods\GoodsCategory::class, 'category_id');
    }
}