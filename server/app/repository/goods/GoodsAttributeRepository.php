<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsAttribute;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsAttributeRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsAttribute();
    }

    /**
     * 分页列表
     */
    /**
     * 取某分组下所有属性（按 sort 升序），用于"分组+属性"层级展示
     */
    public function findByGroupId(int $groupId): array
    {
        return $this->model->where('group_id', $groupId)
            ->order('sort asc')
            ->select()
            ->toArray();
    }

    public function getPageList(array $where = [], int $page = 1, int $limit = 15): array
    {
        $query = $this->model->where($where);

        $total = $query->count();
        $list = $query->page($page, $limit)
            ->order('id desc')
            ->select()
            ->toArray();

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $limit),
            ],
        ];
    }
}