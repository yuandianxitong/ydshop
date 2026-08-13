<?php
declare(strict_types=1);

namespace plugins\new_user_gift\model;

use core\base\Model;

class NewUserGift extends Model
{
    protected $table = 'new_user_gifts';

    protected $fillable = [
        'name', 'description', 'status', 'sort_order',
        'conditions', 'points', 'balance', 'coupon_ids',
        'valid_start', 'valid_end',
    ];

    protected $type = [
        'status'     => 'integer',
        'sort_order' => 'integer',
        'points'     => 'integer',
        'conditions' => 'json',
        'coupon_ids' => 'json',
    ];
}
