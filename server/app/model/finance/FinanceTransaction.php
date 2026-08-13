<?php

declare(strict_types=1);

namespace app\model\finance;

use core\base\Model;

class FinanceTransaction extends Model
{
    protected $table = 'finance_transactions';

    /** 追加型日志表，无更新、无软删除 */
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'transaction_no',
        'event_key',
        'type',
        'biz_type',
        'biz_id',
        'biz_no',
        'amount',
        'payment_channel',
        'trade_no',
        'user_id',
        'remark',
        'created_at',
    ];

    protected $type = [
        'amount' => 'float',
        'biz_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * 生成高熵交易流水号：FT + YmdHis + 48 bit 密码学随机数。
     *
     * 最终唯一性仍由数据库 transaction_no 唯一键保证；Repository
     * 会在极低概率的碰撞时重新生成并重试。
     */
    public static function generateTransactionNo(): string
    {
        return 'FT' . date('YmdHis') . strtoupper(bin2hex(random_bytes(6)));
    }

    /**
     * 类型文本访问器
     */
    public function getTypeTextAttr($value, $data): string
    {
        $map = [
            'income'  => '收入',
            'expense' => '支出',
            'refund'  => '退款',
        ];
        return $map[$data['type'] ?? ''] ?? '未知';
    }

    /**
     * 业务类型文本访问器
     */
    public function getBizTypeTextAttr($value, $data): string
    {
        $map = [
            'order'      => '订单收款',
            'recharge'   => '余额充值',
            'withdrawal' => '分销提现',
            'refund'     => '订单退款',
        ];
        return $map[$data['biz_type'] ?? ''] ?? ($data['biz_type'] ?? '');
    }
}
