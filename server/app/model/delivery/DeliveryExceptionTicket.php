<?php
declare(strict_types=1);

namespace app\model\delivery;

use core\base\Model;

class DeliveryExceptionTicket extends Model
{
    protected $table = 'delivery_exception_tickets';

    protected $deleteTime = 'deleted_at';

    protected $fillable = [
        'ticket_no', 'type', 'status', 'delivery_order_id',
        'title', 'description', 'evidence',
        'reporter', 'contact',
        'resolution_note', 'handled_by', 'handled_at',
    ];

    protected $type = [
        'evidence'          => 'json',
        'delivery_order_id' => 'integer',
        'handled_by'        => 'integer',
    ];
}
