<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\delivery;

use core\base\Validate;

class DeliveryOrderValidate extends Validate
{
    protected $rule = [
        'staff_id'  => 'require|integer|gt:0',
        'status'    => 'require|in:picking,delivering,completed,cancelled',
        'order_ids' => 'require|array|min:1',
        'platform'  => 'require|in:dada,fengniao,uupt,shansong,sfsc',
    ];

    protected $message = [
        'staff_id.require'  => '请选择配送员',
        'status.in'         => '无效的状态',
        'order_ids.require' => 'order_ids 参数无效',
        'platform.require'  => '请选择配送平台',
        'platform.in'       => '无效的配送平台',
    ];

    protected $scene = [
        'assign'        => ['staff_id'],
        'update_status' => ['status'],
        'auto_dispatch' => ['order_ids'],
        'dispatch'      => ['platform'],
    ];
}
