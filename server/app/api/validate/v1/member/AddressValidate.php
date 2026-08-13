<?php
declare(strict_types=1);

namespace app\api\validate\v1\member;

use think\Validate;

class AddressValidate extends Validate
{
    protected $rule = [
        'name'       => 'require|max:32',
        'phone'      => 'require|mobile',
        'province'   => 'require|max:32',
        'city'       => 'require|max:32',
        'district'   => 'max:32',
        'detail'     => 'require|max:255',
        'lng'        => 'float',
        'lat'        => 'float',
        'is_default' => 'in:0,1',
    ];

    protected $message = [
        'name.require'     => '收货人必填',
        'phone.require'    => '手机号必填',
        'phone.mobile'     => '手机号格式不正确',
        'province.require' => '省份必填',
        'city.require'     => '城市必填',
        'detail.require'   => '详细地址必填',
    ];

    protected $scene = [
        'store'  => ['name', 'phone', 'province', 'city', 'district', 'detail', 'lng', 'lat', 'is_default'],
        'update' => ['name', 'phone', 'province', 'city', 'district', 'detail', 'lng', 'lat', 'is_default'],
    ];
}
