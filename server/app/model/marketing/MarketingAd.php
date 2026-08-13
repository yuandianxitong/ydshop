<?php
declare(strict_types=1);

namespace app\model\marketing;

use core\base\Model;

class MarketingAd extends Model
{
    protected $name = 'marketing_ads';

    protected $fillable = [
        'position_id', 'title', 'image', 'link',
        'start_at', 'end_at', 'sort', 'status',
    ];

    protected $type = [
        'position_id' => 'integer',
        'sort'        => 'integer',
        'status'      => 'integer',
        'start_at'    => 'datetime',
        'end_at'      => 'datetime',
    ];

    protected $append = ['is_active'];

    /**
     * 当前是否生效：status=1 AND 时间窗口内 AND position.status=1
     * admin 列表用，C 端不依赖（C 端走 SQL 过滤）
     */
    public function getIsActiveAttr($value, $data): int
    {
        if (($data['status'] ?? 0) !== 1) return 0;
        $now = time();
        if (!empty($data['start_at']) && strtotime($data['start_at']) > $now) return 0;
        if (!empty($data['end_at']) && strtotime($data['end_at']) <= $now) return 0;
        // position.status 判断由 admin 列表 join 后传入 raw data；此处仅依赖 ad 自身
        return 1;
    }

    public function position()
    {
        return $this->belongsTo(MarketingAdPosition::class, 'position_id', 'id');
    }
}
