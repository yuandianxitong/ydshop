<?php
declare(strict_types=1);

namespace app\api\validate\v1\feedback;

use think\Validate;

class FeedbackValidate extends Validate
{
    protected $rule = [
        'type'     => 'in:suggestion,bug,complaint,other',
        'content'  => 'require|max:1000',
        'images'   => 'array|max:3',
        'images.*' => 'max:500',
        'contact'  => 'max:100',
    ];

    protected $message = [
        'type.in'        => '反馈类型不正确',
        'content.require' => '反馈内容不能为空',
        'content.max'    => '反馈内容不能超过 1000 字',
        'images.array'   => '反馈图片格式不正确',
        'images.max'     => '反馈图片最多上传 3 张',
        'images.*.max'   => '反馈图片地址过长',
        'contact.max'    => '联系方式不能超过 100 字',
    ];

    protected $scene = [
        'submit' => ['type', 'content', 'images', 'images.*', 'contact'],
    ];
}
