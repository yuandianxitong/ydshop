<?php
declare(strict_types=1);

namespace app\model\member;

use app\model\system\Admin;
use app\model\user\User;
use core\base\Model;

class MemberRemark extends Model
{
    protected $name = 'member_remarks';
    protected $updateTime = false;
    protected $deleteTime = 'deleted_at';

    protected $type = [
        'user_id'     => 'integer',
        'operator_id' => 'integer',
    ];

    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function operator(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'operator_id');
    }
}
