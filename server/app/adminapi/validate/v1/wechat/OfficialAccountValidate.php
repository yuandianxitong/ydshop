<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\wechat;

use core\base\Validate;

class OfficialAccountValidate extends Validate
{
    protected $rule = [
        'button'     => 'require|array|min:1',
        'code'       => 'require|max:64',
        'receivers'  => 'require',
        'data'       => 'array',
        'openid'     => 'require|max:128',
        'next_openid'=> 'max:128',
    ];

    protected $message = [
        'button.require'    => '菜单内容必填',
        'button.min'        => '至少配置 1 个一级菜单',
        'code.require'      => '模板编码必填',
        'receivers.require' => '接收者必填',
        'openid.require'    => 'openid 必填',
    ];

    protected $scene = [
        'create_menu'   => ['button'],
        'send_template' => ['code', 'receivers', 'data'],
        'user_info'     => ['openid'],
    ];
}
