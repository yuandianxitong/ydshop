<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\order;

use core\base\Validate;

class OrderShipValidate extends Validate
{
    protected $rule = [
        'order_id'             => 'require|integer|>:0',
        'delivery_mode'        => 'require|in:express,none',
        'ship_mode'            => 'in:manual,waybill',
        'express_company'      => 'max:50',
        'express_no'           => 'max:50',
        'waybill_template_id'  => 'integer|>=:0',
    ];

    protected $message = [
        'order_id.require'         => '订单ID不能为空',
        'order_id.integer'         => '订单ID必须为整数',
        'order_id.>'               => '订单ID必须大于0',
        'delivery_mode.require'    => '请选择配送方式',
        'delivery_mode.in'         => '配送方式无效',
        'ship_mode.in'             => '发货方式无效',
        'express_company.max'      => '快递公司名称不能超过50个字符',
        'express_no.max'           => '快递单号不能超过50个字符',
    ];
}
