<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\HasMany;

class GoodsFreightTemplate extends Model
{
    protected $table = 'goods_freight_templates';

    protected $fillable = ['name', 'charge_type', 'is_free', 'sort', 'no_delivery_region_ids'];

    protected $type = [
        'is_free' => 'integer',
        'sort' => 'integer',
        'no_delivery_region_ids' => 'json',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(GoodsFreightTemplateRule::class, 'template_id');
    }

    public function freeRules(): HasMany
    {
        return $this->hasMany(GoodsFreightTemplateFreeRule::class, 'template_id');
    }
}