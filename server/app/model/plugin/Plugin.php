<?php
declare(strict_types=1);

namespace app\model\plugin;

use core\base\Model;

class Plugin extends Model
{
    protected $name = 'plugins';
    protected $pk = 'code';

    protected $type = [
        'palette'     => 'json',
        'manifest'    => 'json',
        'recommended' => 'integer',
    ];

    protected $autoWriteTimestamp = 'datetime';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $deleteTime = false;

    public const STATUS_INSTALLED = 'installed';
    public const STATUS_DISABLED  = 'disabled';

    public const SOURCE_BUNDLED    = 'bundled';
    public const SOURCE_DOWNLOADED = 'downloaded';
}
