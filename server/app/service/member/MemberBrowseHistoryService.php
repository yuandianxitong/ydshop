<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberBrowseHistoryRepository;
use core\base\Service;

class MemberBrowseHistoryService extends Service
{
    protected MemberBrowseHistoryRepository $repository;

    /**
     * 分页列表（含 SPU 信息）
     */
    public function getList(int $userId, int $page = 1, int $limit = 20): array
    {
        $res   = $this->repository->paginateByUser($userId, $page, $limit);
        $items = [];

        foreach ($res['list'] as $row) {
            $arr = $row->toArray();
            $spu = $row->spu;

            $arr['spu_name']   = $spu ? $spu->name : '';
            $arr['spu_image']  = $spu && !empty($spu->images) ? ($spu->images[0] ?? '') : '';
            $arr['min_price']  = $spu ? (float)$spu->min_price : 0.0;
            $arr['spu_status'] = $spu ? $spu->status : '';

            $items[] = $arr;
        }

        $total = (int) $res['total'];
        return [
            'list'       => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int) ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 记录一次浏览（同一 spu 重复浏览只刷新 viewed_at）
     */
    public function record(int $userId, int $spuId): void
    {
        $this->repository->upsertViewedAt($userId, $spuId, date('Y-m-d H:i:s'));
    }

    public function remove(int $id, int $userId): bool
    {
        return $this->repository->deleteByIdForUser($id, $userId);
    }

    public function clear(int $userId): int
    {
        return $this->repository->clearByUser($userId);
    }
}
