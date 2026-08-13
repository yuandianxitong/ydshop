<?php
declare(strict_types=1);

namespace app\repository\delivery;

use app\model\delivery\DeliveryExceptionTicket;
use core\base\Repository;
use think\Model;

class DeliveryExceptionTicketRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DeliveryExceptionTicket();
    }

    public function getPageList(array $filters, int $page, int $limit): array
    {
        $query = $this->model->newQuery();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['delivery_order_id'])) {
            $query->where('delivery_order_id', (int)$filters['delivery_order_id']);
        }
        if (!empty($filters['keyword'])) {
            $kw = (string)$filters['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->whereLike('ticket_no', "%{$kw}%")
                  ->whereOr('title', 'like', "%{$kw}%")
                  ->whereOr('description', 'like', "%{$kw}%");
            });
        }

        $total = $query->count();
        $list  = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 附加 handled_by_name
        $adminIds = array_filter(array_column($list, 'handled_by'));
        $adminMap = [];
        if (!empty($adminIds)) {
            $adminMap = \think\facade\Db::name('admins')
                ->whereIn('id', $adminIds)
                ->column('name', 'id');
        }
        foreach ($list as &$row) {
            $row['handled_by_name'] = $row['handled_by'] && isset($adminMap[$row['handled_by']])
                ? $adminMap[$row['handled_by']]
                : null;
        }
        unset($row);

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max(1, $limit)),
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        $row = DeliveryExceptionTicket::find($id);
        if (!$row) {
            return null;
        }
        $data = $row->toArray();
        if (!empty($data['handled_by'])) {
            $name = \think\facade\Db::name('admins')->where('id', $data['handled_by'])->value('name');
            $data['handled_by_name'] = $name ?: null;
        } else {
            $data['handled_by_name'] = null;
        }
        return $data;
    }

    public function updateById(int $id, array $data): bool
    {
        return DeliveryExceptionTicket::where('id', $id)->update($data) >= 0;
    }

    public function softDelete(int $id): bool
    {
        $row = DeliveryExceptionTicket::find($id);
        if (!$row) {
            return false;
        }
        return (bool)$row->delete();
    }

    public function generateTicketNo(): string
    {
        $prefix = 'EX' . date('Ymd');
        $like   = $prefix . '%';
        $maxNo  = DeliveryExceptionTicket::whereLike('ticket_no', $like)
            ->order('ticket_no', 'desc')
            ->value('ticket_no');
        $seq    = 1;
        if ($maxNo) {
            $seq = (int)substr($maxNo, -4) + 1;
        }
        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
