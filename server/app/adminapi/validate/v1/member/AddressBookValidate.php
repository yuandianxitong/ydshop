<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\member;

use core\base\Validate;

class AddressBookValidate extends Validate
{
    protected $rule = [
        'user_id'    => 'require|integer|gt:0',
        'name'       => 'require|max:32',
        'phone'      => 'require|mobile',
        'province'   => 'require|max:32',
        'city'       => 'require|max:32',
        'district'   => 'max:32',
        'detail'     => 'require|max:255',
        'is_default' => 'in:0,1',
        'lng'        => 'float',
        'lat'        => 'float',
    ];

    protected $message = [
        'user_id.require'  => '会员 ID 必填',
        'name.require'     => '收货人必填',
        'phone.require'    => '手机号必填',
        'phone.mobile'     => '手机号格式不正确',
        'province.require' => '省份必填',
        'city.require'     => '城市必填',
        'detail.require'   => '详细地址必填',
    ];

    protected $scene = [
        'store'  => ['user_id', 'name', 'phone', 'province', 'city', 'district', 'detail', 'is_default', 'lng', 'lat'],
        'update' => ['name', 'phone', 'province', 'city', 'district', 'detail', 'is_default', 'lng', 'lat'],
    ];
}
