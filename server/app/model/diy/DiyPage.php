<?php
declare(strict_types=1);

namespace app\model\diy;

use core\base\Model;

class DiyPage extends Model
{
    protected $table = 'diy_pages';

    protected $fillable = [
        'page_type', 'platform', 'title', 'components', 'page_settings',
        'is_published', 'is_default', 'sort', 'status'
    ];

    protected $type = [
        'is_published' => 'integer',
        'is_default'   => 'integer',
        'sort'         => 'integer',
        'status'       => 'integer',
    ];

    protected $json = ['components', 'page_settings'];

    protected $jsonAssoc = true;

    protected $append = ['page_type_text', 'platform_text'];

    public function getPageTypeTextAttr($value, $data): string
    {
        $map = ['home' => '首页', 'category' => '分类页', 'custom' => '自定义页'];
        return $map[$data['page_type'] ?? ''] ?? '未知';
    }

    public function getPlatformTextAttr($value, $data): string
    {
        $map = ['uniapp' => '移动端', 'pc' => 'PC端'];
        return $map[$data['platform'] ?? ''] ?? '未知';
    }
}
