<?php
declare(strict_types=1);

namespace app\model\marketing;

use core\base\Model;

class MarketingCoupon extends Model
{
    protected $name = 'marketing_coupons';
    protected $deleteTime = 'deleted_at';

    protected $type = [
        'value'        => 'float',
        'min_amount'   => 'float',
        'max_discount' => 'float',
        'total_count'  => 'integer',
        'used_count'   => 'integer',
        'per_user_limit' => 'integer',
        'status'       => 'integer',
    ];
}
