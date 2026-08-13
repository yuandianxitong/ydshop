<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\delivery;

use core\base\Validate;

class ExpressCompanyValidate extends Validate
{
    protected $rule = [
        'name'   => 'require|max:60',
        'code'   => 'require|alphaDash|max:32',
        'logo'   => 'max:500',
        'sort'   => 'integer|egt:0',
        'status' => 'in:0,1',
        'ids'    => 'require|array|min:1',
    ];

    protected $message = [
        'name.require'   => '公司名称必填',
        'code.require'   => '编码必填',
        'code.alphaDash' => '编码只能包含字母、数字、下划线和短横线',
        'ids.require'    => '请选择要删除的记录',
    ];

    protected $scene = [
        'store'        => ['name', 'code', 'logo', 'sort', 'status'],
        'update'       => ['name', 'code', 'logo', 'sort', 'status'],
        'status'       => ['status'],
        'batch_delete' => ['ids'],
    ];

    public function sceneStatus()
    {
        return $this->only(['status'])->append('status', 'require');
    }
}
