<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;

/** 订单拆/合/改价操作日志（只追加，快照留痕用于审计与回溯）。 */
class OrderAdjustLog extends Model
{
    protected $table = 'order_adjust_logs';
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'order_id', 'action', 'related_order_ids', 'admin_id',
        'before_snapshot', 'after_snapshot', 'remark', 'created_at',
    ];

    protected $type = [
        'order_id'          => 'integer',
        'admin_id'          => 'integer',
        'related_order_ids' => 'json',
        'before_snapshot'   => 'json',
        'after_snapshot'    => 'json',
    ];

    // action 枚举
    const ACTION_SPLIT        = 'split';
    const ACTION_MERGE        = 'merge';
    const ACTION_PRICE_ADJUST = 'price_adjust';
}
