<?php
declare(strict_types=1);

namespace app\model\system;

use core\base\Model;

class FileCategory extends Model
{
    protected $table = 'file_categories';

    protected $fillable = [
        'name', 'parent_id', 'sort',
    ];

    protected $type = [
        'parent_id' => 'integer',
        'sort'      => 'integer',
    ];

    // 文件分类不使用软删除
    protected $deleteTime = false;
}
