<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsSpecValue;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsSpecValueRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsSpecValue();
    }

    /**
     * 删除一批 spec_name_id 对应的全部规格值（编辑商品时清理用）
     */
    public function deleteBySpecNameIds(array $specNameIds): int
    {
        if (empty($specNameIds)) {
            return 0;
        }
        return $this->model->whereIn('spec_name_id', $specNameIds)->delete();
    }
}
