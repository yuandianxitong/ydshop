<?php
declare(strict_types=1);

namespace app\model\member;

use core\base\Model;
use think\model\relation\BelongsTo;

class MemberBrowseHistory extends Model
{
    protected $table = 'member_browse_histories';
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'spu_id', 'viewed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\app\model\user\User::class, 'user_id');
    }

    public function spu(): BelongsTo
    {
        return $this->belongsTo(\app\model\goods\GoodsSpu::class, 'spu_id');
    }
}
