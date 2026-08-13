<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;
use think\model\relation\BelongsTo;

class UserAuth extends Model
{
    protected $table = 'user_auths';
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'platform', 'openid', 'unionid', 'nickname', 'avatar',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
