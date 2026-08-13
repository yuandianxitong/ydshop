<?php
declare(strict_types=1);

namespace app\model\delivery;

use core\base\Model;

/**
 * 骑手轨迹（只追加日志表）
 */
class DeliveryOrderTrack extends Model
{
    protected $table = 'delivery_order_tracks';

    // 只追加表 — 关闭 update / delete 时间戳
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'delivery_order_id', 'lat', 'lng', 'platform_status', 'reported_at',
    ];

    protected $type = [
        'delivery_order_id' => 'integer',
        'lat'               => 'float',
        'lng'               => 'float',
    ];
}
