<?php

declare(strict_types=1);

namespace app\repository\user;

use app\model\user\PointsDebtLog;
use core\base\Repository;
use think\Model;

class PointsDebtLogRepository extends Repository
{
    protected function getModel(): Model
    {
        return new PointsDebtLog();
    }

    public function existsByEventKey(string $eventKey): bool
    {
        return $eventKey !== '' && $this->model->where('event_key', $eventKey)->count() > 0;
    }
}
