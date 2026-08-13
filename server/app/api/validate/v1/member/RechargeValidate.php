<?php
declare(strict_types=1);

namespace app\api\validate\v1\member;

use think\Validate;

class RechargeValidate extends Validate
{
    protected $rule = [
        'package_id' => 'integer|egt:0',
        'amount'     => 'float|elt:10000',
        'channel'    => 'require|in:alipay,wechat',
    ];

    protected $message = [
        'channel.require'    => '支付渠道必填',
        'channel.in'         => '不支持的支付渠道',
        'amount.elt'         => '单笔充值金额不能超过 10000',
    ];

    protected $scene = [
        'create' => ['package_id', 'amount', 'channel'],
    ];

    /**
     * 自定义金额（package_id=0）时 amount 必须 > 0
     */
    public function sceneCreate()
    {
        return $this->only(['package_id', 'amount', 'channel'])
            ->append('amount', 'requireIf:package_id,0|gt:0');
    }
}
