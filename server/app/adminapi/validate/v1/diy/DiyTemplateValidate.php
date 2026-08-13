<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\diy;

use core\base\Validate;

class DiyTemplateValidate extends Validate
{
    protected $rule = [
        'name'      => 'require|max:100',
        'platform'  => 'require|in:uniapp,pc',
        'page_type' => 'require|in:home,custom',
    ];

    protected $message = [
        'name.require'      => '模板名称不能为空',
        'name.max'          => '模板名称最长100字符',
        'platform.require'  => '平台不能为空',
        'platform.in'       => '平台值非法',
        'page_type.require' => '页面类型不能为空',
        'page_type.in'      => '页面类型非法',
    ];

    protected $scene = [
        'create' => ['name', 'platform', 'page_type'],
        'update' => ['name'],
    ];
}
