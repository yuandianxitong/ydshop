<?php
declare(strict_types=1);

namespace app\model\order;

use core\base\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

class OrderOrder extends Model
{
    protected $table = 'order_orders';

    protected $fillable = [
        'order_no', 'user_id', 'status',
        'delivery_type',
        'pickup_store_id', 'pickup_code', 'pickup_status', 'pickup_timeout_at',
        'goods_amount', 'freight_amount', 'discount_amount', 'pay_amount',
        'pay_type', 'pay_time', 'ship_time', 'receive_time',
        'buyer_remark', 'seller_remark', 'cancel_reason', 'auto_cancel_at',
        'virtual_fulfillment_status', 'virtual_fulfillment_error',
        'cancel_effects_status', 'cancel_effects_error',
        'address_snapshot',
        // 拆合单关系：parent_order_id 指向直接父/存续单；relation_type 标记来源；
        // payment_root_order_id 为拆单链扁平化后的真实支付根（NULL 表示自身即根）。
        'parent_order_id', 'relation_type', 'payment_root_order_id',
    ];

    protected $type = [
        'address_snapshot' => 'json',
        'goods_amount'     => 'float',
        'freight_amount'   => 'float',
        'discount_amount'  => 'float',
        'pay_amount'       => 'float',
        'user_id'          => 'integer',
        'parent_order_id'       => 'integer',
        'payment_root_order_id' => 'integer',
    ];

    // 暴露给前端的时间字段别名（兼容前端 _at 命名）
    protected $append = ['paid_at', 'shipped_at', 'received_at'];

    public function getPaidAtAttr($value, $data): ?string
    {
        unset($value);
        return $this->castDateTimeToString($data['pay_time'] ?? null);
    }

    public function getShippedAtAttr($value, $data): ?string
    {
        unset($value);
        return $this->castDateTimeToString($data['ship_time'] ?? null);
    }

    public function getReceivedAtAttr($value, $data): ?string
    {
        unset($value);
        return $this->castDateTimeToString($data['receive_time'] ?? null);
    }

    /**
     * 把 ThinkPHP 的 DateTime cast 对象 / DateTimeInterface / 字符串 / null 统一转成 ?string
     * 避免 strict_types 下 accessor 返回 think\model\type\DateTime 触发 TypeError
     */
    private function castDateTimeToString($value): ?string
    {
        if ($value === null || $value === '') return null;
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d H:i:s');
        if (is_object($value) && method_exists($value, '__toString')) return (string)$value;
        return is_string($value) ? $value : null;
    }

    // Status constants
    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_SHIPPED   = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CLOSED    = 'closed';

    // Transition map
    protected static array $transitions = [
        'pending'   => ['paid', 'cancelled'],
        'paid'      => ['shipped'],
        'shipped'   => ['completed'],
        'completed' => ['closed'],
        'cancelled' => [],
        'closed'    => [],
    ];

    /**
     * Check if the order can transition to the given status.
     */
    public function canTransitionTo(string $status): bool
    {
        $current = $this->status ?? '';
        return in_array($status, static::$transitions[$current] ?? [], true);
    }

    /**
     * Transition order to a new status (updates and saves).
     */
    public function transitionTo(string $status): bool
    {
        if (!$this->canTransitionTo($status)) {
            return false;
        }
        $this->status = $status;
        return $this->save();
    }

    /**
     * Status display text accessor.
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            'pending'   => '待付款',
            'paid'      => '已付款',
            'shipped'   => '已发货',
            'completed' => '已完成',
            'cancelled' => '已取消',
            'closed'    => '已关闭',
        ];
        return $map[$data['status'] ?? ''] ?? '未知';
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function logistics(): HasOne
    {
        return $this->hasOne(OrderLogistics::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\app\model\user\User::class, 'user_id', 'id');
    }

    public function deliveryOrder(): HasOne
    {
        return $this->hasOne(\app\model\delivery\DeliveryOrder::class, 'order_id');
    }

    // -------------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a unique order number: YmdHis + 4-digit random.
     */
    public static function generateOrderNo(): string
    {
        return date('YmdHis') . str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
