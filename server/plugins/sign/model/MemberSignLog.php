<?php
declare(strict_types=1);

namespace plugins\sign\model;

use core\base\Model;

class MemberSignLog extends Model
{
    protected $table = 'member_sign_logs';

    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'sign_date', 'continuous_days', 'points_awarded', 'is_makeup', 'source',
    ];

    protected $type = [
        'user_id'         => 'integer',
        'continuous_days' => 'integer',
        'points_awarded'  => 'integer',
        'is_makeup'       => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(\app\model\user\User::class, 'user_id', 'id');
    }
}
