<?php
declare(strict_types=1);

namespace app\model\user;

use core\base\Model;

class UserLoginLog extends Model
{
    protected $name = 'user_login_logs';

    protected $autoWriteTimestamp = false;

    protected $updateTime = false;

    protected $deleteTime = false;
}
