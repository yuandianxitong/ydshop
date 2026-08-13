<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderReview;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderReviewRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderReview();
    }

    /**
     * 商品评价列表（C 端，按 SPU 倒序分页）
     */
    public function getListBySpu(int $spuId, int $page = 1, int $limit = 15): array
    {
        $query = $this->model->where('spu_id', $spuId)->order('id', 'desc');
        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total];
    }

    /**
     * 管理端评价列表（支持 spu_id / rating 过滤），with orderItem
     */
    public function getAdminPageList(array $params, int $page = 1, int $limit = 15): array
    {
        $query = $this->model->order('id', 'desc');
        if (!empty($params['spu_id'])) {
            $query->where('spu_id', (int)$params['spu_id']);
        }
        if (isset($params['rating']) && $params['rating'] !== '') {
            $query->where('rating', (int)$params['rating']);
        }

        $total = $query->count();
        $list  = $query->with(['orderItem'])->page($page, $limit)->select()->toArray();
        return $this->buildPagination($list, $page, $limit, $total);
    }
}
