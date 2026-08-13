<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;
use think\model\relation\BelongsTo;

class OrderPayment extends Model
{
    protected $table = 'order_payments';

    // No soft delete
    protected $deleteTime = false;

    protected $fillable = [
        'order_id', 'payment_order_id', 'pay_type',
        'amount', 'status', 'trade_no', 'paid_at', 'refunded_at',
    ];

    protected $type = [
        'amount'           => 'float',
        'payment_order_id' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOrder::class, 'order_id');
    }
}
