<?php

declare(strict_types=1);

namespace app\model\member;

use core\base\Model;

/** 不可变的订单会员权益调整流水。 */
class OrderMemberRewardAdjustment extends Model
{
    protected $table = 'order_member_reward_adjustments';
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'reward_id', 'order_id', 'refund_id', 'user_id', 'action', 'event_key',
        'refund_amount', 'points', 'points_credited_reversed',
        'growth', 'consume_amount', 'order_count', 'points_debt_added', 'remark',
    ];

    protected $type = [
        'reward_id'         => 'integer',
        'order_id'          => 'integer',
        'refund_id'         => 'integer',
        'user_id'           => 'integer',
        'refund_amount'     => 'float',
        'points'            => 'integer',
        'points_credited_reversed' => 'integer',
        'growth'            => 'integer',
        'consume_amount'    => 'float',
        'order_count'       => 'integer',
        'points_debt_added' => 'integer',
    ];
}
