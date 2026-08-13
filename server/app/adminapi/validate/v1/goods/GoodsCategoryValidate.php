<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsCategoryValidate extends Validate
{
    protected $rule = [
        'parent_id' => 'integer',
        'name' => 'max:50',
        'icon' => 'max:255',
        'sort' => 'integer|>=:0',
        'level' => 'integer',
        'path' => 'max:255',
        'status' => 'integer|in:0,1',
        'is_show' => 'integer',
    ];

    protected $message = [

    ];

    protected $scene = [
        'create' => ['parent_id', 'name', 'icon', 'sort', 'level', 'path', 'status', 'is_show'],
        'update' => ['parent_id', 'name', 'icon', 'sort', 'level', 'path', 'status', 'is_show'],
    ];
}