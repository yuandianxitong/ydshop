<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\adminapi\validate\v1\marketing;

use core\base\Validate;

class MarketingAdValidate extends Validate
{
    protected $rule = [
        'position_id' => 'require|integer|>:0',
        'title'       => 'require|max:100',
        'image'       => 'require|max:500',
        'link'        => 'max:500',
        'sort'        => 'integer',
        'status'      => 'integer|in:0,1',
    ];

    protected $message = [
        'position_id.require' => '请选择广告位',
        'position_id.>'       => '广告位无效',
        'title.require'       => '广告名必填',
        'title.max'           => '广告名最长 100 字符',
        'image.require'       => '请上传广告图片',
        'image.max'           => '图片 URL 过长',
        'link.max'            => '跳转链接过长',
    ];

    protected $scene = [
        'create' => ['position_id', 'title', 'image', 'link', 'sort', 'status'],
        'update' => ['position_id', 'title', 'image', 'link', 'sort', 'status'],
    ];
}
