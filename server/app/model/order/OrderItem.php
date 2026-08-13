<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasOne;

class OrderItem extends Model
{
    protected $table = 'order_items';

    // No soft delete
    protected $deleteTime = false;

    protected $fillable = [
        'order_id', 'spu_id', 'sku_id', 'flash_item_id',
        'goods_name', 'goods_image', 'spec_text',
        'price', 'quantity', 'total_amount',
        'discount_amount', 'freight_amount', 'pay_amount',
        'is_reviewed', 'refund_status',
        // 拆单溯源：该行由哪个原始商品行拆出（NULL 表示非拆单产物）。
        'split_from_item_id',
    ];

    protected $type = [
        'price'        => 'float',
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'freight_amount' => 'float',
        'pay_amount' => 'float',
        'quantity'     => 'integer',
        'flash_item_id' => 'integer',
        'split_from_item_id' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOrder::class, 'order_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class, 'order_item_id');
    }

    public function refund(): HasOne
    {
        return $this->hasOne(OrderRefund::class, 'order_item_id');
    }
}
