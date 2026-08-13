<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberFavorite;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberFavoriteRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberFavorite();
    }

    /**
     * 用户收藏列表（分页，with spu 关联用于拍平展示）
     *
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function getUserPageList(int $userId, int $page = 1, int $limit = 15): array
    {
        $query = $this->model->where('user_id', $userId)
            ->with(['spu'])
            ->order('id', 'desc');

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 用户对某 SPU 的收藏记录
     */
    public function findByUserAndSpu(int $userId, int $spuId): ?array
    {
        $row = $this->model->where('user_id', $userId)
            ->where('spu_id', $spuId)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 是否已收藏
     */
    public function existsByUserAndSpu(int $userId, int $spuId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('spu_id', $spuId)
            ->count() > 0;
    }
}
