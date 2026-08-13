<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsUnitGroupValidate extends Validate
{
    protected $rule = [
        'name'   => 'require|max:64',
        'tone'   => 'max:32',
        'sort'   => 'integer|egt:0',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'name.require' => '分组名称必填',
    ];

    protected $scene = [
        'store'  => ['name', 'tone', 'sort', 'status'],
        'update' => ['name', 'tone', 'sort', 'status'],
    ];
}
