<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

class UserTag extends Model
{
    protected $table = 'user_tags';

    protected $fillable = [
        'name', 'description', 'color', 'group_type',
        'rules', 'auto_update', 'user_count', 'status', 'sort',
    ];

    protected $type = [
        'rules'       => 'json',
        'auto_update' => 'integer',
        'user_count'  => 'integer',
        'status'      => 'integer',
        'sort'        => 'integer',
    ];

    protected $append = ['group_type_text'];

    public const GROUP_TYPES = [
        'consume'   => '消费力',
        'behavior'  => '行为偏好',
        'lifecycle' => '生命周期',
        'social'    => '社交属性',
    ];

    public function getGroupTypeTextAttr($value, $data): string
    {
        $key = $data['group_type'] ?? '';
        return self::GROUP_TYPES[$key] ?? '';
    }
}
