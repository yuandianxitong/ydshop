<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\diy;

use core\base\Validate;

class DiyPageValidate extends Validate
{
    protected $rule = [
        'page_type'  => 'require|in:home,category,custom',
        'platform'   => 'require|in:uniapp,pc',
        'title'      => 'require|max:100',
        'components' => 'array',
    ];

    protected $message = [
        'page_type.require' => '页面类型不能为空',
        'page_type.in'      => '页面类型无效',
        'platform.require'  => '平台不能为空',
        'platform.in'       => '平台无效',
        'title.require'     => '页面标题不能为空',
        'title.max'         => '页面标题最长100字符',
    ];

    protected $scene = [
        'create' => ['page_type', 'platform', 'title'],
        'update' => ['title', 'components'],
    ];
}
