<?php
declare(strict_types=1);

namespace app\model\member;

use core\base\Model;
use think\model\relation\BelongsTo;

class MemberCart extends Model
{
    protected $table = 'member_cart';
    protected $deleteTime = false;

    protected $fillable = [
        'user_id', 'sku_id', 'quantity', 'selected',
    ];

    protected $type = [
        'quantity' => 'integer',
        'selected' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\app\model\user\User::class, 'user_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(\app\model\goods\GoodsSku::class, 'sku_id');
    }
}
