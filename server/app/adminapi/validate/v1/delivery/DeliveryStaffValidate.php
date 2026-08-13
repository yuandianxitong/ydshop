<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\delivery;

use core\base\Validate;

class DeliveryStaffValidate extends Validate
{
    protected $rule = [
        'name'   => 'require|max:32',
        'phone'  => 'require|mobile',
        'status' => 'in:0,1',
        'sort'   => 'integer|egt:0',
        'remark' => 'max:255',
        'ids'    => 'require|array|min:1',
    ];

    protected $message = [
        'name.require'  => '姓名必填',
        'phone.require' => '手机号必填',
        'phone.mobile'  => '手机号格式不正确',
        'ids.require'   => '请选择要删除的记录',
        'ids.min'       => '请至少选择 1 条记录',
    ];

    protected $scene = [
        'store'        => ['name', 'phone', 'status', 'sort', 'remark'],
        'update'       => ['name', 'phone', 'status', 'sort', 'remark'],
        'status'       => ['status'],
        'batch_delete' => ['ids'],
    ];

    public function sceneStatus()
    {
        return $this->only(['status'])->append('status', 'require');
    }
}
