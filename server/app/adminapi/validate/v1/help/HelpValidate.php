<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\help;

use core\base\Validate;

class HelpValidate extends Validate
{
    protected $rule = [
        'category_id' => 'require|integer|>:0',
        'title'       => 'require|max:200',
        'summary'     => 'max:500',
        'content'     => 'require',
        'status'      => 'integer|in:0,1',
    ];

    protected $message = [
        'category_id.require' => '请选择分类',
        'category_id.>'       => '分类无效',
        'title.require'       => '标题必填',
        'title.max'           => '标题最长 200 字符',
        'summary.max'         => '摘要最长 500 字符',
        'content.require'     => '内容必填',
    ];

    protected $scene = [
        'create' => ['category_id', 'title', 'summary', 'content', 'status'],
        'update' => ['category_id', 'title', 'summary', 'content', 'status'],
    ];
}
