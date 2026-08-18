<?php
declare(strict_types=1);

namespace app\model\plugin;

use core\base\Model;

class MobileBuild extends Model
{
    protected $name = 'mobile_builds';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;
    protected $deleteTime = false;

    public const STATUS_QUEUED   = 0;
    public const STATUS_RUNNING  = 1;
    public const STATUS_SUCCESS  = 2;
    public const STATUS_FAILED   = 3;
    public const STATUS_UPLOADED = 4;
    public const STATUS_SKIPPED  = 5;

    public const PLATFORM_H5        = 'h5';
    public const PLATFORM_MP_WEIXIN = 'mp-weixin';
}
