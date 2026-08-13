<?php
declare(strict_types=1);

namespace app\repository\diy;

use core\base\Model;
use core\base\Repository;
use app\model\diy\DiyTheme;

class DiyTemplateRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DiyTheme(); // 表仍是 diy_themes
    }

    public function getList(array $where = [], int $page = 1, int $limit = 15, string $order = 'sort asc, id asc'): array
    {
        $query = $this->model->where('deleted_at', null);
        if (!empty($where['platform'])) {
            $query->where('platform', $where['platform']);
        }
        if (!empty($where['page_type'])) {
            $query->where('page_type', $where['page_type']);
        }
        $total = $query->count();
        $list = $query->order($order)
            ->page($page, $limit)
            ->select()->toArray();
        return [
            'list' => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $limit),
            ]
        ];
    }
}
