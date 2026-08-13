<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\message;

use core\base\Validate;

class MessageTemplateValidate extends Validate
{
    protected $rule = [
        'code'      => 'require|alphaDash|max:64',
        'name'      => 'require|max:120',
        'content'   => 'max:2000',
        'status'    => 'in:0,1',
        'receivers' => 'require|array|min:1',
        'data'      => 'array',
    ];

    protected $message = [
        'code.require'      => '模板编码必填',
        'code.alphaDash'    => '模板编码只能包含字母、数字、下划线和短横线',
        'name.require'      => '模板名称必填',
        'receivers.require' => '请填写接收者',
        'receivers.min'     => '请至少填写 1 个接收者',
    ];

    protected $scene = [
        'store'     => ['code', 'name', 'content', 'status'],
        'update'    => ['name', 'content', 'status'],
        'test_send' => ['code', 'receivers', 'data'],
    ];
}
