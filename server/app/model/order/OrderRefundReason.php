<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;

class OrderRefundReason extends Model
{
    protected $table = 'order_refund_reasons';

    protected $deleteTime = false;

    protected $fillable = [
        'name',
        'sort',
        'status',
    ];
}
