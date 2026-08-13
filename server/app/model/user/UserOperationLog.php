<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

/**
 * 用户操作日志（会员详情聚合表）
 *
 * 由各业务事件 Listener 写入，按 category 分类供「会员详情 - 操作日志」Tab 渲染。
 * 只追加不修改 → 关闭 update/delete 时间戳。
 */
class UserOperationLog extends Model
{
    protected $name = 'user_operation_logs';
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $json = ['meta'];
    protected $jsonAssoc = true;

    protected $fillable = [
        'user_id', 'category', 'event_key', 'event_code', 'title', 'description',
        'icon', 'tone', 'ref_type', 'ref_id', 'meta', 'created_at',
    ];

    protected $type = [
        'user_id' => 'integer',
        'ref_id'  => 'integer',
        'meta'    => 'json',
    ];

    public const CATEGORIES = ['login', 'asset', 'level', 'order', 'service', 'profile', 'other'];

    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
