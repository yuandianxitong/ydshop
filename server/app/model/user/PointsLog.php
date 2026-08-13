<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

class PointsLog extends Model
{
    protected $updateTime = false;
    protected $deleteTime = false;
    protected $name = 'points_logs';
    protected $fillable = [
        'user_id', 'points', 'before_points', 'after_points',
        'type', 'source', 'event_key', 'remark', 'operator_id',
    ];
    protected $type = [
        'user_id'       => 'integer',
        'points'        => 'integer',
        'before_points' => 'integer',
        'after_points'  => 'integer',
        'type'          => 'integer',
        'operator_id'   => 'integer',
    ];
    protected $append = ['type_text'];

    const TYPE_ADMIN_ADJUST   = 1;
    const TYPE_REGISTER       = 2;
    const TYPE_SIGN_IN        = 3;
    const TYPE_CONSUME_AWARD  = 4;
    const TYPE_CONSUME_DEDUCT = 5;
    const TYPE_POINTS_MALL    = 6;
    const TYPE_LOTTERY_PRIZE  = 7;
    const TYPE_LOTTERY_DEDUCT = 8;
    const TYPE_RECHARGE_GIFT  = 9;
    const TYPE_REWARD_REVERSAL = 10;
    const TYPE_MAP = [
        self::TYPE_ADMIN_ADJUST   => '后台调整',
        self::TYPE_REGISTER       => '注册赠送',
        self::TYPE_SIGN_IN        => '签到',
        self::TYPE_CONSUME_AWARD  => '消费赠送',
        self::TYPE_CONSUME_DEDUCT => '消费扣减',
        self::TYPE_POINTS_MALL    => '积分商城兑换',
        self::TYPE_LOTTERY_PRIZE  => '抽奖中奖',
        self::TYPE_LOTTERY_DEDUCT => '抽奖消耗',
        self::TYPE_RECHARGE_GIFT  => '充值赠送',
        self::TYPE_REWARD_REVERSAL => '消费奖励冲正',
    ];

    public function getTypeTextAttr($value, $data): string
    {
        if (!isset($data['type'])) return '';
        return self::TYPE_MAP[$data['type']] ?? '未知';
    }

    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function operator(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\model\system\Admin::class, 'operator_id');
    }
}
