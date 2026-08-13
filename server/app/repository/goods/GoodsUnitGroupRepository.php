<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsUnitGroup;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsUnitGroupRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsUnitGroup();
    }

    /**
     * 列表（带每个分组的单位数）
     */
    public function getListWithCount(): array
    {
        $list = $this->model
            ->withCount(['units' => function ($q) {
                $q->where('status', 1);
            }])
            ->order('sort asc, id asc')
            ->select()
            ->toArray();
        return $list;
    }
}
