<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\order;

use core\base\Validate;

class OrderValidate extends Validate
{
    protected $rule = [
        'reason'        => 'max:255',
        'seller_remark' => 'max:500',
        'pickup_code'   => 'require|length:6',
        'name'          => 'require|max:50',
        'phone'         => 'require|max:20',
        'province'      => 'require|max:50',
        'city'          => 'require|max:50',
        'district'      => 'require|max:50',
        'detail'        => 'require|max:255',
        'lng'           => 'float',
        'lat'           => 'float',
    ];

    protected $message = [
        'pickup_code.require' => '请输入自提码',
        'pickup_code.length'  => '自提码为 6 位',
        'name.require'        => '收件人不能为空',
        'phone.require'       => '手机号不能为空',
        'province.require'    => '省份不能为空',
        'city.require'        => '城市不能为空',
        'district.require'    => '区县不能为空',
        'detail.require'      => '详细地址不能为空',
    ];

    protected $scene = [
        'cancel'        => ['reason'],
        'remark'        => ['seller_remark'],
        'pickup_verify' => ['pickup_code'],
        'address'       => ['name', 'phone', 'province', 'city', 'district', 'detail', 'lng', 'lat'],
    ];
}
