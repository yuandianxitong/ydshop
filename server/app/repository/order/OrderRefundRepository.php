<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderRefund;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderRefundRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderRefund();
    }

    /**
     * Refund paginated list with optional filters.
     *
     * Supported params: keyword/refund_no（售后单号或原订单号）, status, type
     */
    public function getPageList(array $params = [], int $page = 1, int $limit = 15): array
    {
        $query = OrderRefund::alias('r')
            ->leftJoin('order_orders o', 'o.id = r.order_id')
            ->field('r.*')
            ->order('r.id', 'desc');

        $keyword = trim((string)($params['keyword'] ?? $params['refund_no'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('r.refund_no', 'like', "%{$keyword}%")
                    ->whereOr('o.order_no', 'like', "%{$keyword}%");
            });
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('r.status', $params['status']);
        }
        if (isset($params['type']) && $params['type'] !== '') {
            $query->where('r.type', $params['type']);
        }

        $total = $query->count();
        $list  = $query->with([
                'order'     => fn ($q) => $q->field('id, order_no, user_id'),
                'orderItem',
                'user'      => fn ($q) => $q->field('id, nickname, avatar, mobile'),
            ])
            ->page($page, $limit)
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
     * C 端「我的售后」分页列表：与 getPageList 相同的 join / 字段 / 筛选，
     * 额外用订单 user_id 约束，防止查询到他人退款单。
     *
     * Supported params: keyword/refund_no（售后单号或原订单号）, status, type
     */
    public function getPageListByUserId(int $userId, array $params, int $page, int $limit): array
    {
        $query = OrderRefund::alias('r')
            ->leftJoin('order_orders o', 'o.id = r.order_id')
            ->where('o.user_id', $userId)
            ->field('r.*')
            ->order('r.id', 'desc');

        $keyword = trim((string)($params['keyword'] ?? $params['refund_no'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('r.refund_no', 'like', "%{$keyword}%")
                    ->whereOr('o.order_no', 'like', "%{$keyword}%");
            });
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('r.status', $params['status']);
        }
        if (isset($params['type']) && $params['type'] !== '') {
            $query->where('r.type', $params['type']);
        }

        $total = $query->count();
        $list  = $query->with([
                'order'     => fn ($q) => $q->field('id, order_no, user_id'),
                'orderItem',
            ])
            ->page($page, $limit)
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
     * Refund detail with nested order items and the specific order item.
     */
    public function getDetail(int $id): ?array
    {
        $refund = OrderRefund::with(['order.items', 'orderItem'])->find($id);
        return $refund ? $refund->toArray() : null;
    }

    /**
     * 用户当前活跃的退款数（含退款失败待重试/人工复核）
     * —— "我的页"售后角标用
     */
    public function countActiveByUserId(int $userId): int
    {
        return $this->model->alias('r')
            ->join('order_orders o', 'o.id = r.order_id')
            ->where('o.user_id', $userId)
            ->whereIn('r.status', [
                'pending', 'approved', 'returning', 'received', 'refunding',
                'retryable_failed', 'manual_review',
            ])
            ->count();
    }

    /**
     * 某 OrderItem 是否存在活跃退款（非 rejected / refunded）
     */
    public function findActiveByItemId(int $orderItemId): ?array
    {
        $row = $this->model->where('order_item_id', $orderItemId)
            ->whereNotIn('status', ['rejected', 'refunded'])
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 加行锁读取售后单，供审核、退款预占和退款结算使用。
     */
    public function findForUpdate(int $refundId): ?array
    {
        $row = $this->model->where('id', $refundId)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 订单是否存在活跃退款（active 状态集合与 findActiveByItemId 一致：
     * 非 rejected / refunded 都算活跃）。拆单/改价前置校验用。
     */
    public function hasActiveByOrderId(int $orderId): bool
    {
        return $this->model->where('order_id', $orderId)
            ->whereNotIn('status', ['rejected', 'refunded'])
            ->count() > 0;
    }

    /**
     * 订单已预占的退款金额。
     *
     * 除 rejected 外的状态都占用支付单可退额度：申请一旦创建就预占，
     * 避免两个商品同时申请退款时累计金额超过整单实付。
     */
    public function sumReservedByOrderId(int $orderId): float
    {
        return $this->sumReservedByOrderIds([$orderId]);
    }

    /**
     * 家族版预占金额聚合：拆单后同一支付单被多个订单共享，额度校验必须
     * 覆盖支付根家族全部订单。
     *
     * @param int[] $orderIds
     */
    public function sumReservedByOrderIds(array $orderIds): float
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if ($orderIds === []) {
            return 0.0;
        }
        return round((float)$this->model
            ->whereIn('order_id', $orderIds)
            ->whereNotIn('status', ['rejected'])
            ->sum('refund_amount'), 2);
    }

    /**
     * 订单已经由支付渠道成功退回的金额。
     */
    public function sumRefundedByOrderId(int $orderId): float
    {
        return $this->sumRefundedByOrderIds([$orderId]);
    }

    /**
     * 家族版已退金额聚合，语义同 sumReservedByOrderIds。
     *
     * @param int[] $orderIds
     */
    public function sumRefundedByOrderIds(array $orderIds): float
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if ($orderIds === []) {
            return 0.0;
        }
        return round((float)$this->model
            ->whereIn('order_id', $orderIds)
            ->where('status', 'refunded')
            ->sum('refund_amount'), 2);
    }

    /**
     * 读取订单所有已成功退款，用于重建“完成时”的奖励商品快照。
     */
    public function getRefundedByOrderId(int $orderId): array
    {
        return $this->model->where('order_id', $orderId)
            ->where('status', 'refunded')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 补偿任务按主键游标顺序重放已退款事件。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRefundedEventsAfterId(int $afterId, int $limit): array
    {
        $rows = $this->model->where('status', 'refunded')
            ->where('id', '>', $afterId)
            ->order('id', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();

        return array_map(static fn (array $row): array => [
            'refund_id'       => (int)$row['id'],
            'refund_no'       => (string)($row['refund_no'] ?? ''),
            'order_id'        => (int)$row['order_id'],
            'order_item_id'   => (int)$row['order_item_id'],
            'user_id'         => (int)$row['user_id'],
            'type'            => (string)($row['type'] ?? ''),
            'refund_amount'   => (float)($row['refund_amount'] ?? 0),
            'refunded_at'     => (string)($row['refunded_at'] ?? $row['updated_at'] ?? ''),
        ], $rows);
    }

    /**
     * 按主键游标读取超过静默窗口、仍等待渠道最终结果的退款。查询时间使用最近一次
     * provider 检查，其次发起时间；历史记录没有新字段时回退 updated_at/created_at。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStaleRefundingAfterId(
        int $afterId,
        int $limit,
        string $staleBefore
    ): array {
        $limit = min(1000, max(1, $limit));

        return $this->model
            ->where('status', 'refunding')
            ->where('id', '>', max(0, $afterId))
            ->whereRaw(
                'COALESCE(provider_checked_at, provider_requested_at, updated_at, created_at) <= ?',
                [$staleBefore]
            )
            ->order('id', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 按 id + user_id 双约束查找（用户提交退货物流时防越权）
     */
    public function findByIdAndUser(int $refundId, int $userId): ?array
    {
        $row = $this->model->where('id', $refundId)
            ->where('user_id', $userId)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 时间段内（按订单创建时间）退款单数
     */
    public function countByOrderCreatedBetween(string $startDate, string $endDate): int
    {
        return $this->model->alias('r')
            ->join('order_orders o', 'o.id = r.order_id')
            ->where('o.created_at', '>=', $startDate)
            ->where('o.created_at', '<=', $endDate)
            ->count();
    }

    /**
     * 生成退款单号：RF + 秒级时间 + 48 bit 密码学随机数。
     *
     * refund_no 同时是支付渠道幂等键，不再使用仅 4 位的 mt_rand，
     * 避免高并发同秒申请发生碰撞。
     */
    public function generateRefundNo(): string
    {
        return 'RF' . date('YmdHis') . strtoupper(bin2hex(random_bytes(6)));
    }
}
