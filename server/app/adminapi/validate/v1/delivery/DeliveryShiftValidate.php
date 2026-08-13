<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\delivery;

use core\base\Validate;

class DeliveryShiftValidate extends Validate
{
    protected $rule = [
        'staff_id'   => 'require|integer|>:0',
        'weekday'    => 'require|integer|in:1,2,3,4,5,6,7',
        'start_time' => 'require',
        'end_time'   => 'require',
        'remark'     => 'max:100',
    ];

    protected $message = [
        'staff_id.require'   => '请选择配送员',
        'staff_id.integer'   => 'staff_id 必须为整数',
        'weekday.require'    => '请选择周几',
        'weekday.in'         => 'weekday 必须为 1-7',
        'start_time.require' => '请填写上班时间',
        'end_time.require'   => '请填写下班时间',
        'remark.max'         => '备注最长 100 字',
    ];

    protected $scene = [
        'create' => ['staff_id', 'weekday', 'start_time', 'end_time', 'remark'],
        'update' => ['staff_id', 'weekday', 'start_time', 'end_time', 'remark'],
    ];
}
