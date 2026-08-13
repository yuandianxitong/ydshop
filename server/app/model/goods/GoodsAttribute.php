<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsAttribute extends Model
{
    protected $table = 'goods_attributes';

    protected $fillable = ['group_id', 'name', 'type', 'options', 'sort'];

    protected $type = [
        'group_id' => 'integer',
        'options' => 'json',
        'sort' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(GoodsAttributeGroup::class, 'group_id');
    }
}