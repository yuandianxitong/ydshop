<?php
declare(strict_types=1);

namespace app\api\validate\v1\order;

use think\Validate;

class OrderInvoiceValidate extends Validate
{
    protected $rule = [
        'order_id'         => 'require|integer|gt:0',
        'type'             => 'require|in:personal,company',
        'title'            => 'require|max:120',
        'tax_no'           => 'max:40',
        'recipient_email'  => 'require|email|max:80',
        'content'          => 'max:255',
    ];

    protected $message = [
        'order_id.require'        => '订单 ID 必填',
        'type.in'                 => '抬头类型仅支持 personal / company',
        'title.require'           => '请输入发票抬头',
        'recipient_email.require' => '请输入接收邮箱',
        'recipient_email.email'   => '邮箱格式不正确',
    ];

    protected $scene = [
        'submit' => ['order_id', 'type', 'title', 'tax_no', 'recipient_email', 'content'],
    ];

    /**
     * company 抬头额外要求 tax_no
     */
    public function sceneSubmit()
    {
        return $this->only(['order_id', 'type', 'title', 'tax_no', 'recipient_email', 'content'])
            ->append('tax_no', 'requireIf:type,company');
    }
}
