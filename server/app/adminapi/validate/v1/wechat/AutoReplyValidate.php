<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\wechat;

use core\base\Validate;

class AutoReplyValidate extends Validate
{
    protected $rule = [
        'type'    => 'require|max:32',
        'keyword' => 'max:120',
        'content' => 'require|max:2000',
        'status'  => 'in:0,1',
    ];

    protected $message = [
        'type.require'    => '回复类型必填',
        'content.require' => '回复内容必填',
    ];

    protected $scene = [
        'store'  => ['type', 'keyword', 'content', 'status'],
        'update' => ['type', 'keyword', 'content', 'status'],
    ];
}
