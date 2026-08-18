<?php
declare(strict_types=1);

namespace app\model\plugin;

use core\base\Model;

class PluginBuild extends Model
{
    protected $name = 'plugin_builds';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;
    protected $deleteTime = false;

    public const STATUS_QUEUED  = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAILED  = 3;
    public const STATUS_SKIPPED = 5;

    public const TARGET_ADMIN = 'admin';
    public const TARGET_PC    = 'pc';
}
