<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsFreightTemplateFreeRule extends Model
{
    protected $table = 'goods_freight_template_free_rules';
    protected $deleteTime = false;

    protected $fillable = [
        'template_id', 'region_ids', 'free_num', 'free_amount',
    ];

    protected $type = [
        'region_ids'  => 'json',
        'free_num'    => 'integer',
        'free_amount' => 'float',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(GoodsFreightTemplate::class, 'template_id');
    }
}
