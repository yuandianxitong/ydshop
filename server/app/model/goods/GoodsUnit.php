<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\BelongsTo;

class GoodsUnit extends Model
{
    protected $table = 'goods_units';

    protected $fillable = [
        'code', 'name', 'name_en', 'group_id', 'decimal_places',
        'is_base', 'ratio', 'sort', 'status',
    ];

    protected $type = [
        'group_id'       => 'integer',
        'decimal_places' => 'integer',
        'is_base'        => 'integer',
        'ratio'          => 'float',
        'sort'           => 'integer',
        'status'         => 'integer',
    ];

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        return $this->getStatusText($data['status'] ?? 0, [1 => '正常', 0 => '禁用']);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(GoodsUnitGroup::class, 'group_id');
    }
}
