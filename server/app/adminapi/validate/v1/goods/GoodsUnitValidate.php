<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsUnitValidate extends Validate
{
    protected $rule = [
        'name' => 'max:20',
        'status' => 'integer|in:0,1',
    ];

    protected $message = [

    ];

    protected $scene = [
        'create' => ['name', 'status'],
        'update' => ['name', 'status'],
    ];
}