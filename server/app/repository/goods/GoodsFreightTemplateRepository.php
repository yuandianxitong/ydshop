<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsFreightTemplate;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsFreightTemplateRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsFreightTemplate();
    }

    /**
     * 分页列表
     */
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