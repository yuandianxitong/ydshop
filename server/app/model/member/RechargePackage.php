<?php
declare(strict_types=1);

namespace app\model\member;

use core\base\Model;

class RechargePackage extends Model
{
    protected $table = 'recharge_packages';

    protected $fillable = [
        'amount', 'gift_amount', 'gift_points', 'sort', 'status',
    ];

    protected $type = [
        'amount'      => 'float',
        'gift_amount' => 'float',
        'gift_points' => 'integer',
        'sort'        => 'integer',
        'status'      => 'integer',
    ];
}
