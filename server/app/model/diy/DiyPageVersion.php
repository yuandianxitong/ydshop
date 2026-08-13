<?php
declare(strict_types=1);

namespace app\model\diy;

use core\base\Model;

class DiyPageVersion extends Model
{
    protected $table = 'diy_page_versions';

    // 只追加表 — 关闭 update / delete 时间戳
    protected $updateTime = false;
    protected $deleteTime = false;

    protected $fillable = [
        'page_id', 'version_no', 'title', 'components', 'page_settings',
        'note', 'created_by',
    ];

    protected $type = [
        'page_id'    => 'integer',
        'version_no' => 'integer',
        'created_by' => 'integer',
    ];

    protected $json = ['components', 'page_settings'];
    protected $jsonAssoc = true;
}
