<?php
declare(strict_types=1);

namespace app\model\member;

use core\base\Model;
use think\model\relation\BelongsTo;

class MemberRechargeOrder extends Model
{
    public const GROWTH_REVIEW_NONE = 'none';
    public const GROWTH_REVIEW_PENDING = 'pending';
    public const GROWTH_REVIEW_RESOLVED = 'resolved';

    public const GROWTH_RESOLUTION_CONFIRMED_APPLIED = 'confirmed_applied';
    public const GROWTH_RESOLUTION_CONFIRMED_MISSING = 'confirmed_missing';

    protected $table = 'member_recharge_orders';
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'package_id', 'order_no', 'amount', 'gift_amount', 'gift_points',
        'pay_type', 'payment_order_id', 'status', 'paid_at', 'settled_at',
        'expected_growth_value', 'growth_review_status', 'growth_review_resolution',
        'growth_review_reason', 'growth_review_operator_id', 'growth_reviewed_at',
    ];

    protected $type = [
        'amount'      => 'float',
        'gift_amount' => 'float',
        'gift_points' => 'integer',
        'package_id'  => 'integer',
        'payment_order_id' => 'integer',
        'expected_growth_value' => 'integer',
        'growth_review_operator_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\app\model\user\User::class, 'user_id');
    }
}
