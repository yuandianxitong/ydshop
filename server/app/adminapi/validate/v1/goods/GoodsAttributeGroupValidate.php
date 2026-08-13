<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsAttributeGroupValidate extends Validate
{
    protected $rule = [
        'name' => 'max:50',
        'category_id' => 'integer',
        'sort' => 'integer|>=:0',
    ];

    protected $message = [

    ];

    protected $scene = [
        'create' => ['name', 'category_id', 'sort'],
        'update' => ['name', 'category_id', 'sort'],
    ];
}