<?php
declare(strict_types=1);

namespace app\repository\delivery;

use app\model\delivery\DeliveryShift;
use core\base\Repository;
use think\facade\Db;
use think\Model;

class DeliveryShiftRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DeliveryShift();
    }

    public function getPageList(array $filters, int $page, int $limit): array
    {
        $query = $this->model->newQuery();

        if (!empty($filters['staff_id'])) {
            $query->where('staff_id', (int)$filters['staff_id']);
        }
        if (!empty($filters['weekday'])) {
            $query->where('weekday', (int)$filters['weekday']);
        }

        $total = $query->count();
        $list  = $query->order('staff_id', 'asc')
            ->order('weekday', 'asc')
            ->order('start_time', 'asc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $staffIds = array_column($list, 'staff_id');
        $nameMap  = [];
        if (!empty($staffIds)) {
            $nameMap = Db::name('delivery_staff')
                ->whereIn('id', $staffIds)
                ->column('name', 'id');
        }
        foreach ($list as &$row) {
            $row['staff_name'] = $nameMap[$row['staff_id']] ?? '';
        }
        unset($row);

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max(1, $limit)),
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        $row = DeliveryShift::find($id);
        return $row ? $row->toArray() : null;
    }

    public function updateById(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) >= 0;
    }

    public function softDelete(int $id): bool
    {
        $row = DeliveryShift::find($id);
        if (!$row) {
            return false;
        }
        return (bool)$row->delete();
    }

    /**
     * 业务专用：当前时刻在班 staff_id 列表
     *
     * @param int    $weekday 1..7
     * @param string $time    'HH:MM:SS'
     * @return int[]
     */
    public function getActiveStaffIds(int $weekday, string $time): array
    {
        return $this->model->newQuery()
            ->where('weekday', $weekday)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->distinct(true)
            ->column('staff_id');
    }
}
