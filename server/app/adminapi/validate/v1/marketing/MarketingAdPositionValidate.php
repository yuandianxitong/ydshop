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

class MarketingAdPositionValidate extends Validate
{
    protected $rule = [
        'code'               => 'require|max:64|regex:/^[a-zA-Z0-9_]+$/',
        'name'               => 'require|max:100',
        'description'        => 'max:255',
        'recommended_width'  => 'integer|>=:0|<=:9999',
        'recommended_height' => 'integer|>=:0|<=:9999',
        'is_carousel'        => 'integer|in:0,1',
        'sort'               => 'integer',
        'status'             => 'integer|in:0,1',
    ];

    protected $message = [
        'code.require' => '位置编码必填',
        'code.max'     => '位置编码最长 64 字符',
        'code.regex'   => '位置编码只能包含字母数字下划线',
        'name.require' => '位置名称必填',
        'name.max'     => '位置名称最长 100 字符',
    ];

    protected $scene = [
        'create' => ['code', 'name', 'description', 'recommended_width', 'recommended_height', 'is_carousel', 'sort', 'status'],
        'update' => ['name', 'description', 'recommended_width', 'recommended_height', 'is_carousel', 'sort', 'status'],
    ];
}
