<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\system;

use core\base\Validate;

class FileCategoryValidate extends Validate
{
    protected $rule = [
        'name'      => 'require|max:64',
        'parent_id' => 'integer|egt:0',
        'sort'      => 'integer|egt:0',
    ];

    protected $message = [
        'name.require' => '分类名称必填',
    ];

    protected $scene = [
        'store'  => ['name', 'parent_id', 'sort'],
        'update' => ['name', 'parent_id', 'sort'],
    ];
}
