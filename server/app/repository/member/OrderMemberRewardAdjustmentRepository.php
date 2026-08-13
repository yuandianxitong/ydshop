<?php

declare(strict_types=1);

namespace app\repository\member;

use app\model\member\OrderMemberRewardAdjustment;
use core\base\Repository;
use think\Model;

class OrderMemberRewardAdjustmentRepository extends Repository
{
    protected function getModel(): Model
    {
        return new OrderMemberRewardAdjustment();
    }

    public function findByEventKey(string $eventKey): ?array
    {
        $row = $this->model->where('event_key', $eventKey)->find();
        return $row ? $row->toArray() : null;
    }
}
