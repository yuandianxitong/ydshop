<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;

class GoodsSpecTemplate extends Model
{
    protected $table = 'goods_spec_templates';

    protected $fillable = ['name', 'description', 'items', 'sort', 'status'];

    protected $type = [
        'items'  => 'json',
        'sort'   => 'integer',
        'status' => 'integer',
    ];
}
