<?php
declare(strict_types=1);

namespace app\model\plugin;

use core\base\Model;

class PluginMigration extends Model
{
    protected $name = 'plugin_migrations';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $deleteTime = false;
}
