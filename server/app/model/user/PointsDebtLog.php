<?php

declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

/** 积分债务的不可变审计流水；正数增加债务，负数偿还债务。 */
class PointsDebtLog extends Model
{
    protected $table = 'points_debt_logs';
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'delta', 'before_debt', 'after_debt',
        'source', 'event_key', 'remark',
    ];

    protected $type = [
        'user_id'     => 'integer',
        'delta'       => 'integer',
        'before_debt' => 'integer',
        'after_debt'  => 'integer',
    ];
}
