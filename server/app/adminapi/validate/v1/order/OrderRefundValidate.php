<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\order;

use core\base\Validate;

class OrderRefundValidate extends Validate
{
    protected $rule = [
        'admin_remark'  => 'max:500',
        'refuse_reason' => 'require|max:500',
    ];

    protected $message = [
        'refuse_reason.require' => '驳回原因必填',
    ];

    protected $scene = [
        'approve' => ['admin_remark'],
        'reject'  => ['refuse_reason'],
    ];
}
