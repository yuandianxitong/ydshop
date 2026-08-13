<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsAttributeValidate extends Validate
{
    protected $rule = [
        'group_id' => 'integer',
        'name' => 'max:50',
        'type' => 'max:20',
        'sort' => 'integer|>=:0',
    ];

    protected $message = [

    ];

    protected $scene = [
        'create' => ['group_id', 'name', 'type', 'sort'],
        'update' => ['group_id', 'name', 'type', 'sort'],
    ];
}