<?php
declare(strict_types=1);

namespace app\repository\delivery;

use app\model\delivery\DeliveryStaff;
use core\base\Repository;
use think\Model;

class DeliveryStaffRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DeliveryStaff();
    }

    /**
     * 导出所有匹配的配送员（不分页）
     */
    public function getAllForExport(array $where, int $maxRows): array
    {
        return $this->model->where($where)
            ->limit($maxRows + 1)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 按 ID 数组筛选 status=1（在岗）的 staff
     *
     * @return array<array{id: int, name: string, status: int}>
     */
    public function findActiveByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->model->whereIn('id', $ids)
            ->where('status', 1)
            ->field('id, name, status')
            ->select()
            ->toArray();
    }
}
