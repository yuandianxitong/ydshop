<?php
declare(strict_types=1);

namespace app\model\member;

use core\base\Model;

/** 会员成长值流水（只追加，用 source 唯一键保证领域事件幂等）。 */
class MemberGrowthLog extends Model
{
    protected $table = 'member_growth_logs';
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'value', 'before_growth', 'after_growth', 'source', 'created_at',
    ];

    protected $type = [
        'user_id'       => 'integer',
        'value'         => 'integer',
        'before_growth' => 'integer',
        'after_growth'  => 'integer',
    ];
}
