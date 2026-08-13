<?php
declare(strict_types=1);

namespace app\api\validate\v1\message;

use think\Validate;

class MessageReadValidate extends Validate
{
    protected $rule = [
        'ids'   => 'array|max:100',
        'ids.*' => 'integer|gt:0',
    ];

    protected $message = [
        'ids.array'     => '消息 ID 列表格式不正确',
        'ids.max'       => '单次最多标记 100 条消息',
        'ids.*.integer' => '消息 ID 必须为整数',
        'ids.*.gt'      => '消息 ID 必须大于 0',
    ];

    protected $scene = [
        'read' => ['ids', 'ids.*'],
    ];
}
