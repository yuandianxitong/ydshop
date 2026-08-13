<?php
declare(strict_types=1);

namespace app\model\payment;

use core\base\Model;

class PaymentOrder extends Model
{
    protected $name = 'payment_orders';

    protected $fillable = [
        'user_id', 'biz_type', 'business_order_no', 'client_type',
        'order_no', 'trade_no', 'channel', 'trade_type',
        'subject', 'body', 'total_amount', 'refund_amount',
        'status', 'notify_data', 'extra',
        'provider_account_id', 'provider_app_id', 'provider_expires_at',
        'provider_attempt_token', 'provider_request_hash',
        'provider_reconcile_retry_count', 'provider_reconcile_next_at',
        'provider_reconcile_last_error',
        'paid_at', 'refunded_at',
    ];

    protected $type = [
        'user_id'       => 'integer',
        'total_amount'  => 'float',
        'refund_amount' => 'float',
    ];

    // 状态常量
    const STATUS_PENDING  = 'pending';
    const STATUS_CREATING = 'creating';
    const STATUS_CLOSING  = 'closing';
    const STATUS_PAID     = 'paid';
    const STATUS_CLOSED   = 'closed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * 根据商户订单号查找
     */
    public static function findByOrderNo(string $orderNo): ?static
    {
        return static::where('order_no', $orderNo)->find();
    }
}
