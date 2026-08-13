<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberGrowthLog;
use core\base\Repository;
use think\Model;

class MemberGrowthLogRepository extends Repository
{
    protected function getModel(): Model
    {
        return new MemberGrowthLog();
    }

    public function existsBySource(int $userId, string $source): bool
    {
        return $source !== '' && $this->model
            ->where('user_id', $userId)
            ->where('source', $source)
            ->count() > 0;
    }

    /** @param string[] $sources @return array<int, array<string,mixed>> */
    public function findBySourcesForUpdate(int $userId, array $sources): array
    {
        $sources = array_values(array_unique(array_filter(array_map('trim', $sources))));
        if ($sources === []) {
            return [];
        }
        return $this->model
            ->where('user_id', $userId)
            ->whereIn('source', $sources)
            ->order('id', 'asc')
            ->lock(true)
            ->select()
            ->toArray();
    }
}
