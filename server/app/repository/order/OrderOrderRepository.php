<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderOrder;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderOrderRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderOrder();
    }

    /**
     * 单用户订单聚合（用于会员详情概览/订单 Tab 头部 KPI）
     *
     * @return array{
     *   gmv: float,             // 已支付（status != pending/cancelled/closed）的 pay_amount 累计
     *   orders: int,            // 已支付订单数
     *   avg_amount: float,      // 客单价 = gmv/orders
     *   last_order_at: ?string, // 最后一次下单时间
     * }
     */
    public function getStatsByUserId(int $userId): array
    {
        $row = $this->model
            ->field([
                'COUNT(*) AS orders',
                'COALESCE(SUM(pay_amount), 0) AS gmv',
                'MAX(created_at) AS last_order_at',
            ])
            ->where('user_id', $userId)
            ->whereNotIn('status', ['pending', 'cancelled', 'closed'])
            ->find();

        $orders = (int)($row['orders'] ?? 0);
        $gmv    = round((float)($row['gmv'] ?? 0), 2);

        return [
            'gmv'           => $gmv,
            'orders'        => $orders,
            'avg_amount'    => $orders > 0 ? round($gmv / $orders, 2) : 0.0,
            'last_order_at' => $row['last_order_at'] ?? null,
        ];
    }

    /**
     * 按 user_id 批量聚合已完成订单的 GMV（pay_amount 之和），用于分销商列表
     *
     * @param int[] $userIds
     * @return array<int, float> user_id => gmv_total
     */
    public function sumGmvByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $rows = $this->model
            ->field(['user_id', 'SUM(pay_amount) AS gmv_total'])
            ->whereIn('user_id', $userIds)
            ->where('status', 'completed')
            ->group('user_id')
            ->select()
            ->toArray();
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['user_id']] = round((float)$r['gmv_total'], 2);
        }
        return $out;
    }

    /**
     * Order paginated list with optional filters.
     *
     * Supported params: keyword (order_no), user_id, status, start_date, end_date
     */
    public function getPageList(array $params = [], int $page = 1, int $limit = 15): array
    {
        $query = OrderOrder::order('id desc');

        if (!empty($params['keyword'])) {
            $query->where('order_no', 'like', "%{$params['keyword']}%");
        }
        if (!empty($params['user_id'])) {
            $query->where('user_id', (int)$params['user_id']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            // 'refunding' 不是 OrderOrder.status 枚举，是虚拟状态：存在 active 退款记录
            // active 含义同 OrderController::counts: pending / approved / returning / received / refunding
            if ($params['status'] === 'refunding') {
                $query->whereExists(function ($q) {
                    $q->table('order_refunds')
                        ->whereRaw('order_refunds.order_id = order_orders.id')
                        ->whereIn('order_refunds.status', ['pending', 'approved', 'returning', 'received', 'refunding'])
                        ->whereNull('order_refunds.deleted_at');
                });
            } else {
                $query->where('status', $params['status']);
            }
        }
        if (!empty($params['pay_type'])) {
            $query->where('pay_type', $params['pay_type']);
        }
        if (!empty($params['delivery_type'])) {
            $query->where('delivery_type', $params['delivery_type']);
        }
        if (!empty($params['start_date'])) {
            $query->where('created_at', '>=', $params['start_date']);
        }
        if (!empty($params['end_date'])) {
            $query->where('created_at', '<=', $params['end_date']);
        }

        $total = $query->count();
        $list  = $query->with([
                'items',
                'logistics',
                'user' => function ($q) {
                    $q->field('id, nickname, avatar, mobile');
                },
                // 同城配送：admin 列表需要 delivery_order 信息（status/staff）做派单按钮
                'deliveryOrder' => function ($q) {
                    $q->with(['staff' => fn ($s) => $s->field('id, name, phone')]);
                },
            ])
            ->page($page, $limit)
            ->select()
            ->toArray();

        // ThinkPHP toArray 用关联方法名作 key（camelCase），统一转 snake_case 给前端
        $list = array_map([$this, 'normalizeKeys'], $list);

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
     * Order detail with all related data.
     */
    public function getDetail(int $id): ?array
    {
        return $this->getDetailByWhere([['id', '=', $id]]);
    }

    /**
     * 按 id + user_id 取完整订单详情（C 端防越权）。
     */
    public function getDetailByIdAndUser(int $orderId, int $userId): ?array
    {
        return $this->getDetailByWhere([
            ['id', '=', $orderId],
            ['user_id', '=', $userId],
        ]);
    }

    /**
     * 按 order_no + user_id 取完整订单详情（C 端防越权）。
     */
    public function getDetailByOrderNoAndUser(string $orderNo, int $userId): ?array
    {
        return $this->getDetailByWhere([
            ['order_no', '=', $orderNo],
            ['user_id', '=', $userId],
        ]);
    }

    /**
     * 统一加载订单详情关联，避免管理端与 C 端查询结构偏离。
     */
    private function getDetailByWhere(array $where): ?array
    {
        $order = OrderOrder::with([
            'items', 'logistics', 'payments', 'refunds',
            // 同城配送：详情页要展示骑手 + 配送状态
            'deliveryOrder' => fn ($q) => $q->with(['staff' => fn ($s) => $s->field('id, name, phone')]),
        ])->where($where)->find();
        return $order ? $this->normalizeKeys($order->toArray()) : null;
    }

    /**
     * 把 ThinkPHP toArray 输出的 camelCase 关联 key 转 snake_case
     * （ThinkPHP 内部 with()/relation 解析强制 camelCase 方法名，但 toArray 输出
     * 直接用方法名，导致前端拿到 'deliveryOrder' 而非 'delivery_order'）
     */
    private function normalizeKeys(array $row): array
    {
        if (isset($row['deliveryOrder'])) {
            $row['delivery_order'] = $row['deliveryOrder'];
            unset($row['deliveryOrder']);
        }
        return $row;
    }

    /**
     * Find order array by order_no, or null if not found.
     */
    public function findByOrderNo(string $orderNo): ?array
    {
        $order = OrderOrder::where('order_no', $orderNo)->find();
        return $order ? $order->toArray() : null;
    }

    /** 支付结算按业务单号加锁，序列化 query/notify 的消费者重放。 */
    public function findByOrderNoForUpdate(string $orderNo): ?array
    {
        $order = $this->model->where('order_no', $orderNo)->lock(true)->find();
        return $order ? $order->toArray() : null;
    }

    /** 待支付业务订单的原子状态推进。 */
    public function markPaidIfPending(int $orderId, string $channel): int
    {
        return $this->model
            ->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_PENDING)
            ->update([
                'status'         => OrderOrder::STATUS_PAID,
                'pay_type'       => $channel,
                'pay_time'       => date('Y-m-d H:i:s'),
                'auto_cancel_at' => null,
            ]);
    }

    /**
     * 按订单号和用户双重约束查询 C 端业务订单。
     *
     * 支付等敏感入口必须使用此方法，避免仅凭可枚举的订单号访问他人订单。
     */
    public function findByOrderNoAndUser(string $orderNo, int $userId): ?array
    {
        $order = $this->model
            ->where('order_no', $orderNo)
            ->where('user_id', $userId)
            ->find();

        return $order ? $order->toArray() : null;
    }

    /**
     * 加行锁读取订单，用于退款结算时判定“未发货/已发货”库存策略。
     */
    public function findForUpdate(int $orderId): ?array
    {
        $order = $this->model->where('id', $orderId)->lock(true)->find();
        return $order ? $order->toArray() : null;
    }

    /** 订单完成状态 CAS，避免两个完成入口重复推进。 */
    public function markCompletedIfStatus(int $orderId, string $fromStatus): int
    {
        return $this->model->where('id', $orderId)
            ->where('status', $fromStatus)
            ->update([
                'status'       => OrderOrder::STATUS_COMPLETED,
                'receive_time' => date('Y-m-d H:i:s'),
            ]);
    }

    public function markVirtualFulfillmentStatus(
        int $orderId,
        string $status,
        string $error = ''
    ): int {
        return $this->model->where('id', $orderId)
            ->whereIn('status', [OrderOrder::STATUS_PAID, OrderOrder::STATUS_COMPLETED])
            ->update([
                'virtual_fulfillment_status' => $status,
                'virtual_fulfillment_error' => mb_substr($error, 0, 255),
            ]);
    }

    public function markVirtualCompletedIfPaid(int $orderId): int
    {
        return $this->model->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_PAID)
            ->where('virtual_fulfillment_status', 'pending')
            ->update([
                'status' => OrderOrder::STATUS_COMPLETED,
                'receive_time' => date('Y-m-d H:i:s'),
                'virtual_fulfillment_status' => 'completed',
                'virtual_fulfillment_error' => '',
            ]);
    }

    public function markShippedIfPaid(int $orderId): int
    {
        return $this->model->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_PAID)
            ->update([
                'status'    => OrderOrder::STATUS_SHIPPED,
                'ship_time' => date('Y-m-d H:i:s'),
            ]);
    }

    /** 自提核销与订单完成在同一条 CAS 中原子推进。 */
    public function completePickupIfPaid(int $orderId, int $adminId): int
    {
        return $this->model->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_PAID)
            ->where('pickup_status', 'pending')
            ->update([
                'status'             => OrderOrder::STATUS_COMPLETED,
                'receive_time'       => date('Y-m-d H:i:s'),
                'pickup_status'      => 'verified',
                'pickup_at'          => date('Y-m-d H:i:s'),
                'pickup_verified_by' => $adminId,
            ]);
    }

    /**
     * 会员权益补偿按订单主键顺序扫描曾完成的订单。
     * closed + receive_time 非空覆盖“完成后全额退款关闭”的崩溃窗口。
     */
    public function getMemberRewardCandidatesAfterId(int $afterId, int $limit): array
    {
        return $this->model->where('id', '>', $afterId)
            ->whereIn('status', [OrderOrder::STATUS_COMPLETED, OrderOrder::STATUS_CLOSED])
            ->whereNotNull('receive_time')
            ->order('id', 'asc')
            ->limit($limit)
            ->field('id,user_id,status,receive_time')
            ->select()
            ->toArray();
    }

    /**
     * 分销佣金补偿按订单主键游标扫描发布边界后的已完成订单。
     *
     * completed 是正常终态；closed + receive_time 非空覆盖订单完成后又因
     * 全额退款关闭、但完成事件处理曾失败的窗口。调用方会在商品行锁内
     * 重新检查退款状态，因此已退款商品不会被补佣。
     *
     * @return array<int, array{id:int,user_id:int,status:string,receive_time:string}>
     */
    public function getDistributionCommissionCandidatesAfterId(
        string $completedFrom,
        int $afterId,
        int $limit
    ): array {
        return $this->model->where('id', '>', $afterId)
            ->whereIn('status', [OrderOrder::STATUS_COMPLETED, OrderOrder::STATUS_CLOSED])
            ->whereNotNull('receive_time')
            ->where('receive_time', '>=', $completedFrom)
            ->order('id', 'asc')
            ->limit(max(1, $limit))
            ->field('id,user_id,status,receive_time')
            ->select()
            ->toArray();
    }

    /** 自动确认任务的游标查询。 */
    public function getShippedBeforeAfterId(string $deadline, int $afterId, int $limit): array
    {
        return $this->model->where('id', '>', $afterId)
            ->where('status', OrderOrder::STATUS_SHIPPED)
            ->where('ship_time', '<=', $deadline)
            ->order('id', 'asc')
            ->limit($limit)
            ->field('id,user_id,order_no')
            ->select()
            ->toArray();
    }

    /**
     * 自动取消任务按主键游标扫描已超时的待付款订单。
     *
     * 只返回轻量候选信息，不在扫描阶段加锁。真正取消时由
     * OrderService 重新读取并依次执行渠道关单、order/payment 行锁和状态 CAS。
     *
     * @return array<int, array{id:int,user_id:int,order_no:string,auto_cancel_at:string}>
     */
    public function getExpiredPendingAfterId(
        string $deadline,
        int $afterId,
        int $limit
    ): array {
        return $this->model
            ->where('id', '>', max(0, $afterId))
            ->where(function ($query) use ($deadline): void {
                $query->where(function ($pending) use ($deadline): void {
                    $pending->where('status', OrderOrder::STATUS_PENDING)
                        ->whereNotNull('auto_cancel_at')
                        ->where('auto_cancel_at', '<=', $deadline);
                })->whereOr(function ($replay): void {
                    $replay->where('status', OrderOrder::STATUS_CANCELLED)
                        ->whereIn('cancel_effects_status', ['pending', 'failed']);
                });
            })
            ->order('id', 'asc')
            ->limit(min(1000, max(1, $limit)))
            ->field('id,user_id,order_no,auto_cancel_at')
            ->select()
            ->toArray();
    }

    /**
     * 支付单累计全退后的专用终态更新。
     *
     * 全额退款可能发生在待发货、已发货或已完成阶段，不能复用普通取消状态机。
     */
    public function closeAfterFullRefund(int $orderId): bool
    {
        return $this->model
            ->where('id', $orderId)
            ->whereIn('status', ['paid', 'shipped', 'completed'])
            ->update(['status' => OrderOrder::STATUS_CLOSED]) > 0;
    }

    /** 待付款取消 CAS；并发取消中只有一个调用方可恢复库存。 */
    public function cancelIfPending(int $orderId, string $reason): int
    {
        return $this->model
            ->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_PENDING)
            ->update([
                'status' => OrderOrder::STATUS_CANCELLED,
                'cancel_reason' => $reason,
                'cancel_effects_status' => 'pending',
                'cancel_effects_error' => '',
            ]);
    }

    public function markCancelEffects(int $orderId, string $status, string $error = ''): int
    {
        return $this->model
            ->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_CANCELLED)
            ->update([
                'cancel_effects_status' => $status,
                'cancel_effects_error' => mb_substr($error, 0, 255),
            ]);
    }

    /**
     * 批量按主键读取订单（按 id 升序，无锁）。
     *
     * @param int[] $ids
     * @return array<int, array<string, mixed>>
     */
    public function findByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        return $this->model->whereIn('id', $ids)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 支付根家族：根订单自身 + 所有 payment_root_order_id 指向该根的子单。
     *
     * payment_root_order_id 在拆单时一次写入（COALESCE(父.payment_root, 父.id)）
     * 且不再变更，因此无锁读取即可得到稳定家族集合。
     *
     * @return int[] 按 id 升序
     */
    public function findOrderIdsByPaymentRoot(int $rootOrderId): array
    {
        $ids = $this->model
            ->where(function ($query) use ($rootOrderId): void {
                $query->where('id', $rootOrderId)
                    ->whereOr('payment_root_order_id', $rootOrderId);
            })
            ->order('id', 'asc')
            ->column('id');
        return array_map('intval', $ids);
    }

    /**
     * 某订单的直接子单（拆单产物 / 合单被吸收单）。
     *
     * @return array<int, array{id:int, order_no:string, relation_type:string, status:string}>
     */
    public function findChildrenByParentId(int $parentId): array
    {
        return $this->model->where('parent_order_id', $parentId)
            ->order('id', 'asc')
            ->field('id,order_no,relation_type,status')
            ->select()
            ->toArray();
    }

    /**
     * 拆单/改价对待付款订单金额字段的 CAS 更新；status 离开 pending 即失败。
     *
     * @param array<string, mixed> $data 金额及关系字段
     * @return int 影响行数（0 表示状态已变化，调用方必须放弃并重试/报错）
     */
    public function updateAmountsIfPending(int $orderId, array $data): int
    {
        return $this->model
            ->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_PENDING)
            ->update($data);
    }

    /**
     * 合单后存续单金额字段的 CAS 更新；语义同 updateAmountsIfPending。
     *
     * @param array<string, mixed> $data
     * @return int 影响行数
     */
    public function updateMergedAmounts(int $survivorId, array $data): int
    {
        return $this->model
            ->where('id', $survivorId)
            ->where('status', OrderOrder::STATUS_PENDING)
            ->update($data);
    }

    /**
     * 合单吸收：把被合并的待付款订单 CAS 置为 cancelled。
     *
     * 与 cancelIfPending 的关键差异：cancel_effects_status 直接落终态
     * 'completed'。被吸收单的商品行已迁入存续单，库存与优惠券仍被存续单
     * 占用，绝不能被 order:auto-cancel 的副作用重放通道
     * （getExpiredPendingAfterId 只捞 cancelled + effects in pending/failed）
     * 再回补库存或退券；库存回补本身只发生在 cancelPendingInTransaction
     * 的 CAS 赢家事务内，合单路径不经过该函数。
     *
     * @return int 影响行数（0 表示订单已离开 pending，合单必须整体回滚）
     */
    public function cancelForMerge(int $orderId, int $survivorId, string $reason): int
    {
        return $this->model
            ->where('id', $orderId)
            ->where('status', OrderOrder::STATUS_PENDING)
            ->update([
                'status'                => OrderOrder::STATUS_CANCELLED,
                'cancel_reason'         => $reason,
                'parent_order_id'       => $survivorId,
                'relation_type'         => 'merge_absorbed',
                'cancel_effects_status' => 'completed',
                'cancel_effects_error'  => '',
            ]);
    }

    /**
     * Find OrderOrder model instance by order_no, or null if not found.
     */
    public function findModelByOrderNo(string $orderNo): ?OrderOrder
    {
        return OrderOrder::where('order_no', $orderNo)->find() ?: null;
    }

    /**
     * Find OrderOrder model instance by id, or null if not found.
     */
    public function findModel(int $id): ?OrderOrder
    {
        return OrderOrder::find($id) ?: null;
    }

    /**
     * 销售聚合（总订单数 + 总营收，受 status + created_at 范围筛选）
     *
     * @return array{total_orders:int, total_revenue:float}
     */
    public function getSalesAggregateBetween(array $paidStatuses, string $startDate, string $endDate): array
    {
        $row = OrderOrder::whereIn('status', $paidStatuses)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->field('COUNT(*) as total_orders, SUM(pay_amount) as total_revenue')
            ->findOrEmpty()
            ->toArray();
        return [
            'total_orders'  => (int)($row['total_orders'] ?? 0),
            'total_revenue' => round((float)($row['total_revenue'] ?? 0), 2),
        ];
    }

    /**
     * 时间段内订单总数（用于退款率分母）
     */
    public function countCreatedBetween(string $startDate, string $endDate): int
    {
        return OrderOrder::where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->count();
    }

    /**
     * 某门店下 pending 自提单中是否存在指定 pickup_code（自提码唯一性校验）
     */
    public function existsPendingPickupCode(int $storeId, string $pickupCode): bool
    {
        return OrderOrder::where('pickup_store_id', $storeId)
            ->where('pickup_status', 'pending')
            ->where('pickup_code', $pickupCode)
            ->find() !== null;
    }

    /**
     * 加载订单 + items（计算佣金等场景用，比 getDetail 轻量，不带 logistics/payments/refunds）
     */
    public function findWithItems(int $id): ?array
    {
        $order = OrderOrder::with(['items'])->find($id);
        return $order ? $order->toArray() : null;
    }

    /**
     * 按 id + user_id 双约束查找（防越权：用户取消订单时只能取消自己的）
     */
    public function findByIdAndUser(int $orderId, int $userId): ?array
    {
        $order = OrderOrder::where('id', $orderId)->where('user_id', $userId)->find();
        return $order ? $order->toArray() : null;
    }

    /**
     * 按 order_no 查 id（"按 order_no 取详情"前置的轻量映射）
     */
    public function findIdByOrderNo(string $orderNo): ?int
    {
        $id = OrderOrder::where('order_no', $orderNo)->value('id');
        return $id ? (int)$id : null;
    }

    /**
     * 用户各状态订单数统计（"我的页"角标用）
     *
     * @param int      $userId
     * @param string[] $statuses 想要统计的 status 列表
     * @return array<string, int> status => count
     */
    public function countByUserAndStatuses(int $userId, array $statuses): array
    {
        $out = [];
        foreach ($statuses as $status) {
            $out[$status] = $this->model->where('user_id', $userId)
                ->where('status', $status)
                ->count();
        }
        return $out;
    }

    /**
     * Delegate to model static method.
     */
    public function generateOrderNo(): string
    {
        return OrderOrder::generateOrderNo();
    }

    /**
     * 单用户偏好聚合（90 天消费趋势 / TOP 品类 / 支付方式 / 时段热力 / 退款率 / 复购率）
     *
     * 已支付订单 = status NOT IN (pending, cancelled, closed)
     */
    public function getUserPreference(int $userId): array
    {
        $now = date('Y-m-d 23:59:59');
        $start90 = date('Y-m-d 00:00:00', strtotime('-89 days'));

        // 90 天每日 GMV + 订单数
        $trendRows = $this->model
            ->field([
                "DATE(created_at) AS day",
                'COUNT(*) AS orders',
                'COALESCE(SUM(pay_amount), 0) AS gmv',
            ])
            ->where('user_id', $userId)
            ->whereNotIn('status', ['pending', 'cancelled', 'closed'])
            ->whereBetween('created_at', [$start90, $now])
            ->group('day')
            ->order('day', 'asc')
            ->select()
            ->toArray();

        $trendMap = [];
        foreach ($trendRows as $r) {
            $trendMap[$r['day']] = [
                'orders' => (int)$r['orders'],
                'gmv'    => round((float)$r['gmv'], 2),
            ];
        }
        $trend90 = [];
        for ($i = 89; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $trend90[] = [
                'date'   => $day,
                'orders' => $trendMap[$day]['orders'] ?? 0,
                'gmv'    => $trendMap[$day]['gmv']    ?? 0,
            ];
        }

        // 支付方式占比
        $payRows = $this->model
            ->field(['pay_type', 'COUNT(*) AS cnt', 'COALESCE(SUM(pay_amount), 0) AS amt'])
            ->where('user_id', $userId)
            ->whereNotIn('status', ['pending', 'cancelled', 'closed'])
            ->where('pay_type', '<>', '')
            ->group('pay_type')
            ->select()
            ->toArray();

        $payTotal = 0;
        foreach ($payRows as $r) $payTotal += (int)$r['cnt'];
        $payments = [];
        foreach ($payRows as $r) {
            $payments[] = [
                'pay_type' => (string)$r['pay_type'],
                'count'    => (int)$r['cnt'],
                'amount'   => round((float)$r['amt'], 2),
                'percent'  => $payTotal > 0 ? round((int)$r['cnt'] * 100 / $payTotal) : 0,
            ];
        }

        // 24 小时下单热力
        $hourRows = $this->model
            ->field(['HOUR(created_at) AS h', 'COUNT(*) AS cnt'])
            ->where('user_id', $userId)
            ->whereNotIn('status', ['pending', 'cancelled', 'closed'])
            ->group('h')
            ->select()
            ->toArray();
        $hourMap = [];
        foreach ($hourRows as $r) $hourMap[(int)$r['h']] = (int)$r['cnt'];
        $hourHeat = [];
        for ($h = 0; $h < 24; $h++) {
            $hourHeat[] = ['hour' => $h, 'count' => $hourMap[$h] ?? 0];
        }

        // 品类 TOP5（按 order_items 小计聚合）
        $categories = \app\model\order\OrderItem::alias('oi')
            ->leftJoin('order_orders o', 'o.id = oi.order_id')
            ->leftJoin('goods_spu g', 'g.id = oi.spu_id')
            ->leftJoin('goods_categories c', 'c.id = g.category_id')
            ->field([
                'c.id AS category_id',
                'c.name AS category_name',
                'COALESCE(SUM(oi.total_amount), 0) AS gmv',
            ])
            ->where('o.user_id', $userId)
            ->whereNotIn('o.status', ['pending', 'cancelled', 'closed'])
            ->group('c.id')
            ->order('gmv', 'desc')
            ->limit(5)
            ->select()
            ->toArray();
        $catTotal = 0;
        foreach ($categories as $c) $catTotal += (float)$c['gmv'];
        $catList = [];
        foreach ($categories as $c) {
            $catList[] = [
                'category_id'   => (int)($c['category_id'] ?? 0),
                'category_name' => (string)($c['category_name'] ?? '未分类'),
                'gmv'           => round((float)$c['gmv'], 2),
                'percent'       => $catTotal > 0 ? round((float)$c['gmv'] * 100 / $catTotal) : 0,
            ];
        }

        // 退款率（90 天）
        $start90Date = date('Y-m-d', strtotime('-89 days'));
        $totalIn90 = $this->model
            ->where('user_id', $userId)
            ->whereNotIn('status', ['pending', 'cancelled', 'closed'])
            ->where('created_at', '>=', $start90Date)
            ->count();
        $refundedIn90 = 0;
        if ($totalIn90 > 0) {
            $refundedIn90 = (int)\think\facade\Db::name('order_refunds')
                ->alias('r')
                ->leftJoin('order_orders o', 'o.id = r.order_id')
                ->where('o.user_id', $userId)
                ->whereIn('r.status', ['completed', 'approved'])
                ->where('r.created_at', '>=', $start90Date)
                ->count();
        }
        $refundRate = $totalIn90 > 0 ? round($refundedIn90 * 100 / $totalIn90, 1) : 0;

        // 复购率：已完成订单数 ≥ 2 视为复购，rate = (orderCount - 1) / orderCount * 100
        $orderCnt = $this->model
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->count();
        $repurchaseRate = $orderCnt > 0 ? round(max($orderCnt - 1, 0) * 100 / $orderCnt, 1) : 0;

        return [
            'trend90'         => $trend90,
            'payments'        => $payments,
            'hour_heat'       => $hourHeat,
            'categories'      => $catList,
            'refund_rate'     => $refundRate,
            'repurchase_rate' => $repurchaseRate,
        ];
    }

    /**
     * 单用户首单 / 末单时间（生命周期推导用）
     *
     * @return array{first: ?string, last: ?string, completed_count: int}
     */
    public function getUserLifecycleAnchors(int $userId): array
    {
        $row = $this->model
            ->field([
                'MIN(created_at) AS first',
                'MAX(created_at) AS last',
                "SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count",
            ])
            ->where('user_id', $userId)
            ->whereNotIn('status', ['pending', 'cancelled', 'closed'])
            ->find();
        return [
            'first'           => $row['first'] ?? null,
            'last'            => $row['last']  ?? null,
            'completed_count' => (int)($row['completed_count'] ?? 0),
        ];
    }
}
