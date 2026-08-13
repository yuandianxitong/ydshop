<?php
declare(strict_types=1);

namespace app\api\validate\v1\auth;

use think\Validate;

class AuthValidate extends Validate
{
    protected $rule = [
        'account'               => 'require|max:64',
        'mobile'                => 'require|mobile',
        'password'              => 'require|length:6,32',
        'password_confirmation' => 'require|confirm:password',
        'code'                  => 'require|length:4,8',
        'temp_token'            => 'require|max:128',
        'phone_code'            => 'require|max:200',
        'nickname'              => 'max:64',
        'avatar'                => 'max:512',
    ];

    protected $message = [
        'account.require'                  => '账号必填',
        'mobile.require'                   => '手机号必填',
        'mobile.mobile'                    => '手机号格式不正确',
        'password.require'                 => '密码必填',
        'password.length'                  => '密码长度需 6~32 位',
        'password_confirmation.require'    => '请再次输入密码',
        'password_confirmation.confirm'    => '两次输入的密码不一致',
        'code.require'                     => '验证码必填',
        'code.length'                      => '验证码格式不正确',
        'temp_token.require'               => '临时 token 必填',
        'phone_code.require'               => '请提供 getPhoneNumber 返回的 phone_code',
    ];

    protected $scene = [
        'login'            => ['account', 'password'],
        'sms_login'        => ['mobile', 'code'],
        'register'         => ['mobile', 'code', 'password', 'password_confirmation'],
        'wechat_bindphone' => ['temp_token', 'phone_code', 'nickname', 'avatar'],
    ];
}
