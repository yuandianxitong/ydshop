<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class RechargePackageValidate extends Validate
{
    protected $rule = [
        'amount'      => 'require|float|gt:0|elt:100000',
        'gift_amount' => 'float|egt:0',
        'gift_points' => 'integer|egt:0',
        'sort'        => 'integer|egt:0',
        'status'      => 'in:0,1',
    ];

    protected $message = [
        'amount.require' => '充值金额必填',
        'amount.gt'      => '充值金额需大于 0',
        'amount.elt'     => '单笔充值金额不能超过 100000',
    ];

    protected $scene = [
        'store'  => ['amount', 'gift_amount', 'gift_points', 'sort', 'status'],
        'update' => ['amount', 'gift_amount', 'gift_points', 'sort', 'status'],
    ];

    // update 场景下 amount 不要求必填（partial update）
    public function sceneUpdate()
    {
        return $this->only(['amount', 'gift_amount', 'gift_points', 'sort', 'status'])
            ->remove('amount', 'require');
    }
}
