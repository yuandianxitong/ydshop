<?php
declare(strict_types=1);

namespace app\model\plugin;

use core\base\Model;

class MobileChannelConfig extends Model
{
    protected $name = 'mobile_channel_config';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = false;
}
