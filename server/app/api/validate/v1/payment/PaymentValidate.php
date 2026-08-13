<?php
declare(strict_types=1);

namespace app\api\validate\v1\payment;

use think\Validate;

class PaymentValidate extends Validate
{
    protected $rule = [
        'channel'        => 'require|in:wechat,alipay',
        'subject'        => 'max:255',
        'total_amount'   => 'float|gt:0',
        'order_no'       => 'max:64',
        'trade_type'     => 'in:jsapi,native,h5,app,page,wap',
        'return_url'     => 'url|max:500',
        'quit_url'       => 'url|max:500',
        'openid'         => 'max:128',
        'client_ip'      => 'max:64',
        'refund_amount'  => 'require|float|gt:0',
        'reason'         => 'max:120',
    ];

    protected $message = [
        'channel.require'       => '支付渠道必填',
        'channel.in'            => '不支持的支付渠道',
        'total_amount.gt'       => '支付金额需大于 0',
        'refund_amount.require' => '退款金额必填',
        'refund_amount.gt'      => '退款金额需大于 0',
    ];

    protected $scene = [
        'create' => ['channel', 'subject', 'total_amount', 'order_no', 'trade_type', 'return_url', 'quit_url', 'openid', 'client_ip'],
        'refund' => ['channel', 'order_no', 'refund_amount', 'reason'],
    ];

    /**
     * C 端支付必须绑定当前用户的商城订单，金额和标题由服务端订单数据生成。
     */
    public function sceneCreate()
    {
        return $this->only(['channel', 'order_no', 'trade_type', 'return_url', 'quit_url', 'openid', 'client_ip'])
            ->append('order_no', 'require');
    }

    /**
     * refund 场景需要 order_no
     */
    public function sceneRefund()
    {
        return $this->only(['channel', 'order_no', 'refund_amount', 'reason'])
            ->append('order_no', 'require');
    }
}
