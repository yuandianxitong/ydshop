<?php
declare(strict_types=1);

namespace app\model\member;

use core\base\Model;
use think\model\relation\BelongsTo;

class MemberAddress extends Model
{
    protected $table = 'member_addresses';

    protected $fillable = [
        'user_id', 'name', 'phone', 'province', 'city',
        'district', 'detail', 'lng', 'lat', 'region_code', 'is_default',
    ];

    protected $type = [
        'lng' => 'float',
        'lat' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\app\model\user\User::class, 'user_id');
    }
}
