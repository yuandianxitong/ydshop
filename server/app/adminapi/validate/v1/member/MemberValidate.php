<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class MemberValidate extends Validate
{
    protected $rule = [
        'amount'        => 'require|float|between:-100000,100000',
        'points'        => 'require|integer|between:-1000000,1000000',
        'remark'        => 'max:255',
        'type'          => 'integer|egt:0',
        'source'        => 'max:120',
        'content'       => 'require|max:1000',
        'coupon_id'     => 'require|integer|gt:0',
        'count'         => 'integer|between:1,100',
        'template_code' => 'require|max:60',
        'variables'     => 'array',
        'nickname'      => 'max:64',
        'mobile'        => 'mobile',
        'email'         => 'email|max:120',
        'gender'        => 'in:0,1,2',
        'birthday'      => 'date',
        'avatar'        => 'max:500',
    ];

    protected $message = [
        'amount.require'        => '调整金额必填',
        'amount.between'        => '单次调整金额需在 -100000~100000 之间',
        'points.require'        => '积分变动数量必填',
        'points.between'        => '单次积分变动需在 -1000000~1000000 之间',
        'content.require'       => '备注内容必填',
        'coupon_id.require'     => '请选择优惠券',
        'count.between'         => '发放张数需在 1~100 之间',
        'template_code.require' => '请选择短信模板',
        'mobile.mobile'         => '手机号格式不正确',
        'email.email'           => '邮箱格式不正确',
    ];

    protected $scene = [
        'adjust_balance' => ['amount', 'remark'],
        'adjust_points'  => ['points', 'remark'],
        'add_remark'     => ['content'],
        'issue_coupon'   => ['coupon_id', 'count'],
        'send_sms'       => ['template_code', 'variables'],
        'update_profile' => ['nickname', 'mobile', 'email', 'gender', 'birthday', 'avatar'],
    ];
}
