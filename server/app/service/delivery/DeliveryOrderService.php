<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\delivery\DeliveryOrderRepository;
use app\repository\delivery\DeliveryOrderTrackRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\order\OrderItemRepository;
use app\repository\system\SystemConfigRepository;
use app\model\order\OrderOrder;
use app\service\common\ExcelExportService;
use app\service\delivery\platform\DeliveryDispatchRequest;
use app\service\delivery\platform\DeliveryPlatformRegistry;
use core\base\Service;
use core\exception\BusinessException;
use think\facade\Log;

class DeliveryOrderService extends Service
{
    protected DeliveryOrderRepository $deliveryOrderRepo;
    protected ExcelExportService $excelExportService;
    protected \app\repository\delivery\DeliveryShiftRepository $deliveryShiftRepository;
    protected \app\repository\delivery\DeliveryStaffRepository $deliveryStaffRepository;
    protected OrderOrderRepository $orderOrderRepository;
    protected OrderItemRepository $orderItemRepository;
    protected DeliveryPlatformRegistry $deliveryPlatformRegistry;
    protected DeliveryOrderTrackRepository $deliveryOrderTrackRepository;
    protected SystemConfigRepository $systemConfigRepository;

    public function getList(array $params): array
    {
        $where = [];
        if (!empty($params['status'])) {
            $where[] = ['status', '=', $params['status']];
        }
        if (!empty($params['platform'])) {
            $where[] = ['platform', '=', (string)$params['platform']];
        }
        if (!empty($params['keyword'])) {
            // Search by order_id or staff name would require join, keep simple for now
            $where[] = ['order_id', '=', (int)$params['keyword']];
        }
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->deliveryOrderRepo->getListWithRelations($where, $page, $limit);
    }

    public function create(array $data): array
    {
        return $this->deliveryOrderRepo->create([
            'order_id'  => $data['order_id'],
            // 当前内置实现仅支持商家配送；第三方平台未接入前不写入伪平台订单。
            'platform'  => 'merchant',
            'fee'       => $data['fee'] ?? 0,
            'distance'  => $data['distance'] ?? 0,
            'remark'    => $data['remark'] ?? '',
            'status'    => 'pending',
        ]);
    }

    public function assign(int $id, int $staffId): bool
    {
        $record = $this->deliveryOrderRepo->find($id);
        if (!$record) {
            throw new BusinessException('配送记录不存在');
        }
        $ok = $this->deliveryOrderRepo->update($id, [
            'staff_id'    => $staffId,
            'status'      => 'assigned',
            'assigned_at' => date('Y-m-d H:i:s'),
        ]);
        // 派单 = 商家已交骑手，对应 order_orders 状态推进到 shipped（待收货）
        // 否则 uniapp 端用户会一直看到"待发货"
        $this->advanceOrderToShipped((int)($record['order_id'] ?? 0));
        return $ok;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if ($status === 'completed') {
            // completeDelivery 自带 findForUpdate 与幂等校验
            return $this->completeDelivery($id);
        }
        $record = $this->deliveryOrderRepo->find($id);
        if (!$record) {
            throw new BusinessException('配送记录不存在');
        }
        $platform = (string)($record['platform'] ?? 'merchant');

        // 三方配送单的中间态由平台回调驱动，禁止手工推进
        if ($platform !== 'merchant'
            && in_array($status, ['assigned', 'picking', 'picked', 'delivering'], true)) {
            throw new BusinessException('第三方配送单状态由平台回调驱动，请使用「同步状态」');
        }

        // 商家主动取消三方单：先取消平台订单，失败则中断（主动取消是终态）
        if ($status === 'cancelled' && $platform !== 'merchant' && !empty($record['platform_order_id'])) {
            $adapter = $this->deliveryPlatformRegistry->resolve($platform);
            $result  = $adapter->cancelOrder((string)$record['platform_order_id'], '商家主动取消');
            if (!$result->success) {
                throw new BusinessException('平台取消失败：' . $result->errorMessage);
            }
        }

        $data = ['status' => $status];
        if ($status === 'picking') {
            $data['picked_at'] = date('Y-m-d H:i:s');
        }
        return $this->deliveryOrderRepo->update($id, $data);
    }

    private function advanceOrderToShipped(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }
        $this->runInTransaction(function () use ($orderId): void {
            $order = $this->orderOrderRepository->findForUpdate($orderId);
            if (!$order || in_array((string)$order['status'], [
                OrderOrder::STATUS_SHIPPED,
                OrderOrder::STATUS_COMPLETED,
            ], true)) {
                return;
            }
            if ((string)$order['status'] !== OrderOrder::STATUS_PAID
                || $this->orderOrderRepository->markShippedIfPaid($orderId) !== 1) {
                throw new BusinessException('业务订单状态不允许派送');
            }
        });
    }

    /** 同城配送完成与业务订单完成同事务提交，事件可安全重放。 */
    private function completeDelivery(int $id): bool
    {
        $event = $this->runInTransaction(function () use ($id): array {
            $record = $this->deliveryOrderRepo->findForUpdate($id);
            if (!$record) {
                throw new BusinessException('配送记录不存在');
            }
            if ((string)($record['status'] ?? '') === 'cancelled') {
                throw new BusinessException('已取消配送单不能完成');
            }

            $orderId = (int)($record['order_id'] ?? 0);
            $order = $this->orderOrderRepository->findForUpdate($orderId);
            if (!$order) {
                throw new BusinessException('业务订单不存在');
            }
            $replayed = (string)$order['status'] === OrderOrder::STATUS_COMPLETED;
            if (!$replayed) {
                if ((string)$order['status'] !== OrderOrder::STATUS_SHIPPED) {
                    throw new BusinessException('业务订单尚未进入配送中');
                }
                $items = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
                foreach ($items as $item) {
                    if ((int)($item['refund_status'] ?? 0) === 1) {
                        throw new BusinessException('订单存在处理中的售后申请，暂不能完成配送');
                    }
                }
                if ($this->orderOrderRepository->markCompletedIfStatus(
                    $orderId,
                    OrderOrder::STATUS_SHIPPED
                ) !== 1) {
                    throw new BusinessException('业务订单状态已变化，请重试');
                }
            }

            if ((string)($record['status'] ?? '') !== 'completed') {
                $this->deliveryOrderRepo->update($id, [
                    'status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);
            }
            return [
                'order_id' => $orderId,
                'user_id' => (int)$order['user_id'],
                'replayed' => $replayed,
            ];
        });

        $this->trigger('order.completed', $event);
        return true;
    }

    /**
     * 导出配送记录 xlsx
     */
    public function exportXlsx(array $params): \think\Response
    {
        // 复用 getList 同样的 where 构造
        $where = [];
        if (!empty($params['keyword'])) {
            $where[] = ['order_id', '=', (int)$params['keyword']];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (string)$params['status']];
        }
        if (!empty($params['staff_id'])) {
            $where[] = ['staff_id', '=', (int)$params['staff_id']];
        }
        if (!empty($params['platform'])) {
            $where[] = ['platform', '=', (string)$params['platform']];
        }
        if (!empty($params['start_date'])) {
            $where[] = ['created_at', '>=', $params['start_date'] . ' 00:00:00'];
        }
        if (!empty($params['end_date'])) {
            $where[] = ['created_at', '<=', $params['end_date'] . ' 23:59:59'];
        }

        $rows = $this->deliveryOrderRepo->getAllForExport($where, ExcelExportService::MAX_ROWS);

        $statusLabels = [
            'pending'    => '待分配',
            'assigned'   => '已分配',
            'picking'    => '取货中',
            'picked'     => '已取货',
            'delivering' => '配送中',
            'completed'  => '已完成',
            'cancelled'  => '已取消',
        ];
        $headers = ['ID', '订单号', '订单金额', '配送员', '配送员手机', '平台', '配送费', '距离(km)', '状态', '分配时间', '取货时间', '完成时间', '创建时间'];
        $data = array_map(fn($r) => [
            $r['id'],
            $r['order']['order_no'] ?? '',
            $r['order']['pay_amount'] ?? 0,
            $r['staff']['name'] ?? '-',
            $r['staff']['phone'] ?? '',
            $r['platform'] ?? '',
            $r['fee'] ?? 0,
            $r['distance'] ?? 0,
            $statusLabels[$r['status'] ?? ''] ?? ($r['status'] ?? ''),
            $r['assigned_at'] ?? '',
            $r['picked_at'] ?? '',
            $r['completed_at'] ?? '',
            $r['created_at'] ?? '',
        ], $rows);

        return $this->excelExportService->streamXlsx(
            '配送记录_' . date('Ymd_His'),
            $headers,
            $data
        );
    }

    /**
     * 一键自动派单：按 weekday+time 筛在班 staff，按工作量最少分配 pending 订单
     *
     * @param array $orderIds  待派单 ID 数组
     * @return array  { assigned: [{id, staff_id}], skipped: [{id, reason}] }
     */
    public function autoDispatch(array $orderIds): array
    {
        if (empty($orderIds)) {
            throw new BusinessException('请选择待派单订单');
        }
        $now     = time();
        $weekday = (int)date('N', $now);   // 1..7
        $timeStr = date('H:i:s', $now);

        $activeStaffIds = $this->deliveryShiftRepository->getActiveStaffIds($weekday, $timeStr);
        if (empty($activeStaffIds)) {
            throw new BusinessException('当前无在班配送员');
        }
        $candidates = $this->deliveryStaffRepository->findActiveByIds($activeStaffIds);
        if (empty($candidates)) {
            throw new BusinessException('当前无可派单配送员（在班但 status=0）');
        }

        $candidateIds = array_map(fn($s) => (int)$s['id'], $candidates);
        $workload     = $this->deliveryOrderRepo->countActivePerStaff($candidateIds);

        $assigned = [];
        $skipped  = [];
        foreach ($orderIds as $orderId) {
            $orderId = (int)$orderId;
            $order = $this->deliveryOrderRepo->find($orderId);
            if (!$order || $order['status'] !== 'pending') {
                $skipped[] = ['id' => $orderId, 'reason' => '非待派单状态'];
                continue;
            }
            // 选最闲 staff
            $bestStaffId = null;
            $bestLoad    = PHP_INT_MAX;
            foreach ($candidates as $s) {
                $sid  = (int)$s['id'];
                $load = (int)($workload[$sid] ?? 0);
                if ($load < $bestLoad) {
                    $bestLoad    = $load;
                    $bestStaffId = $sid;
                }
            }
            $this->assign($orderId, $bestStaffId);
            $workload[$bestStaffId] = $bestLoad + 1; // 虚拟递增防同 staff 拿光
            $assigned[] = ['id' => $orderId, 'staff_id' => $bestStaffId];
        }
        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    // ==================== 三方同城配送平台 ====================

    /**
     * 向三方平台发单
     *
     * 并发防护：beginDispatch 条件 UPDATE 抢占；平台调用在事务外执行，
     * 失败通过 markDispatchFailed 回滚 platform_status（status 保持 pending 可重试）。
     */
    public function dispatch(int $id, string $platform): array
    {
        $labels = DeliveryPlatformRegistry::LABELS;
        if (!isset($labels[$platform])) {
            throw new BusinessException('未知配送平台：' . $platform);
        }
        $configs = $this->systemConfigRepository->getRawValuesByGroup('local_delivery');
        if ((string)($configs["local_delivery_{$platform}_enabled"] ?? '') !== '1') {
            throw new BusinessException($labels[$platform] . '未启用，请先在系统配置中开启');
        }

        $record = $this->deliveryOrderRepo->find($id);
        if (!$record) {
            throw new BusinessException('配送记录不存在');
        }

        if ($this->deliveryOrderRepo->beginDispatch($id, $platform) !== 1) {
            throw new BusinessException('当前配送单状态不允许发单或正在发单中');
        }

        // 事务外调用平台接口（HTTP 慢调用不占用数据库事务）
        try {
            $request = $this->buildDispatchRequest($record, $platform, $configs);
            $adapter = $this->deliveryPlatformRegistry->resolve($platform);
            $result  = $adapter->createOrder($request);
        } catch (BusinessException $e) {
            $this->deliveryOrderRepo->markDispatchFailed($id, $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $this->deliveryOrderRepo->markDispatchFailed($id, $e->getMessage());
            throw new BusinessException('发单失败：' . $e->getMessage());
        }

        if (!$result->success) {
            $reason = (string)$result->errorMessage;
            $this->deliveryOrderRepo->markDispatchFailed($id, $reason);
            throw new BusinessException('发单失败：' . $reason);
        }

        $update = [
            'platform_order_id'    => $result->platformOrderId,
            'platform_status'      => $result->platformStatus !== '' ? $result->platformStatus : 'created',
            'status'               => 'assigned',
            'dispatched_at'        => date('Y-m-d H:i:s'),
            'dispatch_fail_reason' => '',
        ];
        if ($result->fee !== null) {
            $update['fee'] = $result->fee;
        }
        try {
            $this->deliveryOrderRepo->updatePlatformDispatch($id, $update);
        } catch (\Throwable $e) {
            // 平台已接单但本地落库失败：必须清除 dispatching 哨兵并尽力保留三方单号，
            // 否则记录被永久锁死且三方存在无人跟踪的孤儿运单
            try {
                $this->deliveryOrderRepo->update($id, [
                    'platform_order_id'    => $result->platformOrderId,
                    'platform_status'      => $result->platformStatus !== '' ? $result->platformStatus : 'created',
                    'dispatch_fail_reason' => '发单成功但本地状态更新失败，请点击「同步状态」修复：' . $e->getMessage(),
                ]);
            } catch (\Throwable) {
            }
            throw new BusinessException('发单成功但本地状态更新失败，请在配送记录中「同步状态」：' . $e->getMessage());
        }
        try {
            $this->advanceOrderToShipped((int)($record['order_id'] ?? 0));
        } catch (\Throwable $e) {
            // 配送单已正确落库，业务订单推进失败可由回调/同步补推，不回滚发单结果
            Log::warning('三方发单后推进业务订单状态失败', [
                'delivery_order_id' => $id,
                'order_id'          => $record['order_id'] ?? 0,
                'message'           => $e->getMessage(),
            ]);
        }

        return $this->getDetail($id);
    }

    /**
     * 主动向平台同步三方配送单状态
     */
    public function syncStatus(int $id): array
    {
        $record = $this->deliveryOrderRepo->find($id);
        if (!$record) {
            throw new BusinessException('配送记录不存在');
        }
        $platform = (string)($record['platform'] ?? 'merchant');
        if ($platform === 'merchant' || empty($record['platform_order_id'])) {
            throw new BusinessException('仅已发单的第三方配送单支持同步状态');
        }

        $adapter = $this->deliveryPlatformRegistry->resolve($platform);
        $result  = $adapter->queryOrder((string)$record['platform_order_id']);
        if (!$result->success) {
            throw new BusinessException('同步失败：' . $result->errorMessage);
        }

        $post = $this->runInTransaction(function () use ($id, $result): array {
            $row = $this->deliveryOrderRepo->findForUpdate($id);
            if (!$row) {
                return ['complete' => false, 'advance' => 0];
            }
            return $this->applyPlatformUpdate($row, $result->platformStatus, [
                'name'  => $result->riderName,
                'phone' => $result->riderPhone,
                'lat'   => $result->riderLat,
                'lng'   => $result->riderLng,
            ], []);
        });
        $this->finalizePlatformUpdate($id, $post);

        return $this->getDetail($id);
    }

    /**
     * 三方平台回调统一入口
     *
     * @return bool 是否处理成功（验签失败 / 找不到配送单返回 false，由调用方按平台格式应答）
     */
    public function handleCallback(string $platform, array $payload, array $headers): bool
    {
        $adapter = $this->deliveryPlatformRegistry->resolve($platform);
        $event   = $adapter->parseCallback($payload, $headers);
        if ($event === null) {
            Log::warning('同城配送回调验签失败', ['platform' => $platform, 'payload' => $payload]);
            return false;
        }

        $record = $this->deliveryOrderRepo->findByPlatformOrder($platform, $event->platformOrderId);
        if (!$record) {
            Log::warning('同城配送回调未匹配到配送单', [
                'platform'          => $platform,
                'platform_order_id' => $event->platformOrderId,
            ]);
            return false;
        }

        $id   = (int)$record['id'];
        $post = $this->runInTransaction(function () use ($id, $event): array {
            $row = $this->deliveryOrderRepo->findForUpdate($id);
            if (!$row) {
                return ['complete' => false, 'advance' => 0];
            }
            return $this->applyPlatformUpdate($row, $event->platformStatus, [
                'name'  => $event->riderName,
                'phone' => $event->riderPhone,
                'lat'   => $event->riderLat,
                'lng'   => $event->riderLng,
            ], $event->raw);
        });
        // completeDelivery / advanceOrderToShipped 自带事务与幂等，放在行锁事务外执行避免嵌套死锁
        $this->finalizePlatformUpdate($id, $post);
        return true;
    }

    /**
     * 支付成功后自动发单（由 DeliveryAutoDispatchListener 调用，失败不影响支付主流程）
     */
    public function autoDispatchIfEnabled(int $orderId): void
    {
        $configs = $this->systemConfigRepository->getRawValuesByGroup('local_delivery');
        if ((string)($configs['local_delivery_auto_dispatch_enabled'] ?? '') !== '1') {
            return;
        }
        $platform = (string)($configs['local_delivery_platform'] ?? '');
        if ($platform === '' || $platform === 'merchant') {
            return;
        }
        $record = $this->deliveryOrderRepo->findByOrderId($orderId);
        if (!$record || (string)($record['status'] ?? '') !== 'pending') {
            return;
        }
        $this->dispatch((int)$record['id'], $platform);
    }

    /**
     * 已启用的三方平台选项
     *
     * @return array<int, array{code: string, label: string}>
     */
    public function getEnabledPlatformOptions(): array
    {
        $configs = $this->systemConfigRepository->getRawValuesByGroup('local_delivery');
        $options = [];
        foreach (DeliveryPlatformRegistry::LABELS as $code => $label) {
            if ((string)($configs["local_delivery_{$code}_enabled"] ?? '') === '1') {
                $options[] = ['code' => $code, 'label' => $label];
            }
        }
        return $options;
    }

    /**
     * 配送单详情（含 order/staff 关联与平台字段）
     */
    public function getDetail(int $id): array
    {
        $record = $this->deliveryOrderRepo->findWithRelations($id);
        if (!$record) {
            throw new BusinessException('配送记录不存在');
        }
        return $record;
    }

    /**
     * 骑手轨迹列表
     */
    public function getTracks(int $id): array
    {
        return $this->deliveryOrderTrackRepository->listByDeliveryOrderId($id);
    }

    /**
     * 构建发单请求（寄件人取 local_delivery_shop_* 配置，收件人取订单地址快照）
     */
    private function buildDispatchRequest(array $record, string $platform, array $configs): DeliveryDispatchRequest
    {
        $sender = [
            'name'     => (string)($configs['local_delivery_shop_name'] ?? ''),
            'phone'    => (string)($configs['local_delivery_shop_phone'] ?? ''),
            'province' => (string)($configs['local_delivery_shop_province'] ?? ''),
            'city'     => (string)($configs['local_delivery_shop_city'] ?? ''),
            'district' => (string)($configs['local_delivery_shop_district'] ?? ''),
            'address'  => (string)($configs['local_delivery_shop_address'] ?? ''),
            'lat'      => (float)($configs['local_delivery_shop_lat'] ?? 0),
            'lng'      => (float)($configs['local_delivery_shop_lng'] ?? 0),
        ];
        if ($sender['name'] === '' || $sender['phone'] === '' || $sender['address'] === '') {
            throw new BusinessException('请先在系统配置中完善寄件门店信息');
        }

        $orderId = (int)($record['order_id'] ?? 0);
        $order   = $this->orderOrderRepository->find($orderId);
        if (!$order) {
            throw new BusinessException('关联业务订单不存在');
        }
        $snap = $order['address_snapshot'] ?? [];
        if (!is_array($snap)) {
            $snap = json_decode((string)$snap, true) ?: [];
        }
        $receiver = [
            'name'     => (string)($snap['name'] ?? ''),
            'phone'    => (string)($snap['phone'] ?? ''),
            'province' => (string)($snap['province'] ?? ''),
            'city'     => (string)($snap['city'] ?? ''),
            'district' => (string)($snap['district'] ?? ''),
            'address'  => (string)($snap['detail'] ?? ''),
            'lat'      => (float)($record['dest_lat'] ?? 0),
            'lng'      => (float)($record['dest_lng'] ?? 0),
        ];
        if ($receiver['name'] === '' || $receiver['phone'] === '' || $receiver['address'] === '') {
            throw new BusinessException('订单收货地址信息不完整，无法发单');
        }

        $items     = $this->orderItemRepository->findByOrderId($orderId);
        $totalQty  = 0;
        foreach ($items as $item) {
            $totalQty += (int)($item['quantity'] ?? 0);
        }
        $firstName = (string)($items[0]['goods_name'] ?? '商品');
        $cargoName = $totalQty > 1 ? ($firstName . '等' . $totalQty . '件') : $firstName;

        return new DeliveryDispatchRequest(
            $orderId,
            (string)($order['order_no'] ?? ''),
            $cargoName,
            (float)($order['pay_amount'] ?? 0),
            0.0,
            0.0,
            $sender,
            $receiver,
            (string)($record['remark'] ?? ''),
            $this->buildNotifyUrl($platform, $configs)
        );
    }

    /**
     * 回调地址：优先配置域名，否则取当前请求域名
     */
    private function buildNotifyUrl(string $platform, array $configs): string
    {
        $domain = rtrim((string)($configs['local_delivery_notify_domain'] ?? ''), '/');
        if ($domain === '') {
            try {
                $domain = rtrim(request()->domain(), '/');
            } catch (\Throwable) {
                $domain = '';
            }
        }
        return $domain . '/api/delivery/callback/' . $platform;
    }

    /**
     * 应用平台状态更新（调用方需持有行锁事务）
     *
     * 分支规则：
     * - 本地已 cancelled 终态：忽略迟到事件
     * - mapped=completed：写骑手信息，事务提交后委托 completeDelivery（幂等）
     * - mapped=cancelled（平台侧取消）：status 回落 pending、清平台字段，可重新发单（非终态）
     * - 其余：写 status/platform_status/骑手信息；本地还是 pending 时事务后补 advanceOrderToShipped
     *
     * @param array{name: string, phone: string, lat: float|null, lng: float|null} $rider
     * @return array{complete: bool, advance: int} 事务提交后需要执行的动作
     */
    private function applyPlatformUpdate(array $record, string $platformStatus, array $rider, array $raw): array
    {
        $post = ['complete' => false, 'advance' => 0];
        if ((string)($record['status'] ?? '') === 'cancelled') {
            return $post;
        }

        $id       = (int)$record['id'];
        $platform = (string)($record['platform'] ?? '');
        $adapter  = $this->deliveryPlatformRegistry->resolve($platform);
        $mapped   = $adapter->mapStatus($platformStatus);

        // 本地已 completed 终态：仅允许 completed 重放（自身幂等），迟到/乱序的中间态事件不得回退状态
        if ((string)($record['status'] ?? '') === 'completed' && $mapped !== 'completed') {
            return $post;
        }

        $riderData = [];
        if ((string)($rider['name'] ?? '') !== '') {
            $riderData['rider_name'] = (string)$rider['name'];
        }
        if ((string)($rider['phone'] ?? '') !== '') {
            $riderData['rider_phone'] = (string)$rider['phone'];
        }
        $hasCoords = isset($rider['lat'], $rider['lng']);
        if ($hasCoords) {
            $riderData['rider_lat'] = (float)$rider['lat'];
            $riderData['rider_lng'] = (float)$rider['lng'];
        }
        $base = ['platform_status' => $platformStatus] + $riderData;
        if (!empty($raw)) {
            $base['callback_raw'] = $raw;
        }

        if ($mapped === 'completed') {
            $this->deliveryOrderRepo->update($id, $base);
            if ((string)$record['status'] === 'pending') {
                $post['advance'] = (int)($record['order_id'] ?? 0);
            }
            $post['complete'] = true; // completeDelivery 自带事务与幂等
        } elseif ($mapped === 'cancelled') {
            // 平台侧取消 → 回落 pending，允许重新发单（非终态）
            $reason = (string)($raw['cancel_reason'] ?? $raw['cancelReason'] ?? $raw['reason'] ?? '');
            $label  = DeliveryPlatformRegistry::LABELS[$platform] ?? $platform;
            $append = '[' . $label . '已取消' . ($reason !== '' ? '：' . $reason : '') . ']';
            $this->deliveryOrderRepo->update($id, [
                'status'            => 'pending',
                'platform_order_id' => null,
                'platform_status'   => '',
                'dispatched_at'     => null,
                'rider_name'        => '',
                'rider_phone'       => '',
                'rider_lat'         => null,
                'rider_lng'         => null,
                'remark'            => trim(trim((string)($record['remark'] ?? '')) . ' ' . $append),
                'callback_raw'      => !empty($raw) ? $raw : null,
            ]);
            return $post;
        } else {
            $data = $base;
            if ($mapped !== '') {
                $data['status'] = $mapped;
                if (in_array($mapped, ['picking', 'picked'], true) && empty($record['picked_at'])) {
                    $data['picked_at'] = date('Y-m-d H:i:s');
                }
                if ((string)$record['status'] === 'pending') {
                    $post['advance'] = (int)($record['order_id'] ?? 0);
                }
            }
            $this->deliveryOrderRepo->update($id, $data);
        }

        if ($hasCoords) {
            $this->deliveryOrderTrackRepository->create([
                'delivery_order_id' => $id,
                'lat'               => (float)$rider['lat'],
                'lng'               => (float)$rider['lng'],
                'platform_status'   => $platformStatus,
                'reported_at'       => date('Y-m-d H:i:s'),
            ]);
        }
        return $post;
    }

    /**
     * 行锁事务提交后的收尾动作（advanceOrderToShipped / completeDelivery 各自带事务）
     */
    private function finalizePlatformUpdate(int $id, array $post): void
    {
        if ((int)($post['advance'] ?? 0) > 0) {
            $this->advanceOrderToShipped((int)$post['advance']);
        }
        if (!empty($post['complete'])) {
            $this->completeDelivery($id);
        }
    }
}
