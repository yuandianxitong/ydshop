<?php
declare(strict_types=1);

namespace app\model;

use core\base\Model;

class Store extends Model
{
    protected $name = 'stores';

    protected $fillable = [
        'name', 'code',
        'address', 'province', 'city', 'district', 'detail', 'region_code',
        'lng', 'lat', 'phone',
        'business_hours', 'status', 'sort', 'remark',
    ];

    protected $type = [
        'business_hours' => 'json',
        'lng'            => 'float',
        'lat'            => 'float',
        'status'         => 'integer',
        'sort'           => 'integer',
    ];

    protected $append = ['is_open_now', 'status_text'];

    /**
     * 当前是否营业（按系统时间 + business_hours JSON 判断）
     */
    public function getIsOpenNowAttr($value, $data): bool
    {
        unset($value); // ThinkPHP 访问器签名要求第一个参数为原始 value，is_open_now 是虚拟字段不读 value
        // $data['business_hours'] 在 type cast 'json' 下是 think\model\type\Json 对象，
        // 直接 is_array 永远 false，需要 ->value() 取出真数组（或 string 时手动 decode）
        $rawHours = $data['business_hours'] ?? null;
        if ($rawHours instanceof \think\model\type\Json) {
            $hours = $rawHours->value();
        } elseif (is_string($rawHours)) {
            $hours = json_decode($rawHours, true);
        } else {
            $hours = $rawHours;
        }
        if (!is_array($hours)) return false;

        $today = strtolower(date('D')); // mon/tue/wed/thu/fri/sat/sun
        $key = match ($today) {
            'mon' => 'mon', 'tue' => 'tue', 'wed' => 'wed', 'thu' => 'thu',
            'fri' => 'fri', 'sat' => 'sat', 'sun' => 'sun',
            default => null,
        };
        if (!$key || !isset($hours[$key])) return false;

        $slot = $hours[$key];
        if ($slot === 'closed' || !is_array($slot) || empty($slot)) return false;

        $now = date('H:i');
        foreach ($slot as $period) {
            if (!isset($period['open'], $period['close'])) continue;
            $open = $period['open'];
            $close = $period['close'];
            if ($open <= $close) {
                // 同日时段：09:00-21:00
                if ($now >= $open && $now <= $close) return true;
            } else {
                // 跨午夜时段：22:00-02:00
                if ($now >= $open || $now <= $close) return true;
            }
        }
        return false;
    }
}
