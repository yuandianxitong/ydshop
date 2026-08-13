<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsComboItem;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsComboItemRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsComboItem();
    }

    /**
     * 删除某套餐 SPU 下的全部组合项（编辑套餐时清理）
     */
    public function deleteByComboSpuId(int $comboSpuId): int
    {
        return $this->model->where('combo_spu_id', $comboSpuId)->delete();
    }
}
