<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;
use think\model\relation\BelongsTo;

class OrderInvoice extends Model
{
    protected $table = 'order_invoices';

    protected $fillable = [
        'order_id', 'order_no', 'user_id',
        'type', 'invoice_type',
        'title', 'tax_no', 'bank_name', 'bank_account',
        'company_address', 'company_phone',
        'recipient_name', 'recipient_phone', 'recipient_email',
        'amount', 'content',
        'status', 'file_url', 'admin_remark', 'issued_at',
    ];

    protected $type = [
        'order_id' => 'integer',
        'user_id'  => 'integer',
        'amount'   => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOrder::class, 'order_id');
    }
}
