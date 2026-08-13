<?php
declare(strict_types=1);

namespace app\model\marketing;

use app\model\user\User;
use core\base\Model;

/**
 * 用户优惠券领取记录
 *
 * 表 marketing_coupon_users 没有 deleted_at 字段（schema 仅 created_at/updated_at），
 * 关闭 deleteTime 防止 Model 默认软删 SQL 报错。
 */
class MarketingCouponUser extends Model
{
    protected $name = 'marketing_coupon_users';
    protected $deleteTime = false;

    protected $type = [
        'coupon_id'     => 'integer',
        'user_id'       => 'integer',
        'used_order_id' => 'integer',
    ];

    public function coupon(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(MarketingCoupon::class, 'coupon_id');
    }

    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
