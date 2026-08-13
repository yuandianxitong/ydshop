<?php
declare(strict_types=1);

namespace app\api\validate\v1\order;

use think\Validate;

class OrderRefundValidate extends Validate
{
    protected $rule = [
        'order_item_id' => 'require|integer|gt:0',
        'refund_amount' => 'float|gt:0',
        'type'          => 'in:refund_only,return_refund,refund_with_goods',
        'reason'        => 'max:120',
        'description'   => 'max:500',
        'images'        => 'array',
        'company'       => 'require|max:60',
        'express_no'    => 'require|max:60',
    ];

    protected $message = [
        'order_item_id.require' => '商品行 ID 必填',
        'order_item_id.gt'      => '商品行不合法',
        'refund_amount.gt'      => '退款金额必须大于 0',
        'type.in'               => '退款类型仅支持 refund_only / return_refund',
        'company.require'       => '快递公司必填',
        'express_no.require'    => '快递单号必填',
    ];

    protected $scene = [
        'apply'            => ['order_item_id', 'refund_amount', 'type', 'reason', 'description', 'images'],
        'submit_logistics' => ['company', 'express_no'],
    ];
}
