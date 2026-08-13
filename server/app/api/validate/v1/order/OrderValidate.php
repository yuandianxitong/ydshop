<?php
declare(strict_types=1);

namespace app\api\validate\v1\order;

use think\Validate;

class OrderValidate extends Validate
{
    protected $rule = [
        'items'           => 'require|array',
        'skus'            => 'require|array',
        'address'         => 'array',
        'coupon_user_id'  => 'integer|egt:0',
        'delivery_type'   => 'in:express,merchant,pickup',
        'pickup_store_id' => 'integer|egt:0',
        'buyer_remark'    => 'max:255',
        'remark'          => 'max:255',
        'reason'          => 'max:255',
        'lng'             => 'float',
        'lat'             => 'float',
        'region_code'     => 'max:20',
        'province'        => 'max:50',
    ];

    protected $message = [
        'items.require'        => '商品列表不能为空',
        'items.array'          => '商品列表格式错误',
        'skus.require'         => 'SKU 列表不能为空',
        'skus.array'           => 'SKU 列表格式错误',
        'address.array'        => '收货地址格式错误',
        'coupon_user_id.integer' => '优惠券 ID 必须为整数',
        'delivery_type.in'     => '配送方式仅支持 express、merchant 或 pickup',
        'pickup_store_id.integer' => '自提门店 ID 必须为整数',
    ];

    protected $scene = [
        'create'   => ['items', 'address', 'coupon_user_id', 'delivery_type', 'pickup_store_id', 'buyer_remark', 'remark'],
        'calc'     => ['skus', 'coupon_user_id', 'delivery_type', 'lng', 'lat', 'region_code', 'province'],
        'cancel'   => ['reason'],
    ];
}
