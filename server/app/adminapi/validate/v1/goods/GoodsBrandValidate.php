<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsBrandValidate extends Validate
{
    protected $rule = [
        'name' => 'max:100',
        'logo' => 'max:255',
        'sort' => 'integer|>=:0',
        'status' => 'integer|in:0,1',
    ];

    protected $message = [

    ];

    protected $scene = [
        'create' => ['name', 'logo', 'sort', 'status'],
        'update' => ['name', 'logo', 'sort', 'status'],
    ];
}