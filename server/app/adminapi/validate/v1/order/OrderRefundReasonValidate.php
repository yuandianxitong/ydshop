<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\order;

use core\base\Validate;

class OrderRefundReasonValidate extends Validate
{
    protected $rule = [
        'name'   => 'require|max:120',
        'sort'   => 'integer|egt:0',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'name.require' => '原因名称必填',
        'name.max'     => '原因名称不能超过120个字符',
    ];

    protected $scene = [
        'store'  => ['name', 'sort', 'status'],
        'update' => ['name', 'sort', 'status'],
    ];
}
