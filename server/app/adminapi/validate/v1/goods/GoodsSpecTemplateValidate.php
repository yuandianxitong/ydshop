<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsSpecTemplateValidate extends Validate
{
    protected $rule = [
        'name'        => 'require|max:60',
        'description' => 'max:255',
        'sort'        => 'integer|>=:0',
        'status'      => 'in:0,1',
    ];

    protected $message = [
        'name.require' => '请填写模板名称',
        'name.max'     => '模板名称最多 60 个字符',
    ];

    protected $scene = [
        'create' => ['name', 'description', 'sort', 'status'],
        'update' => ['name', 'description', 'sort', 'status'],
    ];
}
