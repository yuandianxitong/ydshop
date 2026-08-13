<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class UserTagValidate extends Validate
{
    protected $rule = [
        'name'        => 'require|max:30',
        'description' => 'max:255',
        'color'       => 'max:20',
        'group_type'  => 'in:consume,behavior,lifecycle,social',
        'auto_update' => 'in:0,1',
        'sort'        => 'integer|>=:0',
        'status'      => 'in:0,1',
    ];

    protected $message = [
        'name.require'        => '请输入标签名称',
        'group_type.in'       => '分组类型不在允许范围内',
    ];

    protected $scene = [
        'create' => ['name', 'description', 'color', 'group_type', 'auto_update', 'sort', 'status'],
        'update' => ['name', 'description', 'color', 'group_type', 'auto_update', 'sort', 'status'],
    ];
}
