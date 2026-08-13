<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;
use think\model\relation\BelongsTo;

class OrderLogistics extends Model
{
    protected $table = 'order_logistics';

    // No soft delete
    protected $deleteTime = false;

    protected $fillable = [
        'order_id', 'express_company', 'express_no', 'waybill_no',
        'traces', 'status',
    ];

    protected $type = [
        'traces' => 'json',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOrder::class, 'order_id');
    }
}
