<?php
declare(strict_types=1);

namespace app\repository\user;

use app\model\user\UserLoginLog;
use core\base\Repository;
use think\Model as ThinkModel;

class UserLoginLogRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new UserLoginLog();
    }

    /**
     * 用户首次登录时间（生命周期推导用）
     */
    public function findFirstLoginAt(int $userId): ?string
    {
        $val = $this->model->where('user_id', $userId)
            ->order('login_at', 'asc')
            ->value('login_at');
        return $val !== null ? (string)$val : null;
    }
}
