<?php

declare(strict_types=1);

namespace app\model\user;

use core\base\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'nickname', 'avatar', 'mobile', 'email', 'password',
        'gender', 'birthday', 'openid', 'oa_openid', 'unionid', 'mini_openid',
        'last_login_ip', 'last_login_time', 'login_count', 'status',
        'member_level_id', 'growth_value', 'total_points', 'total_consume',
        'order_count', 'is_distributor', 'distributor_level_id', 'inviter_id', 'invite_code',
        'new_user_gift_claimed_at', 'commission_debt', 'points_debt',
    ];

    protected $hidden = ['password'];

    protected $type = [
        'gender'               => 'integer',
        'login_count'          => 'integer',
        'status'               => 'integer',
        'balance'              => 'float',
        'commission_debt'      => 'float',
        'points_debt'          => 'integer',
        'points'               => 'integer',
        'member_level_id'      => 'integer',
        'growth_value'         => 'integer',
        'total_points'         => 'integer',
        'total_consume'        => 'float',
        'order_count'          => 'integer',
        'is_distributor'       => 'integer',
        'distributor_level_id' => 'integer',
        'inviter_id'           => 'integer',
    ];

    public function memberLevel(): BelongsTo
    {
        return $this->belongsTo(\app\model\member\MemberLevel::class, 'member_level_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function auths(): HasMany
    {
        return $this->hasMany(\app\model\user\UserAuth::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(\app\model\member\MemberAddress::class, 'user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(\app\model\member\MemberFavorite::class, 'user_id');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(\app\model\member\MemberCart::class, 'user_id');
    }

    /**
     * 根据手机号查找
     */
    public static function findByMobile(string $mobile): ?static
    {
        return static::where('mobile', $mobile)->find();
    }

    /**
     * 根据 openid 查找
     */
    public static function findByOpenid(string $openid): ?static
    {
        return static::where('openid', $openid)->find();
    }

    /**
     * 根据小程序 openid 查找
     */
    public static function findByMiniOpenid(string $openid): ?static
    {
        return static::where('mini_openid', $openid)->find();
    }
}
