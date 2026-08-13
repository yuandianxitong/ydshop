<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;
use think\model\relation\BelongsTo;

class OrderReview extends Model
{
    protected $table = 'order_reviews';

    protected $fillable = [
        'order_item_id', 'user_id', 'spu_id', 'sku_id',
        'rating', 'content', 'images',
        'is_anonymous', 'reply_content', 'reply_at',
    ];

    protected $type = [
        'images' => 'json',
        'rating' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
