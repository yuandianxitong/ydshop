<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1;

use core\base\Validate;

class StoreValidate extends Validate
{
    protected $rule = [
        'name'           => 'require|max:100',
        'code'           => 'require|max:32|alphaDash',
        'address'        => 'require|max:255',
        'lng'            => 'require|float|between:-180,180',
        'lat'            => 'require|float|between:-90,90',
        'phone'          => 'max:20',
        'business_hours' => 'array',
        'status'         => 'in:0,1',
        'sort'           => 'integer',
    ];

    protected $message = [
        'name.require'    => '请填写门店名',
        'code.require'    => '请填写门店编码',
        'address.require' => '请填写地址',
        'lng.require'     => '请选择门店经度',
        'lat.require'     => '请选择门店纬度',
    ];

    protected $scene = [
        'create' => ['name', 'code', 'address', 'lng', 'lat', 'phone', 'business_hours', 'status', 'sort'],
        'update' => ['name', 'address', 'lng', 'lat', 'phone', 'business_hours', 'status', 'sort'],
    ];
}
