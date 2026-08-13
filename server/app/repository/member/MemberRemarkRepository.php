<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberRemark;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberRemarkRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberRemark();
    }

    public function listByUserId(int $userId, int $limit = 50): array
    {
        return MemberRemark::where('user_id', $userId)
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    public function countByUserId(int $userId): int
    {
        return MemberRemark::where('user_id', $userId)->count();
    }
}
