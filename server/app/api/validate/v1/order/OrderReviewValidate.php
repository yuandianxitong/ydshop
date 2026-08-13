<?php
declare(strict_types=1);

namespace app\api\validate\v1\order;

use think\Validate;

class OrderReviewValidate extends Validate
{
    protected $rule = [
        'order_item_id' => 'require|integer|gt:0',
        'rating'        => 'require|integer|between:1,5',
        'content'       => 'max:500',
        'images'        => 'array',
        'is_anonymous'  => 'in:0,1',
    ];

    protected $message = [
        'order_item_id.require' => '商品行 ID 必填',
        'rating.require'        => '评分必填',
        'rating.between'        => '评分需在 1~5 之间',
        'content.max'           => '评价内容不能超过 500 字',
    ];

    protected $scene = [
        'create' => ['order_item_id', 'rating', 'content', 'images', 'is_anonymous'],
    ];
}
