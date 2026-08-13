<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\RechargePackage;
use core\base\Repository;
use think\Model as ThinkModel;

class RechargePackageRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new RechargePackage();
    }

    public function findModel(int $id): ?ThinkModel
    {
        return $this->model->find($id);
    }

    /**
     * 后台分页（status 可选过滤）
     */
    public function getAdminPageList(array $params, int $page = 1, int $limit = 20): array
    {
        $query = $this->model->order('sort asc, id asc');
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }
        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * C 端启用中的套餐列表
     */
    public function getActiveList(): array
    {
        return $this->model->where('status', 1)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();
    }
}
