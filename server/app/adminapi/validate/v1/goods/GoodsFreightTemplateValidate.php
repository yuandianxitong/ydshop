<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsFreightTemplateValidate extends Validate
{
    protected $rule = [
        'name' => 'max:50',
        'is_free' => 'integer',
        'sort' => 'integer|>=:0',
    ];

    protected $message = [

    ];

    protected $scene = [
        'create' => ['name', 'is_free', 'sort'],
        'update' => ['name', 'is_free', 'sort'],
    ];
}