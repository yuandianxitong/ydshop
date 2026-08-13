<?php
declare(strict_types=1);

namespace app\model\plugin;

use core\base\Model;

class PluginInstallLog extends Model
{
    protected $name = 'plugin_install_logs';
    protected $autoWriteTimestamp = 'datetime';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $updateTime = false;
    protected $deleteTime = false;

    public const ACTION_INSTALL   = 'install';
    public const ACTION_UNINSTALL = 'uninstall';
    public const ACTION_UPGRADE   = 'upgrade';
    public const ACTION_ENABLE    = 'enable';
    public const ACTION_DISABLE   = 'disable';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED  = 'failed';
}
