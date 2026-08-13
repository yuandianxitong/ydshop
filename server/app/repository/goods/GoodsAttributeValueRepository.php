<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsAttributeValue;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsAttributeValueRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsAttributeValue();
    }

    /**
     * 删除某 SPU 下所有自定义属性值
     */
    public function deleteBySpuId(int $spuId): int
    {
        return $this->model->where('spu_id', $spuId)->delete();
    }
}
