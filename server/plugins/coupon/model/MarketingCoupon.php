<?php
declare(strict_types=1);

namespace plugins\coupon\model;

use core\base\Model;

/**
 * 优惠券模型
 *
 * @property int    $id
 * @property string $name
 * @property string $type         fixed|percent|no_threshold
 * @property float  $value
 * @property float  $min_amount
 * @property float  $max_discount
 * @property int    $total_count
 * @property int    $used_count
 * @property int    $per_user_limit
 * @property string $use_scope    all|category|spu
 * @property array  $scope_ids
 * @property string $start_at
 * @property string $end_at
 * @property int    $status
 */
class MarketingCoupon extends Model
{
    protected $table = 'marketing_coupons';

    protected $fillable = [
        'name', 'type', 'value', 'min_amount', 'max_discount',
        'total_count', 'used_count', 'per_user_limit',
        'use_scope', 'scope_ids', 'start_at', 'end_at', 'status',
    ];

    protected $type = [
        'scope_ids'    => 'json',
        'value'        => 'float',
        'min_amount'   => 'float',
        'max_discount' => 'float',
        'total_count'  => 'integer',
        'used_count'   => 'integer',
        'per_user_limit' => 'integer',
        'status'       => 'integer',
    ];

    /**
     * 是否已过期
     */
    public function isExpired(): bool
    {
        $now = date('Y-m-d H:i:s');
        return ($this->end_at && $this->end_at < $now);
    }

    /**
     * 是否在有效期内且可领取
     */
    public function isAvailable(): bool
    {
        if ($this->status !== 1) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if ($this->start_at && $this->start_at > $now) {
            return false;
        }
        if ($this->end_at && $this->end_at < $now) {
            return false;
        }
        return true;
    }

    /**
     * 关联领取记录
     */
    public function couponUsers()
    {
        return $this->hasMany(MarketingCouponUser::class, 'coupon_id');
    }
}
