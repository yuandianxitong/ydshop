<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberFavoriteRepository;
use core\base\Service;
use core\exception\BusinessException;

class MemberFavoriteService extends Service
{
    protected MemberFavoriteRepository $memberFavoriteRepository;

    /**
     * 收藏列表（分页，含 SPU 信息）
     */
    public function getList(int $userId, int $page = 1, int $limit = 15): array
    {
        $result = $this->memberFavoriteRepository->getUserPageList($userId, $page, $limit);
        $total  = $result['total'];

        $items = array_map(function (array $row) {
            $spu = $row['spu'] ?? null;
            $images = (is_array($spu) && !empty($spu['images']))
                ? (is_array($spu['images']) ? $spu['images'] : [])
                : [];

            $row['spu_name']   = is_array($spu) ? (string)($spu['name'] ?? '') : '';
            $row['spu_image']  = !empty($images) ? (string)($images[0] ?? '') : '';
            $row['min_price']  = is_array($spu) ? (float)($spu['min_price'] ?? 0) : 0.0;
            $row['spu_status'] = is_array($spu) ? (string)($spu['status'] ?? '') : '';

            return $row;
        }, $result['list']);

        return [
            'list'       => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 切换收藏状态
     * 已收藏则取消（返回 false），未收藏则添加（返回 true）
     * 使用唯一索引应对并发竞争
     */
    public function toggle(int $userId, int $spuId): bool
    {
        $existing = $this->memberFavoriteRepository->findByUserAndSpu($userId, $spuId);

        if ($existing) {
            $this->memberFavoriteRepository->delete((int)$existing['id']);
            return false;
        }

        try {
            $this->memberFavoriteRepository->create([
                'user_id' => $userId,
                'spu_id'  => $spuId,
            ]);
        } catch (\think\exception\PDOException $e) {
            // 并发情况下唯一索引冲突，视为已收藏
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return true;
            }
            throw new BusinessException('操作失败：' . $e->getMessage());
        }

        return true;
    }

    /**
     * 判断是否已收藏
     */
    public function isFavorited(int $userId, int $spuId): bool
    {
        return $this->memberFavoriteRepository->existsByUserAndSpu($userId, $spuId);
    }
}
