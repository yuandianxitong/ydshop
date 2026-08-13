<?php
declare(strict_types=1);

namespace app\repository\delivery;

use app\model\delivery\WaybillTemplate;
use core\base\Repository;
use think\Model as ThinkModel;

class WaybillTemplateRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new WaybillTemplate();
    }

    public function getPageList(array $where = [], int $page = 1, int $limit = 15): array
    {
        $query = $this->model->where($where);
        $total = $query->count();
        $list = $query->page($page, $limit)
            ->order('is_default desc, sort asc, id desc')
            ->select()
            ->toArray();

        return [
            'list' => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max(1, $limit)),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function getEnabledOptions(): array
    {
        return $this->model
            ->where('status', 1)
            ->order('is_default desc, sort asc, id desc')
            ->field('id,name,express_code,express_name,exp_type,exp_type_name,template_size,template_size_label,pay_type,need_pickup,is_default')
            ->select()
            ->toArray();
    }

    /** 清除默认标记；excludeId 用于更新时保留当前行。 */
    public function clearDefault(?int $excludeId = null): int
    {
        $query = $this->model->where('is_default', 1);
        if ($excludeId !== null && $excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        return (int)$query->update(['is_default' => 0]);
    }
}
