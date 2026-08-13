<?php
declare(strict_types=1);

namespace app\adminapi\validate\v1\order;

use core\base\Validate;

/**
 * 订单拆分 / 合并 / 改价参数验证
 *
 * ThinkPHP Validate 不支持 items.*.xxx 通配规则，元素级校验通过
 * 自定义方法 checkSplitItems / checkPriceItems 实现；Service 在锁内
 * 还会按最新数据做二次业务校验。
 */
class OrderAdjustValidate extends Validate
{
    protected $rule = [
        'items'           => 'require|array',
        'order_ids'       => 'require|array|min:2',
        'freight_amount'  => 'float|egt:0',
        'discount_amount' => 'float|egt:0',
        'remark'          => 'max:255',
    ];

    protected $message = [
        'items.require'       => '请选择商品行',
        'items.array'         => '商品行参数格式错误',
        'order_ids.require'   => '请选择要合并的订单',
        'order_ids.array'     => '订单参数格式错误',
        'order_ids.min'       => '请至少选择两个订单进行合并',
        'freight_amount.float'  => '运费金额格式错误',
        'freight_amount.egt'    => '运费金额不能为负数',
        'discount_amount.float' => '优惠金额格式错误',
        'discount_amount.egt'   => '优惠金额不能为负数',
        'remark.max'          => '备注最多 255 字',
    ];

    protected $scene = [
        'merge' => ['order_ids', 'remark'],
    ];

    public function sceneSplit()
    {
        return $this->only(['items', 'remark'])
            ->append('items', 'checkSplitItems');
    }

    public function scenePriceAdjust()
    {
        return $this->only(['items', 'freight_amount', 'discount_amount', 'remark'])
            ->append('items', 'checkPriceItems');
    }

    /**
     * 拆分行：item_id require|integer|gt:0，quantity require|integer|gt:0
     *
     * @param mixed $value
     */
    protected function checkSplitItems($value): bool|string
    {
        if (!is_array($value) || $value === []) {
            return '请选择要拆分的商品行';
        }
        foreach ($value as $row) {
            if (!is_array($row)) {
                return '拆分行参数格式错误';
            }
            if (!$this->isPositiveInteger($row['item_id'] ?? null)) {
                return '拆分行 item_id 必须为正整数';
            }
            if (!$this->isPositiveInteger($row['quantity'] ?? null)) {
                return '拆分行 quantity 必须为正整数';
            }
        }
        return true;
    }

    /**
     * 改价行：item_id require|integer|gt:0，price require|float|egt:0
     *
     * @param mixed $value
     */
    protected function checkPriceItems($value): bool|string
    {
        if (!is_array($value) || $value === []) {
            return '请至少提供一条改价商品行';
        }
        foreach ($value as $row) {
            if (!is_array($row)) {
                return '改价行参数格式错误';
            }
            if (!$this->isPositiveInteger($row['item_id'] ?? null)) {
                return '改价行 item_id 必须为正整数';
            }
            $price = $row['price'] ?? null;
            if (!is_numeric($price) || (float)$price < 0) {
                return '改价行 price 必须为不小于 0 的数字';
            }
        }
        return true;
    }

    private function isPositiveInteger(mixed $value): bool
    {
        return is_numeric($value)
            && (string)(int)$value === (string)$value
            && (int)$value > 0;
    }
}
