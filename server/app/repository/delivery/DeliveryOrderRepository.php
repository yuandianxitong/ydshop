<?php
declare(strict_types=1);

namespace app\repository\delivery;

use app\model\delivery\DeliveryOrder;
use core\base\Repository;
use think\Model;

class DeliveryOrderRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DeliveryOrder();
    }

    public function findForUpdate(int $id): ?array
    {
        $row = $this->model->where('id', $id)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    public function findByOrderId(int $orderId): ?array
    {
        $row = $this->model->where('order_id', $orderId)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 按平台 + 平台订单号定位配送单（三方回调入口）
     */
    public function findByPlatformOrder(string $platform, string $platformOrderId): ?array
    {
        if ($platformOrderId === '') {
            return null;
        }
        $row = $this->model->where('platform', $platform)
            ->where('platform_order_id', $platformOrderId)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 单条详情（含 order/staff 关联）
     */
    public function findWithRelations(int $id): ?array
    {
        $row = $this->model->with(['order' => function ($q) {
            $q->field('id, order_no, pay_amount, status, address_snapshot');
        }, 'staff' => function ($q) {
            $q->field('id, name, phone');
        }])->find($id);
        return $row ? $row->toArray() : null;
    }

    /**
     * 发单抢占：条件 UPDATE 防并发重复发单
     *
     * @return int 影响行数（1=抢占成功，0=状态不允许或已在发单中）
     */
    public function beginDispatch(int $id, string $platform): int
    {
        return $this->model->where('id', $id)
            ->where('status', 'pending')
            ->where('platform_status', '<>', 'dispatching')
            ->update([
                'platform'        => $platform,
                'platform_status' => 'dispatching',
            ]);
    }

    /**
     * 发单成功后写入平台回执
     */
    public function updatePlatformDispatch(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) >= 0;
    }

    /**
     * 发单失败：记录原因、把 platform_status 清回 ''，并把 platform 回退 merchant
     * （status 保持 pending 可重试三方发单，也可走商家配送员分配兜底流程——
     *  三方码残留会触发 updateStatus 的三方中间态守护，导致商家配送死路）
     */
    public function markDispatchFailed(int $id, string $reason): bool
    {
        return $this->model->where('id', $id)->update([
            'dispatch_fail_reason' => mb_substr($reason, 0, 255),
            'platform_status'      => '',
            'platform'             => 'merchant',
        ]) >= 0;
    }

    public function getListWithRelations(array $where, int $page, int $limit): array
    {
        $query = $this->model->where($where);
        $total = $query->count();
        $list = $query->with(['order' => function($q) {
            $q->field('id,order_no,pay_amount,status,address_snapshot');
        }, 'staff' => function($q) {
            $q->field('id,name,phone');
        }])->page($page, $limit)->order('created_at desc')->select()->toArray();

        return [
            'list' => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'last_page' => (int)ceil($total / $limit),
            ],
        ];
    }

    /**
     * 导出所有匹配的配送记录（不分页）
     *
     * 复用 getListWithRelations 的 eager-load 模式（order/staff），limit maxRows+1。
     */
    public function getAllForExport(array $where, int $maxRows): array
    {
        return $this->model->where($where)
            ->with([
                'order' => function ($q) {
                    $q->field('id, order_no, pay_amount, status, address_snapshot');
                },
                'staff' => function ($q) {
                    $q->field('id, name, phone');
                },
            ])
            ->limit($maxRows + 1)
            ->order('created_at', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 实时地图查询：按 status/since 筛选，返回扁平化行
     * 含 from order.address_snapshot 拼出的 address 字符串
     *
     * @return array  每行：id, order_no, address, status, dest_lat, dest_lng
     */
    public function getForMap(array $filters): array
    {
        $query = $this->model->newQuery();

        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }
        if (!empty($filters['since'])) {
            $query->where('created_at', '>=', $filters['since']);
        }

        $rows = $query->with(['order' => function ($q) {
            $q->field('id, order_no, address_snapshot');
        }])
        ->limit(500)
        ->order('id', 'desc')
        ->select()
        ->toArray();

        $out = [];
        foreach ($rows as $r) {
            $snap = $r['order']['address_snapshot'] ?? null;
            $addr = '';
            if (is_array($snap)) {
                $addr = trim(
                    (string)($snap['province'] ?? '') .
                    (string)($snap['city']     ?? '') .
                    (string)($snap['district'] ?? '') .
                    (string)($snap['detail']   ?? '')
                );
            }
            $out[] = [
                'id'        => (int)$r['id'],
                'order_no'  => (string)($r['order']['order_no'] ?? ''),
                'address'   => $addr,
                'status'    => (string)$r['status'],
                'dest_lat'  => $r['dest_lat'] !== null ? (float)$r['dest_lat'] : null,
                'dest_lng'  => $r['dest_lng'] !== null ? (float)$r['dest_lng'] : null,
            ];
        }
        return $out;
    }

    /**
     * 写入坐标缓存
     */
    public function updateGeoCoords(int $id, float $lat, float $lng): bool
    {
        return $this->model->where('id', $id)
            ->update([
                'dest_lat' => $lat,
                'dest_lng' => $lng,
            ]) >= 0;
    }

    /**
     * 订单是否已有 delivery_order（merchant 配送付款后建单的幂等校验）
     */
    public function existsByOrderId(int $orderId): bool
    {
        return $this->model->where('order_id', $orderId)->find() !== null;
    }

    /**
     * 按 order_id 原子 create-once。数据库唯一键负责并发裁决，重复事件返回已有行。
     */
    public function createOnceForOrder(array $data): array
    {
        $orderId = (int)($data['order_id'] ?? 0);
        try {
            return $this->create($data);
        } catch (\Throwable $e) {
            $row = $this->model->where('order_id', $orderId)->find();
            if ($row) {
                return $row->toArray();
            }
            throw $e;
        }
    }

    /**
     * 当前 in-progress 订单数 GROUP BY staff_id
     *
     * @return array<int, int>  staff_id => count
     */
    public function countActivePerStaff(array $staffIds): array
    {
        if (empty($staffIds)) {
            return [];
        }
        $rows = $this->model->whereIn('staff_id', $staffIds)
            ->whereIn('status', ['assigned', 'picking', 'delivering'])
            ->field('staff_id, COUNT(*) as cnt')
            ->group('staff_id')
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['staff_id']] = (int)$r['cnt'];
        }
        return $out;
    }
}
