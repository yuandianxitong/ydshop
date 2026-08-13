<?php
declare(strict_types=1);

namespace app\model\marketing;

use core\base\Model;

class MarketingAdPosition extends Model
{
    protected $name = 'marketing_ad_positions';

    protected $fillable = [
        'code', 'name', 'description', 'recommended_width', 'recommended_height',
        'is_carousel', 'sort', 'status',
    ];

    protected $type = [
        'recommended_width'  => 'integer',
        'recommended_height' => 'integer',
        'is_carousel'        => 'integer',
        'sort'               => 'integer',
        'status'             => 'integer',
    ];

    /**
     * 该位置下的广告（仅启用 + 未软删，按 sort desc）
     */
    public function ads()
    {
        return $this->hasMany(MarketingAd::class, 'position_id', 'id');
    }
}
