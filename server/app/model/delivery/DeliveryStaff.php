<?php
declare(strict_types=1);

namespace app\model\delivery;

use core\base\Model;

class DeliveryStaff extends Model
{
    protected $table = 'delivery_staff';
    protected $deleteTime = false;

    protected $fillable = ['name', 'phone', 'status'];

    protected $type = [
        'status' => 'integer',
    ];
}
