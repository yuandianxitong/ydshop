<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\HasMany;

class GoodsUnitGroup extends Model
{
    protected $table = 'goods_unit_groups';

    protected $fillable = ['code', 'name', 'tone', 'sort', 'status'];

    protected $type = [
        'sort'   => 'integer',
        'status' => 'integer',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(GoodsUnit::class, 'group_id');
    }
}
