<?php
declare(strict_types=1);

namespace app\model\delivery;

use app\model\order\OrderOrder;
use core\base\Model;
use think\model\relation\BelongsTo;

class DeliveryOrder extends Model
{
    protected $table = 'delivery_orders';
    protected $deleteTime = false;

    protected $fillable = [
        'order_id', 'staff_id', 'platform', 'status',
        'distance', 'fee', 'remark',
        'dest_lat', 'dest_lng',
        'assigned_at', 'picked_at', 'completed_at',
        // 三方同城配送平台字段
        'platform_order_id', 'platform_status',
        'rider_name', 'rider_phone', 'rider_lat', 'rider_lng',
        'dispatched_at', 'dispatch_fail_reason', 'callback_raw',
    ];

    protected $type = [
        'order_id'  => 'integer',
        'staff_id'  => 'integer',
        'distance'  => 'float',
        'fee'       => 'float',
        'dest_lat'  => 'float',
        'dest_lng'  => 'float',
        'rider_lat' => 'float',
        'rider_lng' => 'float',
    ];

    protected $json = ['callback_raw'];
    protected $jsonAssoc = true;

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOrder::class, 'order_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(DeliveryStaff::class, 'staff_id');
    }
}
