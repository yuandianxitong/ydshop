<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class AccountFundValidate extends Validate
{
    protected $rule = [
        'remark' => 'require|max:255',
        'payout_reference' => 'require|max:100',
        'payout_proof' => 'max:500',
    ];

    protected $message = [
        'remark.require' => '拒绝原因不能为空',
        'remark.max'     => '拒绝原因过长',
        'payout_reference.require' => '线下打款渠道流水号不能为空',
    ];

    protected $scene = [
        'reject_withdrawal' => ['remark'],
        'pay_withdrawal' => ['payout_reference', 'payout_proof'],
    ];
}
