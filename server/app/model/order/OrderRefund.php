<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;
use think\model\relation\BelongsTo;

class OrderRefund extends Model
{
    protected $table = 'order_refunds';

    protected $fillable = [
        'refund_no', 'order_id', 'order_item_id', 'user_id',
        'type', 'status', 'reason', 'description', 'images',
        'refund_amount', 'refund_trade_no', 'refund_trade_no_source',
        'provider_status', 'provider_requested_at', 'provider_checked_at', 'refunded_at',
        'failure_reason', 'retry_count',
        'return_express_company', 'return_express_no',
        'admin_remark', 'refuse_reason',
    ];

    protected $type = [
        'images'        => 'json',
        'refund_amount' => 'float',
    ];

    // Transition map
    protected static array $transitions = [
        'pending'   => ['approved', 'rejected'],
        'approved'  => ['returning', 'refunding'],
        'returning' => ['received', 'rejected'],
        'received'  => ['refunding', 'rejected'],
        'refunding' => ['retryable_failed', 'manual_review', 'refunded'],
        'retryable_failed' => ['refunding', 'rejected', 'refunded'],
        'manual_review' => ['refunded'],
        'refunded'  => [],
        'rejected'  => [],
    ];

    /**
     * Check if the refund can transition to the given status.
     */
    public function canTransitionTo(string $status): bool
    {
        $current = $this->status ?? '';
        return in_array($status, static::$transitions[$current] ?? [], true);
    }

    /**
     * Transition refund to a new status (updates and saves).
     */
    public function transitionTo(string $status): bool
    {
        if (!$this->canTransitionTo($status)) {
            return false;
        }
        $this->status = $status;
        return $this->save();
    }

    /**
     * Status display text accessor.
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            'pending'   => '待处理',
            'approved'  => '已同意',
            'returning' => '退货中',
            'received'  => '已收货',
            'refunding' => '退款中',
            'retryable_failed' => '可重试失败',
            'manual_review' => '待人工复核',
            'refunded'  => '已退款',
            'rejected'  => '已拒绝',
        ];
        return $map[$data['status'] ?? ''] ?? '未知';
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOrder::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\app\model\user\User::class, 'user_id', 'id');
    }

}
