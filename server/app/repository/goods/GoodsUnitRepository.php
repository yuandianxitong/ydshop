<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsUnit;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsUnitRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsUnit();
    }

    /**
     * 分页列表（带分组信息）
     */
    public function getPageList(array $where = [], int $page = 1, int $limit = 15, array $extra = []): array
    {
        $query = $this->model->with(['group']);

        if (!empty($where)) {
            $query = $query->where($where);
        }

        if (!empty($extra['keyword'])) {
            $kw = '%' . $extra['keyword'] . '%';
            $query = $query->where(function ($q) use ($kw) {
                $q->whereLike('name', $kw)
                  ->whereOr('code', 'like', $kw)
                  ->whereOr('name_en', 'like', $kw);
            });
        }
        if (!empty($extra['group_id'])) {
            $query = $query->where('group_id', (int)$extra['group_id']);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)
            ->order('group_id asc, is_base desc, sort asc, id asc')
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

    /**
     * 通过 ID 列表读取（用于换算关系合并）
     */
    public function getByIds(array $ids): array
    {
        if (!$ids) return [];
        return $this->model->whereIn('id', $ids)->select()->toArray();
    }

    /**
     * 当前分组下的基准单位
     */
    public function findBaseInGroup(int $groupId): ?array
    {
        $row = $this->model->where('group_id', $groupId)->where('is_base', 1)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 清除分组下其他单位的"基准"标记
     */
    public function clearBaseInGroup(int $groupId, int $exceptId = 0): int
    {
        $query = $this->model->where('group_id', $groupId)->where('is_base', 1);
        if ($exceptId > 0) {
            $query = $query->where('id', '<>', $exceptId);
        }
        return (int)$query->update(['is_base' => 0]);
    }

    /**
     * 单位被引用的商品数（goods_spu.unit_id）
     */
    public function countLinkedSpu(array $unitIds): array
    {
        if (!$unitIds) return [];
        $rows = \think\facade\Db::table('goods_spu')
            ->where('deleted_at', null)
            ->whereIn('unit_id', $unitIds)
            ->field('unit_id, count(*) as cnt')
            ->group('unit_id')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['unit_id']] = (int)$r['cnt'];
        }
        return $map;
    }
}
