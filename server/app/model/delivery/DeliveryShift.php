<?php
declare(strict_types=1);

namespace app\model\delivery;

use core\base\Model;

class DeliveryShift extends Model
{
    protected $table = 'delivery_shifts';

    protected $deleteTime = 'deleted_at';

    protected $fillable = [
        'staff_id', 'weekday', 'start_time', 'end_time', 'remark',
    ];

    protected $type = [
        'staff_id' => 'integer',
        'weekday'  => 'integer',
    ];
}
