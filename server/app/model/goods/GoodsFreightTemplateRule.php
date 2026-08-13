<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsFreightTemplateRule extends Model
{
    protected $table = 'goods_freight_template_rules';
    protected $deleteTime = false;

    protected $fillable = [
        'template_id', 'region_ids', 'first_unit', 'first_price',
        'continue_unit', 'continue_price',
    ];

    protected $type = [
        'region_ids'     => 'json',
        'first_unit'     => 'float',
        'first_price'    => 'float',
        'continue_unit'  => 'float',
        'continue_price' => 'float',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(GoodsFreightTemplate::class, 'template_id');
    }
}
