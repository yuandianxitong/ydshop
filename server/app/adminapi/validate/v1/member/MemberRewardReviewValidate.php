<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class MemberRewardReviewValidate extends Validate
{
    protected $rule = [
        'reason' => 'require|min:5|max:255',
        'resolution' => 'require|in:confirmed_applied,confirmed_missing',
    ];

    protected $message = [
        'reason.require' => '请填写复核依据',
        'reason.min' => '复核依据至少 5 个字符',
        'reason.max' => '复核依据不能超过 255 个字符',
        'resolution.require' => '请选择复核结论',
        'resolution.in' => '复核结论无效',
    ];

    protected $scene = [
        'resolve' => ['reason'],
        'resolveRecharge' => ['resolution', 'reason'],
    ];
}
