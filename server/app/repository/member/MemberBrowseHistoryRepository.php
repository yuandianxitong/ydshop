<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberBrowseHistory;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberBrowseHistoryRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberBrowseHistory();
    }

    /**
     * 分页查询用户浏览记录（按 viewed_at 倒序，含 SPU 关联）
     */
    public function paginateByUser(int $userId, int $page, int $limit): array
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->with(['spu'])
            ->order('viewed_at', 'desc');

        $total = $query->count();
        $list  = $query->page($page, $limit)->select();

        return [
            'list'  => $list,
            'total' => $total,
        ];
    }

    /**
     * 取或建一条记录（用于"记录浏览"语义）
     * 已存在则刷新 viewed_at，不存在则新建
     */
    public function upsertViewedAt(int $userId, int $spuId, string $viewedAt): void
    {
        $existing = $this->model
            ->where('user_id', $userId)
            ->where('spu_id', $spuId)
            ->find();

        if ($existing) {
            $existing->viewed_at = $viewedAt;
            $existing->save();
            return;
        }

        $this->model->create([
            'user_id'   => $userId,
            'spu_id'    => $spuId,
            'viewed_at' => $viewedAt,
        ]);
    }

    public function deleteByIdForUser(int $id, int $userId): bool
    {
        $row = $this->model->where('id', $id)->where('user_id', $userId)->find();
        if (!$row) return false;
        return (bool) $row->delete();
    }

    public function clearByUser(int $userId): int
    {
        return $this->model->where('user_id', $userId)->delete();
    }
}
