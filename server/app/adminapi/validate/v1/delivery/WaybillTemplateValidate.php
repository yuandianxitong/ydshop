<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\delivery;

use think\Validate;

class WaybillTemplateValidate extends Validate
{
    protected $rule = [
        'name' => 'require|max:100',
        'express_code' => 'require|max:50',
        'express_name' => 'max:50',
        'exp_type' => 'require|max:20',
        'exp_type_name' => 'max:50',
        'template_size' => 'max:20',
        'template_size_label' => 'max:50',
        'pay_type' => 'integer|between:1,3',
        'need_pickup' => 'in:0,1',
        'is_default' => 'in:0,1',
        'status' => 'in:0,1',
        'sort' => 'integer',
    ];

    protected $message = [
        'name.require' => '模版名称不能为空',
        'express_code.require' => '物流公司不能为空',
        'exp_type.require' => '业务类型不能为空',
        'pay_type.between' => '邮费支付方式无效',
    ];

    protected $scene = [
        'create' => [
            'name', 'express_code', 'express_name', 'exp_type', 'exp_type_name',
            'template_size', 'template_size_label', 'pay_type', 'need_pickup',
            'is_default', 'status', 'sort',
        ],
        'update' => [
            'name', 'express_code', 'express_name', 'exp_type', 'exp_type_name',
            'template_size', 'template_size_label', 'pay_type', 'need_pickup',
            'is_default', 'status', 'sort',
        ],
        'status' => ['status'],
    ];
}
