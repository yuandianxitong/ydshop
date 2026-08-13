<?php
declare(strict_types=1);

namespace app\api\validate\v1\member;

use think\Validate;

class CartValidate extends Validate
{
    protected $rule = [
        'sku_id'   => 'require|integer|gt:0',
        'quantity' => 'require|integer|between:1,999',
        'selected' => 'boolean',
    ];

    protected $message = [
        'sku_id.require'    => 'SKU 必填',
        'sku_id.gt'         => 'SKU 不合法',
        'quantity.require'  => '数量必填',
        'quantity.between'  => '数量需在 1~999 之间',
    ];

    protected $scene = [
        'add'         => ['sku_id', 'quantity'],
        'update'      => ['quantity'],
        'select_all'  => ['selected'],
    ];
}
