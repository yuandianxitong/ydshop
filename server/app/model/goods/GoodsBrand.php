<?php
declare(strict_types=1);

namespace app\model\goods;

use core\base\Model;

class GoodsBrand extends Model
{
    protected $table = 'goods_brands';

    protected $fillable = ['name', 'logo', 'description', 'sort', 'status'];

    protected $type = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        return $this->getStatusText($data['status'] ?? 0, [1 => '正常', 0 => '禁用']);
    }
}