<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\goods;

use core\base\Validate;

class GoodsSpuValidate extends Validate
{
    protected $rule = [
        'name'                => 'require|max:200',
        'category_id'         => 'require|integer|>:0',
        'type'                => 'require|in:physical,virtual,combo',
        'images'              => 'require|array',
        'brand_id'            => 'integer',
        'unit_id'             => 'integer',
        'freight_template_id' => 'integer',
        'specs'               => 'array',
        'specs.*.name'        => 'require|max:50',
        'specs.*.values'      => 'require|array|min:1',
        'skus'                => 'array|min:1',
        'skus.*.price'        => 'require|float|>=:0',
        'skus.*.stock'        => 'require|integer|>=:0',
    ];

    protected $message = [
        'name.require'        => '商品名称不能为空',
        'category_id.require' => '请选择商品分类',
        'type.require'        => '请选择商品类型',
        'images.require'      => '至少上传一张商品图片',
        'skus.min'            => '至少需要一个SKU',
    ];

    protected $scene = [
        'create' => ['name', 'category_id', 'type', 'images'],
        'update' => ['name', 'category_id', 'images'],
    ];
}
