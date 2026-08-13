<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;
use think\model\relation\HasMany;
use think\model\relation\BelongsTo;

class GoodsCategory extends Model
{
    protected $table = 'goods_categories';

    protected $fillable = ['parent_id', 'name', 'icon', 'sort', 'level', 'path', 'status', 'is_show'];

    protected $type = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'level' => 'integer',
        'status' => 'integer',
        'is_show' => 'integer',
    ];

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        return $this->getStatusText($data['status'] ?? 0, [1 => '正常', 0 => '禁用']);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GoodsCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(GoodsCategory::class, 'parent_id');
    }
}