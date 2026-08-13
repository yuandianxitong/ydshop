<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsSpecName;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsSpecNameRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsSpecName();
    }

    /**
     * 取某 SPU 下所有规格名的 id 列表（删除前置查询用）
     *
     * @return int[]
     */
    public function getIdsBySpuId(int $spuId): array
    {
        return array_map('intval', $this->model->where('spu_id', $spuId)->column('id'));
    }

    /**
     * 删除某 SPU 下所有规格名
     */
    public function deleteBySpuId(int $spuId): int
    {
        return $this->model->where('spu_id', $spuId)->delete();
    }
}
