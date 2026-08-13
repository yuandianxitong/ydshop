<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class MemberLevelValidate extends Validate
{
    protected $rule = [
        'name'       => 'require|max:50',
        'level'      => 'integer|>=:0',
        'growth_min' => 'integer|>=:0',
        'icon'       => 'max:255',
        'sort'       => 'integer|>=:0',
        'status'     => 'integer|in:0,1',
    ];

    protected $message = [
        'name.require' => '等级名称不能为空',
        'name.max'     => '等级名称不能超过50个字符',
    ];

    protected $scene = [
        'create' => ['name', 'level', 'growth_min', 'icon', 'sort', 'status'],
        'update' => ['name', 'level', 'growth_min', 'icon', 'sort', 'status'],
    ];
}
