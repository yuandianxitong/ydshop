<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderInvoice;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderInvoiceRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderInvoice();
    }

    public function getPageList(array $filters = [], int $page = 1, int $limit = 15): array
    {
        $query = $this->model;

        if (!empty($filters['status'])) {
            $query = $query->where('status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $query = $query->where('type', $filters['type']);
        }
        if (!empty($filters['order_no'])) {
            $query = $query->where('order_no', 'like', '%' . $filters['order_no'] . '%');
        }
        if (!empty($filters['title'])) {
            $query = $query->where('title', 'like', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['start_date'])) {
            $query = $query->where('created_at', '>=', $filters['start_date'] . ' 00:00:00');
        }
        if (!empty($filters['end_date'])) {
            $query = $query->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
        }

        $total = $query->count();
        $list = $query->page($page, $limit)
            ->order('id desc')
            ->select()
            ->toArray();

        return [
            'list' => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $limit),
            ],
        ];
    }

    public function countByStatus(): array
    {
        $rows = $this->model
            ->field('status, count(*) as cnt')
            ->group('status')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $r) {
            $map[$r['status']] = (int)$r['cnt'];
        }
        return $map;
    }

    /**
     * 按订单 ID 查发票（一单一票）
     */
    public function findByOrderId(int $orderId): ?array
    {
        $row = OrderInvoice::where('order_id', $orderId)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 用户发票分页
     */
    public function paginateByUser(int $userId, int $page, int $limit): array
    {
        $query = OrderInvoice::where('user_id', $userId);
        $total = $query->count();
        $list  = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }
}
