<?php
declare(strict_types=1);

namespace plugins\new_user_gift\model;

use app\model\user\User;
use core\base\Model;

class NewUserGiftLog extends Model
{
    protected $table = 'new_user_gift_logs';

    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'gift_id', 'gift_name',
        'points_awarded', 'balance_awarded', 'coupon_ids',
    ];

    protected $type = [
        'user_id'        => 'integer',
        'gift_id'        => 'integer',
        'points_awarded' => 'integer',
        'coupon_ids'     => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
