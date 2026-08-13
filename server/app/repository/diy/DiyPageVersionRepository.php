<?php
declare(strict_types=1);

namespace app\repository\diy;

use core\base\Model;
use core\base\Repository;
use app\model\diy\DiyPageVersion;

class DiyPageVersionRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DiyPageVersion();
    }

    public function listByPage(int $pageId): array
    {
        return $this->model
            ->where('page_id', $pageId)
            ->order('version_no desc')
            ->select()
            ->toArray();
    }

    public function findInPage(int $pageId, int $versionId): ?array
    {
        $row = $this->model
            ->where('page_id', $pageId)
            ->where('id', $versionId)
            ->find();
        return $row ? $row->toArray() : null;
    }

    public function getNextVersionNo(int $pageId): int
    {
        $max = $this->model
            ->where('page_id', $pageId)
            ->max('version_no');
        return (int) $max + 1;
    }

    public function countByPage(int $pageId): int
    {
        return $this->model->where('page_id', $pageId)->count();
    }

    public function deleteOldest(int $pageId, int $keepLatest): int
    {
        $count = $this->countByPage($pageId);
        if ($count <= $keepLatest) {
            return 0;
        }
        $toDelete = $count - $keepLatest;
        $rows = $this->model
            ->where('page_id', $pageId)
            ->order('created_at asc, id asc')
            ->limit($toDelete)
            ->select();
        $deleted = 0;
        foreach ($rows as $row) {
            $row->delete();
            $deleted++;
        }
        return $deleted;
    }
}
