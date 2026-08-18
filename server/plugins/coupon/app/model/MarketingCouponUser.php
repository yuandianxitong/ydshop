<?php
declare(strict_types=1);

namespace plugins\coupon\model;

use core\base\Model;

/**
 * 用户优惠券领取记录模型
 *
 * @property int    $id
 * @property int    $coupon_id
 * @property int    $user_id
 * @property string $status        unused|used|expired
 * @property int    $used_order_id
 * @property string|null $used_at
 */
class MarketingCouponUser extends Model
{
    protected $table = 'marketing_coupon_users';

    // 无软删除
    protected $deleteTime = false;

    protected $fillable = [
        'coupon_id', 'user_id', 'status', 'used_order_id', 'used_at',
    ];

    protected $type = [
        'coupon_id'     => 'integer',
        'user_id'       => 'integer',
        'used_order_id' => 'integer',
    ];

    /**
     * 关联优惠券
     */
    public function coupon()
    {
        return $this->belongsTo(MarketingCoupon::class, 'coupon_id');
    }
}
