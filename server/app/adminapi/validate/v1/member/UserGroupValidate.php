<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class UserGroupValidate extends Validate
{
    protected $rule = [
        'name'        => 'require|max:64',
        'description' => 'max:255',
        'conditions'  => 'array',
        'sort'        => 'integer|egt:0',
        'status'      => 'in:0,1',
        'user_ids'    => 'require|array|min:1',
    ];

    protected $message = [
        'name.require'     => '分组名称必填',
        'user_ids.require' => '请传入用户 ID 列表',
        'user_ids.min'     => '请至少选择 1 个用户',
    ];

    protected $scene = [
        'store'        => ['name', 'description', 'conditions', 'sort', 'status'],
        'update'       => ['name', 'description', 'conditions', 'sort', 'status'],
        'add_users'    => ['user_ids'],
        'remove_users' => ['user_ids'],
    ];
}
