<?php
declare(strict_types=1);

namespace app\repository\user;

use app\model\user\PointsLog;
use core\base\Model;

class PointsLogRepository extends AbstractLedgerLogRepository
{
    protected function getModel(): Model
    {
        return new PointsLog();
    }

    public function findByUserAndSource(int $userId, string $source): ?array
    {
        $row = $this->model->where('user_id', $userId)
            ->where('source', $source)
            ->order('id', 'asc')
            ->find();
        return $row ? $row->toArray() : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getByUserAndSource(int $userId, string $source): array
    {
        return $this->model->where('user_id', $userId)
            ->where('source', $source)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 锁定同一业务 source 的全部历史流水。
     *
     * 不能预先按 user_id 过滤：升级导入需要把“同 source 被写到其他用户”也识别为
     * provenance 冲突，而不是静默忽略后继续自动冲正。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBySourceForUpdate(string $source): array
    {
        return $this->model->where('source', $source)
            ->order('id', 'asc')
            ->lock(true)
            ->select()
            ->toArray();
    }
}
