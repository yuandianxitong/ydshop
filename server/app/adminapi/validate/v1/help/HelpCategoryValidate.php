<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\help;

use core\base\Validate;

class HelpCategoryValidate extends Validate
{
    protected $rule = [
        'name'   => 'require|max:100',
        'icon'   => 'max:255',
        'sort'   => 'integer',
        'status' => 'integer|in:0,1',
    ];

    protected $message = [
        'name.require' => '分类名称必填',
        'name.max'     => '分类名称最长 100 字符',
    ];

    protected $scene = [
        'create' => ['name', 'icon', 'sort', 'status'],
        'update' => ['name', 'icon', 'sort', 'status'],
    ];
}
