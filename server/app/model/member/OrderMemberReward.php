<?php

declare(strict_types=1);

namespace app\model\member;

use core\base\Model;

/**
 * 订单完成时的会员权益快照。
 *
 * points/growth/consume/order_count 记录理论快照；verified_* 单独记录有不可变
 * 订单级证据、允许自动冲正的权益。后续退款只追加 adjustment 并更新冲正游标。
 */
class OrderMemberReward extends Model
{
    public const ORIGIN_NATIVE = 'native';
    public const ORIGIN_LEGACY_IMPORT = 'legacy_import';

    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_PARTIAL = 'partial';
    public const VERIFICATION_UNVERIFIED = 'unverified';

    public const REVIEW_NONE = 'none';
    public const REVIEW_PENDING = 'pending';
    public const REVIEW_RESOLVED = 'resolved';

    protected $table = 'order_member_rewards';
    protected $deleteTime = false;

    protected $fillable = [
        'order_id', 'user_id', 'eligible_item_ids', 'reward_amount',
        'points_rate', 'points', 'points_credited', 'points_debt_offset',
        'growth', 'consume_amount', 'order_count',
        'origin', 'verification_status',
        'verified_points', 'verified_points_credited', 'verified_growth',
        'verified_consume_amount', 'verified_order_count',
        'evidence', 'review_status', 'review_resolution', 'review_reason',
        'review_operator_id', 'reviewed_at',
        'refunded_amount', 'reversed_points', 'reversed_points_credited', 'reversed_growth',
        'reversed_consume_amount', 'reversed_order_count',
        'awarded_at', 'fully_reversed_at',
    ];

    protected $type = [
        'order_id'                 => 'integer',
        'user_id'                  => 'integer',
        'eligible_item_ids'        => 'json',
        'reward_amount'            => 'float',
        'points_rate'              => 'float',
        'points'                   => 'integer',
        'points_credited'          => 'integer',
        'points_debt_offset'       => 'integer',
        'growth'                   => 'integer',
        'consume_amount'           => 'float',
        'order_count'              => 'integer',
        'verified_points'          => 'integer',
        'verified_points_credited' => 'integer',
        'verified_growth'          => 'integer',
        'verified_consume_amount'  => 'float',
        'verified_order_count'     => 'integer',
        'review_operator_id'       => 'integer',
        'evidence'                 => 'json',
        'refunded_amount'          => 'float',
        'reversed_points'          => 'integer',
        'reversed_points_credited' => 'integer',
        'reversed_growth'          => 'integer',
        'reversed_consume_amount'  => 'float',
        'reversed_order_count'     => 'integer',
    ];
}
