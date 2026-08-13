<?php

declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

class BalanceLog extends Model
{
    protected $updateTime = false;
    protected $deleteTime = false;
    protected $name = 'balance_logs';
    protected $fillable = [
        'user_id', 'amount', 'before_balance', 'after_balance',
        'type', 'source', 'event_key', 'remark', 'operator_id',
    ];
    protected $type = [
        'user_id'        => 'integer',
        'amount'         => 'float',
        'before_balance' => 'float',
        'after_balance'  => 'float',
        'type'           => 'integer',
        'operator_id'    => 'integer',
    ];
    protected $append = ['type_text'];

    public const TYPE_RECHARGE            = 1;
    public const TYPE_CONSUME             = 2;
    public const TYPE_REFUND              = 3;
    public const TYPE_ADMIN_ADJUST        = 4;
    public const TYPE_DISTRIBUTION_SETTLE = 5;
    public const TYPE_DISTRIBUTION_WITHDRAWAL = 6;
    public const TYPE_DISTRIBUTION_WITHDRAWAL_REFUND = 7;
    public const TYPE_DISTRIBUTION_REVERSAL = 8;
    public const TYPE_MAP = [
        self::TYPE_RECHARGE            => '充值',
        self::TYPE_CONSUME             => '消费',
        self::TYPE_REFUND              => '退款',
        self::TYPE_ADMIN_ADJUST        => '后台调整',
        self::TYPE_DISTRIBUTION_SETTLE => '分销佣金结算',
        self::TYPE_DISTRIBUTION_WITHDRAWAL => '分销提现冻结',
        self::TYPE_DISTRIBUTION_WITHDRAWAL_REFUND => '分销提现退回',
        self::TYPE_DISTRIBUTION_REVERSAL => '分销佣金退款冲正',
    ];

    public function getTypeTextAttr($value, $data): string
    {
        if (!isset($data['type'])) {
            return '';
        }
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
