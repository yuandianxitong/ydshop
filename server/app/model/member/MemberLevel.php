<?php
declare(strict_types=1);

namespace app\model\member;

use core\base\Model;

class MemberLevel extends Model
{
    protected $table = 'member_levels';

    protected $fillable = [
        'name', 'icon', 'growth_min', 'discount', 'points_rate',
        'free_freight', 'privileges', 'sort', 'status',
    ];

    protected $type = [
        'privileges'   => 'json',
        'discount'     => 'float',
        'points_rate'  => 'float',
        'growth_min'   => 'integer',
    ];
}
