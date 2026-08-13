<?php
declare(strict_types=1);

namespace app\model\diy;

use core\base\Model;

class DiyTheme extends Model
{
    protected $table = 'diy_themes';

    protected $fillable = [
        'name', 'cover', 'platform', 'page_type', 'components', 'is_system', 'sort', 'status'
    ];

    protected $type = [
        'is_system' => 'integer',
        'sort'      => 'integer',
        'status'    => 'integer',
    ];

    protected $json = ['components'];
    protected $jsonAssoc = true;

    protected $append = ['platform_text', 'page_type_text'];

    public function getPlatformTextAttr($value, $data): string
    {
        $map = ['uniapp' => '移动端', 'pc' => 'PC端'];
        return $map[$data['platform'] ?? ''] ?? '未知';
    }

    public function getPageTypeTextAttr($value, $data): string
    {
        $map = ['home' => '首页', 'category' => '分类页'];
        return $map[$data['page_type'] ?? ''] ?? '未知';
    }
}
