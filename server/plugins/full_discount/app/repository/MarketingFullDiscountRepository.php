<?php
declare(strict_types=1);

namespace plugins\full_discount\repository;

use core\base\Repository;
use plugins\full_discount\model\MarketingFullDiscount;
use think\Model as ThinkModel;

class MarketingFullDiscountRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MarketingFullDiscount();
    }

    /**
     * 后台分页列表（status + keyword 过滤）
     */
    public function getAdminPageList(array $params, int $page = 1, int $limit = 15): array
    {
        $query = $this->model->order('id', 'desc');

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['keyword'])) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 取所有当前有效活动（status=1 且当前在 start_at~end_at 之间）
     */
    public function getActiveNow(): array
    {
        $now = date('Y-m-d H:i:s');
        return $this->model->where('status', 1)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 取模型实例（save() 风格的更新；create/update 用基类）
     */
    public function findModel(int $id): ?ThinkModel
    {
        return $this->model->find($id);
    }
}
