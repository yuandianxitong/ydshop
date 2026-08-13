<?php
declare(strict_types=1);

namespace app\service\order;

use app\repository\goods\GoodsSkuRepository;
use app\repository\goods\GoodsSpuRepository;
use app\repository\order\OrderItemRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\order\OrderPaymentRepository;
use app\repository\order\OrderRefundRepository;
use app\repository\payment\PaymentOrderRepository;
use app\repository\system\SystemConfigRepository;
use app\service\payment\PaymentService;
use app\support\OrderItemAmountAllocator;
use core\base\Service;
use core\exception\BusinessException;
use core\plugin\PluginManager;

class OrderRefundService extends Service
{
    protected OrderRefundRepository $orderRefundRepo;
    protected OrderItemRepository $orderItemRepository;
    protected OrderOrderRepository $orderOrderRepository;
    protected OrderPaymentRepository $orderPaymentRepository;
    protected PaymentOrderRepository $paymentOrderRepository;
    protected GoodsSkuRepository $goodsSkuRepository;
    protected GoodsSpuRepository $goodsSpuRepository;
    protected SystemConfigRepository $systemConfigRepository;
    protected PaymentService $paymentService;

    /**
     * 拆单子订单不持有自己的支付记录，退款必须回溯到支付根订单。
     * payment_root_order_id 为 NULL/0 表示自身即根（非拆单订单）。
     *
     * @param array<string, mixed> $order 订单行
     */
    private function resolvePaymentRootOrderId(array $order): int
    {
        $rootId = (int)($order['payment_root_order_id'] ?? 0);
        return $rootId > 0 ? $rootId : (int)$order['id'];
    }

    /**
     * 只有订单 id 时的根解析：payment_root_order_id 拆单时一次写入且不可变，
     * 无锁读取即可；订单行缺失时回退自身 id，保持与改造前相同的报错路径
     * （后续 findSuccessByOrderId 返回 null → '未找到有效的支付记录'）。
     */
    private function resolvePaymentRootByOrderId(int $orderId): int
    {
        $order = $this->orderOrderRepository->find($orderId);
        return $order !== null ? $this->resolvePaymentRootOrderId($order) : $orderId;
    }

    /**
     * 支付根家族（根自身 + 全部拆单子订单），按 id 升序；额度校验与全额
     * 退款结算都必须以家族为单位。非拆单订单家族恒为 [自身]。
     *
     * @return int[]
     */
    private function resolvePaymentFamilyOrderIds(int $rootOrderId): array
    {
        $ids = $this->orderOrderRepository->findOrderIdsByPaymentRoot($rootOrderId);
        if ($ids === []) {
            return [$rootOrderId];
        }
        sort($ids);
        return $ids;
    }

    /**
     * 用户申请退款
     *
     * @param int   $userId 用户ID
     * @param array $data   [order_item_id, type, reason, description, images, refund_amount]
     */
    public function apply(int $userId, array $data): array
    {
        $itemId = (int)($data['order_item_id'] ?? 0);
        $item = $this->orderItemRepository->find($itemId);
        if (!$item) {
            throw new BusinessException('订单商品不存在');
        }

        $order = $this->orderOrderRepository->findByIdAndUser((int)$item['order_id'], $userId);
        if (!$order) {
            throw new BusinessException('订单不存在');
        }

        if (!in_array($order['status'] ?? '', ['paid', 'shipped', 'completed'], true)) {
            throw new BusinessException('当前订单状态不支持申请退款');
        }

        $type = $this->normalizeRefundType((string)($data['type'] ?? 'refund_only'));
        $this->assertRefundTypeAllowedForOrderStatus($type, (string)$order['status']);

        // 检查是否超过售后申请时限
        $deadlineDays = (int)$this->systemConfigRepository->getConfigValue('order.refund_deadline_days', 15);
        if ($deadlineDays > 0 && !empty($order['receive_time'])) {
            $deadline = strtotime($order['receive_time']) + $deadlineDays * 86400;
            if (time() > $deadline) {
                throw new BusinessException('已超过售后申请时限（' . $deadlineDays . '天）');
            }
        }

        return $this->runInTransaction(function () use ($userId, $data, $itemId, $order, $type) {
            // 以 payment_orders 行锁作为整单退款额度的串行化锁。
            // 不同商品行同时申请，也必须按此锁顺序计算剩余可退。
            // 拆单子订单回溯到支付根订单查支付记录（非拆单订单根=自身）。
            $orderPayment = $this->orderPaymentRepository->findSuccessByOrderId(
                $this->resolvePaymentRootOrderId($order)
            );
            if (!$orderPayment) {
                throw new BusinessException('未找到有效的支付记录');
            }

            $paymentOrder = $this->paymentOrderRepository->findForUpdate((int)$orderPayment['payment_order_id']);
            if (!$paymentOrder || (string)$paymentOrder->status !== 'paid') {
                throw new BusinessException('支付单当前无可退金额');
            }

            // 锁后重新校验订单归属和状态，避免事务前读取的数据已变化。
            $lockedOrder = $this->orderOrderRepository->findByIdAndUser((int)$order['id'], $userId);
            if (!$lockedOrder) {
                throw new BusinessException('订单不存在');
            }
            if (!in_array($lockedOrder['status'] ?? '', ['paid', 'shipped', 'completed'], true)) {
                throw new BusinessException('当前订单状态不支持申请退款');
            }
            $this->assertRefundTypeAllowedForOrderStatus(
                $type,
                (string)$lockedOrder['status']
            );

            $lockedItems = $this->orderItemRepository->findByOrderIdForUpdate((int)$order['id']);
            $lockedItems = $this->ensureItemAmountAllocations($lockedOrder, $lockedItems);
            $lockedItem = null;
            foreach ($lockedItems as $candidate) {
                if ((int)$candidate['id'] === $itemId) {
                    $lockedItem = $candidate;
                    break;
                }
            }
            if (!$lockedItem || (int)$lockedItem['order_id'] !== (int)$order['id']) {
                throw new BusinessException('订单商品不存在');
            }

            if ((int)($lockedItem['refund_status'] ?? 0) !== 0
                || $this->orderRefundRepo->findActiveByItemId($itemId) !== null) {
                throw new BusinessException('该商品已存在退款申请，请勿重复提交');
            }

            $linePayCents = $this->toCents($lockedItem['pay_amount'] ?? 0);
            if ($linePayCents <= 0) {
                throw new BusinessException('该商品无可退金额');
            }

            if (array_key_exists('refund_amount', $data)
                && $this->toCents($data['refund_amount']) !== $linePayCents) {
                // 当前售后模型没有“退款数量”字段，因此明确采用整行退款；
                // 避免退 1 元却将整行库存回库。
                throw new BusinessException('退款金额必须等于该商品行净实付金额');
            }

            return $this->createRefundRecord(
                $lockedOrder,
                $lockedItem,
                $type,
                (string)($data['reason'] ?? ''),
                'pending',
                (string)($data['description'] ?? ''),
                (array)($data['images'] ?? [])
            );
        });
    }

    /**
     * 校验整单剩余可退额度 → 创建退款记录 → 置 item refund_status=1。
     *
     * 必须在事务内、且调用方已持有支付单行锁与订单商品行锁后调用；
     * 内部对同一支付单再次 findForUpdate 属同事务重入，不改变锁顺序。
     *
     * @param array<string, mixed> $order 订单行（含 id / user_id）
     * @param array<string, mixed> $item  订单商品行（含 id / pay_amount）
     */
    private function createRefundRecord(
        array $order,
        array $item,
        string $type,
        string $reason,
        string $initialStatus,
        string $description = '',
        array $images = []
    ): array {
        $itemId = (int)$item['id'];

        $rootOrderId = $this->resolvePaymentRootOrderId($order);
        $orderPayment = $this->orderPaymentRepository->findSuccessByOrderId($rootOrderId);
        if (!$orderPayment) {
            throw new BusinessException('未找到有效的支付记录');
        }
        $paymentOrder = $this->paymentOrderRepository->findForUpdate((int)$orderPayment['payment_order_id']);
        if (!$paymentOrder || (string)$paymentOrder->status !== 'paid') {
            throw new BusinessException('支付单当前无可退金额');
        }

        // 拆单后同一支付单被家族多个订单共享，预占额度必须按家族聚合。
        $familyOrderIds = $this->resolvePaymentFamilyOrderIds($rootOrderId);
        $totalCents = $this->toCents($paymentOrder->total_amount ?? 0);
        $reservedCents = max(
            $this->toCents($this->orderRefundRepo->sumReservedByOrderIds($familyOrderIds)),
            $this->toCents($paymentOrder->refund_amount ?? 0)
        );
        $remainingCents = max(0, $totalCents - $reservedCents);

        $refundCents = $this->toCents($item['pay_amount'] ?? 0);
        if ($refundCents <= 0 || $refundCents > $remainingCents) {
            throw new BusinessException('退款金额超过支付单剩余可退金额');
        }

        $refund = $this->orderRefundRepo->create([
            'refund_no'     => $this->orderRefundRepo->generateRefundNo(),
            'order_id'      => (int)$order['id'],
            'order_item_id' => $itemId,
            'user_id'       => (int)$order['user_id'],
            'type'          => $type,
            'status'        => $initialStatus,
            'reason'        => $reason,
            'description'   => $description,
            'images'        => $images,
            'refund_amount' => $this->fromCents($refundCents),
        ]);

        $this->orderItemRepository->setRefundStatus($itemId, 1);

        return $refund;
    }

    /**
     * 系统对整单发起「仅退款」（拼团失败等自动补偿场景）。
     *
     * 事务内锁支付单（额度串行化锁，与 apply 同序）→ 锁订单（必须 paid）→
     * 锁全部商品行，对每个有净实付、未退款且无活跃退款记录的行创建 approved
     * 退款记录；事务提交后逐条执行外部退款。重复调用因 refund_status 过滤
     * 后无可退行，幂等返回 skipped。
     *
     * @return array{order_id:int,refund_ids:array<int,int>,skipped:array<int,int>}
     */
    public function systemRefundOrder(int $orderId, string $reason): array
    {
        $skipped = [];
        $created = $this->runInTransaction(function () use ($orderId, $reason, &$skipped): array {
            $orderPayment = $this->orderPaymentRepository->findSuccessByOrderId(
                $this->resolvePaymentRootByOrderId($orderId)
            );
            if (!$orderPayment) {
                throw new BusinessException('未找到有效的支付记录');
            }
            $paymentOrder = $this->paymentOrderRepository->findForUpdate((int)$orderPayment['payment_order_id']);
            if (!$paymentOrder || (string)$paymentOrder->status !== 'paid') {
                throw new BusinessException('支付单当前无可退金额');
            }

            $order = $this->orderOrderRepository->findForUpdate($orderId);
            if (!$order) {
                throw new BusinessException('订单不存在');
            }
            if (($order['status'] ?? '') !== 'paid') {
                throw new BusinessException('当前订单状态不支持系统退款');
            }

            $items = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
            $items = $this->ensureItemAmountAllocations($order, $items);

            $refunds = [];
            foreach ($items as $item) {
                $itemId = (int)$item['id'];
                if ($this->toCents($item['pay_amount'] ?? 0) <= 0
                    || (int)($item['refund_status'] ?? 0) !== 0
                    || $this->orderRefundRepo->findActiveByItemId($itemId) !== null) {
                    $skipped[] = $itemId;
                    continue;
                }
                $refunds[] = $this->createRefundRecord($order, $item, 'refund_only', $reason, 'approved');
            }

            return $refunds;
        });

        $refundIds = [];
        foreach ($created as $refund) {
            $refundId = (int)$refund['id'];
            $refundIds[] = $refundId;
            try {
                $this->executeRefund($refundId);
            } catch (\Throwable $e) {
                // 单条外呼失败不阻断其余行；记录保持 approved/refunding，
                // 可由管理员重试或 refund:reconcile 补偿。
                $this->log('systemRefundOrder executeRefund failed', [
                    'order_id'  => $orderId,
                    'refund_id' => $refundId,
                    'error'     => $e->getMessage(),
                ], 'error');
            }
        }

        return [
            'order_id'   => $orderId,
            'refund_ids' => $refundIds,
            'skipped'    => $skipped,
        ];
    }

    /**
     * 管理员同意退款申请
     */
    public function approve(int $refundId, string $remark = ''): void
    {
        $shouldExecute = $this->runInTransaction(function () use ($refundId, $remark): bool {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund) {
                throw new BusinessException('退款记录不存在');
            }

            $status = (string)($refund['status'] ?? '');
            if ($status === 'pending') {
                $this->orderRefundRepo->update($refundId, [
                    'status'       => 'approved',
                    'admin_remark' => $remark,
                ]);
                return $this->isRefundOnly($refund);
            }

            // 如果上次请求在“已审核”后、外部调用前中断，再次审核可继续执行。
            if ($status === 'approved' && $this->isRefundOnly($refund)) {
                return true;
            }
            if (in_array($status, ['refunding', 'refunded'], true)) {
                return false;
            }

            throw new BusinessException('当前退款状态不支持审核通过操作');
        });

        // 仅退款审核后直接退款；退货退款必须等待商家确认收货。
        if ($shouldExecute) {
            $this->executeRefund($refundId);
        }
    }

    /**
     * 管理员拒绝退款申请
     */
    public function reject(int $refundId, string $reason): void
    {
        $this->runInTransaction(function () use ($refundId, $reason): void {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund) {
                throw new BusinessException('退款记录不存在');
            }

            if (($refund['status'] ?? '') === 'rejected') {
                return;
            }
            $status = (string)($refund['status'] ?? '');
            if (!in_array($status, ['pending', 'approved', 'returning', 'received', 'retryable_failed'], true)) {
                throw new BusinessException('当前退款状态不支持拒绝操作');
            }
            if ($status === 'retryable_failed'
                && !$this->isProviderRefundDefinitelyAbsent((string)($refund['provider_status'] ?? ''))) {
                throw new BusinessException('仅渠道明确不存在的退款可以释放预占额度');
            }

            // 与申请/执行退款使用相同支付单行锁，保证释放预占额度时的串行化。
            // 拆单子订单同样回溯根订单的支付记录加锁。
            $orderPayment = $this->orderPaymentRepository->findSuccessByOrderId(
                $this->resolvePaymentRootByOrderId((int)$refund['order_id'])
            );
            if ($orderPayment) {
                $this->paymentOrderRepository->findForUpdate((int)$orderPayment['payment_order_id']);
            }

            $this->orderRefundRepo->update($refundId, [
                'status'        => 'rejected',
                'refuse_reason' => $reason,
            ]);

            $this->orderItemRepository->setRefundStatus((int)$refund['order_item_id'], 0);
        });
    }

    /**
     * 用户提交退货物流
     */
    public function submitReturnLogistics(int $refundId, int $userId, string $company, string $expressNo): void
    {
        $this->runInTransaction(function () use ($refundId, $userId, $company, $expressNo): void {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund || (int)($refund['user_id'] ?? 0) !== $userId) {
                throw new BusinessException('退款记录不存在');
            }
            if (!$this->isReturnRefund($refund) || ($refund['status'] ?? '') !== 'approved') {
                throw new BusinessException('当前退款状态不支持填写退货物流');
            }

            $this->orderRefundRepo->update($refundId, [
                'status'                 => 'returning',
                'return_express_company' => $company,
                'return_express_no'      => $expressNo,
            ]);
        });
    }

    /**
     * 管理员确认收到退货商品
     */
    public function confirmReceived(int $refundId): void
    {
        $shouldExecute = $this->runInTransaction(function () use ($refundId): bool {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund) {
                throw new BusinessException('退款记录不存在');
            }
            if (!$this->isReturnRefund($refund)) {
                throw new BusinessException('仅退货退款需要确认收货');
            }

            $status = (string)($refund['status'] ?? '');
            if ($status === 'returning') {
                $this->orderRefundRepo->update($refundId, ['status' => 'received']);
                return true;
            }
            if ($status === 'received') {
                return true;
            }
            if (in_array($status, ['refunding', 'refunded'], true)) {
                return false;
            }

            throw new BusinessException('当前退款状态不支持确认收货操作');
        });

        if ($shouldExecute) {
            $this->executeRefund($refundId);
        }
    }

    /**
     * 执行实际退款操作
     */
    public function executeRefund(int $refundId): void
    {
        $prepared = $this->prepareRefund($refundId);
        if ($prepared === null) {
            return;
        }

        try {
            // 外部网络调用必须位于两个本地事务之间，不占用 DB 行锁。
            $result = $this->paymentService->refund((string)$prepared['payment_channel'], [
                'out_trade_no'  => $prepared['payment_order_no'],
                'out_refund_no' => $prepared['refund_no'],
                'refund_amount' => $prepared['refund_amount'],
                'total_amount'  => $prepared['total_amount'],
                'reason'        => $prepared['reason'],
            ]);
        } catch (\Throwable $e) {
            // 外呼超时/断线无法证明 provider 未受理。保持 refunding 并保留同一
            // refund_no，只允许 retry/sync；恢复 approved 会让管理员可拒绝并
            // 释放额度，形成“渠道已退款、本地却拒绝”的资金漏洞。
            $this->recordProviderObservation($refundId, [
                'provider_status' => 'UNKNOWN',
            ], date('Y-m-d H:i:s'));
            throw $e;
        }

        $checkedAt = date('Y-m-d H:i:s');
        $this->recordProviderObservation($refundId, $result, $checkedAt);

        // 微信可能返回 PROCESSING：保持 refunding，后续只查询原 refund_no，
        // 直到 provider 明确返回 SUCCESS 才结算本地账务。
        if (($result['status'] ?? '') === 'processing') {
            return;
        }
        if (($result['status'] ?? '') !== 'success') {
            throw new BusinessException('支付渠道退款未成功');
        }

        $refundTradeNo = trim((string)($result['refund_trade_no'] ?? $prepared['refund_no']));
        $refundTradeNoSource = trim((string)($result['refund_trade_no_source'] ?? 'merchant_refund_no'));
        $refundedAt = $this->resolveRefundedAt($result, $checkedAt);
        $event = $this->finalizeRefund(
            $refundId,
            $refundTradeNo,
            $refundTradeNoSource,
            (string)($result['provider_status'] ?? 'SUCCESS'),
            $checkedAt,
            $refundedAt
        );
        if ($event !== null) {
            // 仅首次成功结算会返回事件；并发重试不会重复回库或写流水。
            $this->trigger('order.refund.completed', $event);
        }
    }

    /** 对 refunding 状态只查询原 refund_no，避免人工按钮重复发起外部退款。 */
    public function retryRefund(int $refundId): void
    {
        $status = $this->runInTransaction(function () use ($refundId): string {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund) {
                throw new BusinessException('退款记录不存在');
            }
            return (string)($refund['status'] ?? '');
        });
        if ($status === 'retryable_failed') {
            $this->executeRefund($refundId);
            return;
        }
        $this->syncRefund($refundId);
    }

    /**
     * 查询渠道上的原退款请求并同步本地。该方法绝不释放退款预占：failed/unknown
     * 只持久化诊断状态；只有严格校验后的 success 才进入幂等结算事务。
     *
     * @return array<string, mixed>
     */
    public function syncRefund(int $refundId): array
    {
        $prepared = $this->prepareRefundSync($refundId);
        if ($prepared === null) {
            return [
                'status' => 'success',
                'provider_status' => 'LOCAL_REFUNDED',
                'already_finalized' => true,
            ];
        }

        try {
            $result = $this->paymentService->queryRefund((string)$prepared['payment_channel'], [
                'out_trade_no' => $prepared['payment_order_no'],
                'out_refund_no' => $prepared['refund_no'],
                'refund_amount' => $prepared['refund_amount'],
                'total_amount' => $prepared['total_amount'],
            ]);
        } catch (\Throwable $e) {
            $this->recordProviderObservation($refundId, [
                'provider_status' => 'UNKNOWN',
            ], date('Y-m-d H:i:s'));
            throw $e;
        }

        $checkedAt = date('Y-m-d H:i:s');
        $this->recordProviderObservation($refundId, $result, $checkedAt);

        if (($result['status'] ?? '') === 'failed') {
            $this->recordDefinitiveRefundFailure($refundId, $result);
            return $result;
        }
        if (($result['status'] ?? '') !== 'success') {
            return $result;
        }

        $refundTradeNo = trim((string)($result['refund_trade_no'] ?? $prepared['refund_no']));
        $refundTradeNoSource = trim((string)($result['refund_trade_no_source'] ?? 'merchant_refund_no'));
        $refundedAt = $this->resolveRefundedAt($result, $checkedAt);
        $event = $this->finalizeRefund(
            $refundId,
            $refundTradeNo,
            $refundTradeNoSource,
            (string)($result['provider_status'] ?? 'SUCCESS'),
            $checkedAt,
            $refundedAt
        );
        if ($event !== null) {
            $this->trigger('order.refund.completed', $event);
        }

        return $result;
    }

    /**
     * 定时补偿入口：以主键游标扫描超过静默窗口的 refunding 记录，逐条查询渠道。
     * 单条失败不会阻断后续记录，也不会回滚/拒绝任何退款。
     *
     * @return array{stale_before:string,scanned:int,success:int,pending:int,failed:array<int,array{refund_id:int,error:string}>}
     */
    public function reconcileStaleRefunds(int $batchSize = 200, int $staleMinutes = 10): array
    {
        $batchSize = min(1000, max(1, $batchSize));
        $staleMinutes = min(1440, max(1, $staleMinutes));
        $staleBefore = date('Y-m-d H:i:s', time() - ($staleMinutes * 60));
        $cursor = 0;
        $stats = [
            'stale_before' => $staleBefore,
            'scanned' => 0,
            'success' => 0,
            'pending' => 0,
            'failed' => [],
        ];

        while (true) {
            $rows = $this->orderRefundRepo->getStaleRefundingAfterId(
                $cursor,
                $batchSize,
                $staleBefore
            );
            if ($rows === []) {
                break;
            }

            $previousCursor = $cursor;
            foreach ($rows as $row) {
                $refundId = (int)($row['id'] ?? 0);
                if ($refundId <= $cursor) {
                    continue;
                }
                $cursor = $refundId;
                $stats['scanned']++;

                try {
                    $result = $this->syncRefund($refundId);
                    if (($result['status'] ?? '') === 'success') {
                        $stats['success']++;
                    } else {
                        $stats['pending']++;
                    }
                } catch (\Throwable $e) {
                    $stats['failed'][] = [
                        'refund_id' => $refundId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if ($cursor <= $previousCursor || count($rows) < $batchSize) {
                break;
            }
        }

        return $stats;
    }

    /**
     * 事务 1：锁定售后单和支付单，校验累计额度并进入 refunding。
     *
     * @return array<string, mixed>|null null 表示已完成或另一个请求正在处理
     */
    private function prepareRefund(int $refundId): ?array
    {
        return $this->runInTransaction(function () use ($refundId): ?array {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund) {
                throw new BusinessException('退款记录不存在');
            }

            $status = (string)($refund['status'] ?? '');
            if ($status === 'refunded') {
                return null;
            }
            if ($status === 'refunding') {
                return null;
            }

            $eligibleStatus = $this->isRefundOnly($refund) ? 'approved' : 'received';
            if (!in_array($status, [$eligibleStatus, 'retryable_failed'], true)) {
                throw new BusinessException('当前退款状态不支持执行退款');
            }

            $rootOrderId = $this->resolvePaymentRootByOrderId((int)$refund['order_id']);
            $orderPayment = $this->orderPaymentRepository->findSuccessByOrderId($rootOrderId);
            if (!$orderPayment) {
                throw new BusinessException('未找到有效的支付记录');
            }

            $paymentOrder = $this->paymentOrderRepository->findForUpdate((int)$orderPayment['payment_order_id']);
            if (!$paymentOrder) {
                throw new BusinessException('未找到支付平台订单');
            }
            if ((string)$paymentOrder->status !== 'paid') {
                throw new BusinessException('支付单当前无可退金额');
            }

            // 家族聚合额度：拆单家族共享同一支付单的可退总额。
            $familyOrderIds = $this->resolvePaymentFamilyOrderIds($rootOrderId);
            $totalCents = $this->toCents($paymentOrder->total_amount ?? 0);
            $refundCents = $this->toCents($refund['refund_amount'] ?? 0);
            $reservedCents = max(
                $this->toCents($this->orderRefundRepo->sumReservedByOrderIds($familyOrderIds)),
                $this->toCents($paymentOrder->refund_amount ?? 0)
            );
            $alreadyRefundedCents = max(
                $this->toCents($this->orderRefundRepo->sumRefundedByOrderIds($familyOrderIds)),
                $this->toCents($paymentOrder->refund_amount ?? 0)
            );

            if ($refundCents <= 0
                || $reservedCents > $totalCents
                || ($alreadyRefundedCents + $refundCents) > $totalCents) {
                throw new BusinessException('退款金额超过支付单剩余可退金额');
            }

            $this->orderRefundRepo->update($refundId, [
                'status' => 'refunding',
                'provider_status' => 'REQUESTING',
                'provider_requested_at' => date('Y-m-d H:i:s'),
                // 新的幂等退款请求尚未获得观察结果，避免旧检查时间让自动任务立即命中。
                'provider_checked_at' => null,
                'failure_reason' => '',
                'retry_count' => $status === 'retryable_failed'
                    ? ((int)($refund['retry_count'] ?? 0) + 1)
                    : (int)($refund['retry_count'] ?? 0),
            ]);

            return [
                'refund_no'       => (string)$refund['refund_no'],
                'refund_amount'   => $this->fromCents($refundCents),
                'reason'          => (string)($refund['reason'] ?? ''),
                'payment_channel' => (string)$paymentOrder->channel,
                'payment_order_no' => (string)$paymentOrder->order_no,
                'total_amount'    => $this->fromCents($totalCents),
            ];
        });
    }

    /**
     * 构造退款查询上下文。读取与结算采用相同锁顺序，但外部查询在事务外执行。
     *
     * @return array<string, mixed>|null 已本地结算时返回 null
     */
    private function prepareRefundSync(int $refundId): ?array
    {
        return $this->runInTransaction(function () use ($refundId): ?array {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund) {
                throw new BusinessException('退款记录不存在');
            }
            if (($refund['status'] ?? '') === 'refunded') {
                return null;
            }
            if (!in_array(($refund['status'] ?? ''), ['refunding', 'manual_review'], true)) {
                throw new BusinessException('当前退款状态不支持渠道同步');
            }

            $orderPayment = $this->orderPaymentRepository->findSuccessByOrderId(
                $this->resolvePaymentRootByOrderId((int)$refund['order_id'])
            );
            if (!$orderPayment) {
                throw new BusinessException('未找到有效的支付记录');
            }
            $paymentOrder = $this->paymentOrderRepository->findForUpdate((int)$orderPayment['payment_order_id']);
            if (!$paymentOrder || !in_array((string)$paymentOrder->status, ['paid', 'refunded'], true)) {
                throw new BusinessException('支付单状态不支持退款同步');
            }

            $refundCents = $this->toCents($refund['refund_amount'] ?? 0);
            $totalCents = $this->toCents($paymentOrder->total_amount ?? 0);
            if ($refundCents <= 0 || $refundCents > $totalCents) {
                throw new BusinessException('退款金额与支付单不匹配');
            }

            return [
                'refund_no' => (string)$refund['refund_no'],
                'refund_amount' => $this->fromCents($refundCents),
                'payment_channel' => (string)$paymentOrder->channel,
                'payment_order_no' => (string)$paymentOrder->order_no,
                'total_amount' => $this->fromCents($totalCents),
            ];
        });
    }

    /**
     * 持久化一次渠道观察。退款标识一旦记录便不可被另一标识覆盖；这条约束与
     * 商户退款单号共同防止错误查询结果串单。
     *
     * @param array<string, mixed> $result
     */
    private function recordProviderObservation(int $refundId, array $result, string $checkedAt): void
    {
        $this->runInTransaction(function () use ($refundId, $result, $checkedAt): void {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund || ($refund['status'] ?? '') === 'refunded') {
                return;
            }
            if (!in_array(($refund['status'] ?? ''), [
                'refunding', 'retryable_failed', 'manual_review',
            ], true)) {
                throw new BusinessException('退款单状态已变更，不能写入渠道结果');
            }

            $incomingTradeNo = trim((string)($result['refund_trade_no'] ?? ''));
            $incomingSource = trim((string)($result['refund_trade_no_source'] ?? ''));
            $storedTradeNo = trim((string)($refund['refund_trade_no'] ?? ''));
            $storedSource = trim((string)($refund['refund_trade_no_source'] ?? ''));
            if ($storedTradeNo !== '' && $incomingTradeNo !== '' && !hash_equals($storedTradeNo, $incomingTradeNo)) {
                throw new BusinessException('支付渠道退款标识与已记录结果不匹配');
            }
            if ($storedSource !== '' && $incomingSource !== '' && !hash_equals($storedSource, $incomingSource)) {
                throw new BusinessException('支付渠道退款标识来源与已记录结果不匹配');
            }

            $providerStatus = strtoupper(trim((string)($result['provider_status'] ?? ($result['status'] ?? 'UNKNOWN'))));
            $data = [
                'provider_status' => substr($providerStatus !== '' ? $providerStatus : 'UNKNOWN', 0, 32),
                'provider_checked_at' => $checkedAt,
            ];
            if ($incomingTradeNo !== '') {
                $data['refund_trade_no'] = $incomingTradeNo;
            }
            if ($incomingSource !== '') {
                $data['refund_trade_no_source'] = $incomingSource;
            }
            $this->orderRefundRepo->update($refundId, $data);
        });
    }

    /** 渠道明确失败后离开 refunding，避免无限占据“处理中”且没有运维入口。 */
    private function recordDefinitiveRefundFailure(int $refundId, array $result): void
    {
        $providerStatus = strtoupper(trim((string)($result['provider_status'] ?? 'FAILED')));
        $targetStatus = $this->isProviderRefundDefinitelyAbsent($providerStatus)
            ? 'retryable_failed'
            : 'manual_review';

        $this->runInTransaction(function () use (
            $refundId,
            $providerStatus,
            $targetStatus
        ): void {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund || ($refund['status'] ?? '') === 'refunded') {
                return;
            }
            if (($refund['status'] ?? '') !== 'refunding') {
                return;
            }
            $this->orderRefundRepo->update($refundId, [
                'status' => $targetStatus,
                'failure_reason' => mb_substr(
                    '支付渠道退款状态：' . ($providerStatus !== '' ? $providerStatus : 'FAILED'),
                    0,
                    255
                ),
            ]);
        });
    }

    /** 只有渠道明确“该退款请求从未存在”时，才允许重发或释放本地预占。 */
    private function isProviderRefundDefinitelyAbsent(string $providerStatus): bool
    {
        return in_array(strtoupper(trim($providerStatus)), [
            'REFUND_NOT_EXIST',
            'REFUND_NOT_EXISTS',
            'ACQ.REFUND_NOT_EXIST',
        ], true);
    }

    /**
     * 渠道明确给出成功时间时，使用其真实发生时间；缺失时才回退到本地确认时间。
     * 微信返回带时区的 RFC3339，支付宝返回商户时区的 Y-m-d H:i:s。
     *
     * @param array<string, mixed> $result
     */
    private function resolveRefundedAt(array $result, string $localConfirmedAt): string
    {
        $providerValue = trim((string)($result['provider_refunded_at'] ?? ''));
        if ($providerValue === '') {
            return $localConfirmedAt;
        }

        $providerTime = $this->parseProviderRefundedAt($providerValue);
        if ($providerTime === null || $providerTime->getTimestamp() <= 0) {
            throw new BusinessException('支付渠道退款成功时间格式非法');
        }
        if ($providerTime->getTimestamp() > time()) {
            throw new BusinessException('支付渠道退款成功时间不能晚于当前时间');
        }

        return $providerTime
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->format('Y-m-d H:i:s');
    }

    private function parseProviderRefundedAt(string $value): ?\DateTimeImmutable
    {
        $localTimezone = new \DateTimeZone(date_default_timezone_get());
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $this->createStrictDateTime('!Y-m-d H:i:s', $value, $localTimezone);
        }

        if (preg_match(
            '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.(\d{1,6}))?(Z|[+-]\d{2}:\d{2})$/',
            $value,
            $matches
        ) !== 1) {
            return null;
        }

        $fraction = (string)($matches[2] ?? '');
        $zone = $matches[3] === 'Z' ? '+00:00' : $matches[3];
        if ($fraction !== '') {
            $normalized = $matches[1] . '.' . str_pad($fraction, 6, '0') . $zone;
            return $this->createStrictDateTime('!Y-m-d\TH:i:s.uP', $normalized, $localTimezone);
        }

        return $this->createStrictDateTime(
            '!Y-m-d\TH:i:sP',
            $matches[1] . $zone,
            $localTimezone
        );
    }

    private function createStrictDateTime(
        string $format,
        string $value,
        \DateTimeZone $timezone
    ): ?\DateTimeImmutable {
        $dateTime = \DateTimeImmutable::createFromFormat($format, $value, $timezone);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($dateTime === false) {
            return null;
        }
        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $dateTime;
    }

    /**
     * 事务 2：provider 成功后结算本地累计退款、订单项与库存。
     *
     * @return array<string, mixed>|null 并发重试中只有首个结算者返回事件
     */
    private function finalizeRefund(
        int $refundId,
        string $refundTradeNo,
        string $refundTradeNoSource,
        string $providerStatus,
        string $providerCheckedAt,
        string $refundedAt
    ): ?array
    {
        return $this->runInTransaction(function () use (
            $refundId,
            $refundTradeNo,
            $refundTradeNoSource,
            $providerStatus,
            $providerCheckedAt,
            $refundedAt
        ): ?array {
            $refund = $this->orderRefundRepo->findForUpdate($refundId);
            if (!$refund) {
                throw new BusinessException('退款记录不存在');
            }
            if (($refund['status'] ?? '') === 'refunded') {
                return null;
            }
            if (!in_array(($refund['status'] ?? ''), [
                'refunding', 'retryable_failed', 'manual_review',
            ], true)) {
                throw new BusinessException('退款单状态已变更，请重新处理');
            }

            $orderId = (int)$refund['order_id'];
            $rootOrderId = $this->resolvePaymentRootByOrderId($orderId);
            $orderPayment = $this->orderPaymentRepository->findSuccessByOrderId($rootOrderId);
            if (!$orderPayment) {
                throw new BusinessException('未找到有效的支付记录');
            }

            $paymentOrderId = (int)$orderPayment['payment_order_id'];
            $paymentOrder = $this->paymentOrderRepository->findForUpdate($paymentOrderId);
            if (!$paymentOrder || (string)$paymentOrder->status !== 'paid') {
                throw new BusinessException('支付单状态已变更，请核对退款结果');
            }

            // 锁序：真实支付单行锁 → 家族订单按 id 升序 → 商品行。
            // 非拆单订单家族=[自身]，与改造前单订单行锁完全一致。
            $familyOrderIds = $this->resolvePaymentFamilyOrderIds($rootOrderId);
            if (!in_array($orderId, $familyOrderIds, true)) {
                $familyOrderIds[] = $orderId;
                sort($familyOrderIds);
            }
            $familyOrders = [];
            foreach ($familyOrderIds as $familyOrderId) {
                $lockedFamilyOrder = $this->orderOrderRepository->findForUpdate($familyOrderId);
                if ($lockedFamilyOrder !== null) {
                    $familyOrders[$familyOrderId] = $lockedFamilyOrder;
                }
            }
            $orderRecord = $familyOrders[$orderId] ?? null;
            if (!$orderRecord) {
                throw new BusinessException('订单不存在');
            }
            $orderStatusBeforeRefund = (string)($orderRecord['status'] ?? '');

            $totalCents = $this->toCents($paymentOrder->total_amount ?? 0);
            $currentRefundedCents = max(
                $this->toCents($paymentOrder->refund_amount ?? 0),
                $this->toCents($this->orderRefundRepo->sumRefundedByOrderIds($familyOrderIds))
            );
            $nextRefundedCents = $currentRefundedCents + $this->toCents($refund['refund_amount'] ?? 0);
            if ($nextRefundedCents > $totalCents) {
                throw new BusinessException('累计退款金额超过支付金额');
            }

            $fullyRefunded = $nextRefundedCents === $totalCents;
            $this->paymentOrderRepository->recordRefund(
                $paymentOrderId,
                $this->fromCents($nextRefundedCents),
                $fullyRefunded
            );
            $finalProviderStatus = strtoupper(trim($providerStatus));
            if ($finalProviderStatus === '') {
                $finalProviderStatus = 'SUCCESS';
            }
            $this->orderRefundRepo->update($refundId, [
                'status'                 => 'refunded',
                'refund_trade_no'        => $refundTradeNo,
                'refund_trade_no_source' => $refundTradeNoSource,
                // 并发查询可能在成功观察和结算之间写入 PROCESSING；最终结算必须
                // 以触发本次结算的明确成功响应覆盖为成功状态。
                'provider_status'        => substr($finalProviderStatus, 0, 32),
                'provider_checked_at'    => $providerCheckedAt,
                'refunded_at'            => $refundedAt,
            ]);

            $item = $this->orderItemRepository->findForUpdate((int)$refund['order_item_id']);
            if ($item && (int)($item['refund_status'] ?? 0) !== 2) {
                $this->orderItemRepository->setRefundStatus((int)$item['id'], 2);

                // 未发货（paid）的“仅退款”商品仍在仓库，退款后应释放占用库存；
                // 已发货/已完成的“仅退款”不回库。退货退款则在确认收货后回库。
                $shouldRestoreInventory = $this->isReturnRefund($refund)
                    || ($this->isRefundOnly($refund) && $orderStatusBeforeRefund === 'paid');
                if ($shouldRestoreInventory) {
                    $this->restoreItemInventory($item);
                }
            }

            if ($fullyRefunded) {
                // 近乎全额优惠可能产生净实付为 0 的商品行，它无法单独申请退款。
                // 家族支付金额全部退回时，遍历家族每个未发货（paid）订单，
                // 释放这些零实付行库存；商品行锁在家族订单锁之后按订单 id 升序获取。
                foreach ($familyOrders as $familyOrderId => $familyOrder) {
                    if ((string)($familyOrder['status'] ?? '') !== 'paid') {
                        continue;
                    }
                    foreach ($this->orderItemRepository->findByOrderIdForUpdate($familyOrderId) as $remainingItem) {
                        if ((int)($remainingItem['refund_status'] ?? 0) === 2
                            || $this->toCents($remainingItem['pay_amount'] ?? 0) !== 0) {
                            continue;
                        }
                        $this->orderItemRepository->setRefundStatus((int)$remainingItem['id'], 2);
                        $this->restoreItemInventory($remainingItem);
                    }
                }

                // 支付记录挂在根订单名下；家族每个订单都以自身 CAS 关单，
                // 已取消/已关闭的家族成员天然不受影响。
                $this->orderPaymentRepository->markFullyRefunded($rootOrderId);
                foreach ($familyOrderIds as $familyOrderId) {
                    $this->orderOrderRepository->closeAfterFullRefund($familyOrderId);
                }
            }

            return [
                'refund_id'        => $refundId,
                'refund_no'        => (string)$refund['refund_no'],
                'order_id'         => $orderId,
                'order_no'         => (string)($orderRecord['order_no'] ?? ''),
                'order_item_id'    => (int)$refund['order_item_id'],
                'type'             => (string)$refund['type'],
                'refund_amount'    => $this->fromCents($this->toCents($refund['refund_amount'] ?? 0)),
                'refunded_amount'  => $this->fromCents($nextRefundedCents),
                'cumulative_refund_amount' => $this->fromCents($nextRefundedCents),
                'is_fully_refunded' => $fullyRefunded,
                'order_status_before_refund' => $orderStatusBeforeRefund,
                'user_id'          => (int)$refund['user_id'],
                'payment_channel'  => (string)$paymentOrder->channel,
                'payment_trade_no' => (string)($paymentOrder->trade_no ?? ''),
                // 旧消费者仍可读取 trade_no，但其含义明确为原支付交易号。
                'trade_no'         => (string)($paymentOrder->trade_no ?? ''),
                'refund_trade_no'  => $refundTradeNo,
                'refund_trade_no_source' => $refundTradeNoSource,
                'occurred_at'      => $refundedAt,
            ];
        });
    }

    /** @param array<string, mixed> $item */
    private function restoreItemInventory(array $item): void
    {
        $quantity = (int)$item['quantity'];
        $this->goodsSkuRepository->restoreStock((int)$item['sku_id'], $quantity);
        $this->goodsSpuRepository->decSalesCount((int)$item['spu_id'], $quantity);

        $flashItemId = (int)($item['flash_item_id'] ?? 0);
        if ($flashItemId > 0) {
            $this->restoreFlashItemStock($flashItemId, $quantity);
        }
    }

    /**
     * Soft-call flash_sale stock restore. Overridable in unit tests.
     * No-op when plugin is disabled/absent — order_items.flash_item_id is retained.
     */
    protected function restoreFlashItemStock(int $flashItemId, int $quantity): void
    {
        if (!PluginManager::isInstalled('flash_sale')
            || !class_exists('\plugins\flash_sale\repository\MarketingFlashSaleItemRepository')
        ) {
            return;
        }
        app(\plugins\flash_sale\repository\MarketingFlashSaleItemRepository::class)
            ->restoreStock($flashItemId, $quantity);
    }

    /**
     * 获取退款列表
     */
    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->orderRefundRepo->getPageList($params, $page, $limit);
    }

    /**
     * 获取退款详情
     */
    public function getDetail(int $id): ?array
    {
        return $this->orderRefundRepo->getDetail($id);
    }

    /**
     * C 端「我的售后」列表（只读，按订单 user_id 约束）
     */
    public function getMyList(int $userId, array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->orderRefundRepo->getPageListByUserId($userId, $params, $page, $limit);
    }

    /**
     * C 端「我的售后」详情（只读）。
     *
     * 不存在与不属于当前用户统一返回「退款记录不存在」，不泄露记录是否存在。
     */
    public function getMyDetail(int $userId, int $id): array
    {
        $refund = $this->orderRefundRepo->findByIdAndUser($id, $userId);
        if (!$refund) {
            throw new BusinessException('退款记录不存在');
        }

        $item  = $this->orderItemRepository->find((int)$refund['order_item_id']);
        $order = $this->orderOrderRepository->findByIdAndUser((int)$refund['order_id'], $userId);

        return [
            'id'                     => (int)$refund['id'],
            'refund_no'              => (string)($refund['refund_no'] ?? ''),
            'order_id'               => (int)$refund['order_id'],
            'order_no'               => (string)($order['order_no'] ?? ''),
            'order_item_id'          => (int)$refund['order_item_id'],
            'type'                   => (string)($refund['type'] ?? ''),
            'status'                 => (string)($refund['status'] ?? ''),
            'refund_amount'          => $this->fromCents($this->toCents($refund['refund_amount'] ?? 0)),
            'reason'                 => (string)($refund['reason'] ?? ''),
            'description'            => (string)($refund['description'] ?? ''),
            'images'                 => $refund['images'] ?? [],
            'refuse_reason'          => (string)($refund['refuse_reason'] ?? ''),
            'return_express_company' => (string)($refund['return_express_company'] ?? ''),
            'return_express_no'      => (string)($refund['return_express_no'] ?? ''),
            'refunded_at'            => $refund['refunded_at'] ?? null,
            'created_at'             => $refund['created_at'] ?? null,
            'goods_name'             => (string)($item['goods_name'] ?? ''),
            'goods_image'            => (string)($item['goods_image'] ?? ''),
            'spec_text'              => (string)($item['spec_text'] ?? ''),
            'price'                  => (float)($item['price'] ?? 0),
            'quantity'               => (int)($item['quantity'] ?? 0),
        ];
    }

    /**
     * 新订单在下单时已写入行分摊；历史订单在首次售后申请时惰性回填。
     *
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function ensureItemAmountAllocations(array $order, array $items): array
    {
        if ($items === []) {
            throw new BusinessException('订单商品不存在');
        }

        $actualDiscountCents = 0;
        $actualFreightCents = 0;
        $actualPayCents = 0;
        foreach ($items as $item) {
            $actualDiscountCents += $this->toCents($item['discount_amount'] ?? 0);
            $actualFreightCents += $this->toCents($item['freight_amount'] ?? 0);
            $actualPayCents += $this->toCents($item['pay_amount'] ?? 0);
        }

        $alreadyAllocated = $actualDiscountCents === $this->toCents($order['discount_amount'] ?? 0)
            && $actualFreightCents === $this->toCents($order['freight_amount'] ?? 0)
            && $actualPayCents === $this->toCents($order['pay_amount'] ?? 0);
        if ($alreadyAllocated) {
            return $items;
        }

        try {
            $allocations = OrderItemAmountAllocator::allocate(
                array_map(static fn(array $item) => $item['total_amount'] ?? 0, $items),
                $order['discount_amount'] ?? 0,
                $order['freight_amount'] ?? 0
            );
        } catch (\InvalidArgumentException $e) {
            throw new BusinessException('历史订单金额无法分摊：' . $e->getMessage());
        }

        foreach ($items as $index => &$item) {
            $allocation = $allocations[$index];
            $this->orderItemRepository->updateAmountAllocation(
                (int)$item['id'],
                $allocation['discount_amount'],
                $allocation['freight_amount'],
                $allocation['pay_amount']
            );
            $item = array_merge($item, $allocation);
        }
        unset($item);

        return $items;
    }

    private function normalizeRefundType(string $type): string
    {
        if ($type === 'refund_with_goods') {
            return 'return_refund';
        }
        if (!in_array($type, ['refund_only', 'return_refund'], true)) {
            throw new BusinessException('不支持的退款类型');
        }
        return $type;
    }

    /** 未发货订单没有可退回实物，只能走原路仅退款。 */
    private function assertRefundTypeAllowedForOrderStatus(string $type, string $orderStatus): void
    {
        if ($type === 'return_refund' && !in_array($orderStatus, ['shipped', 'completed'], true)) {
            throw new BusinessException('未发货订单仅支持仅退款');
        }
    }

    private function isRefundOnly(array $refund): bool
    {
        return ($refund['type'] ?? '') === 'refund_only';
    }

    private function isReturnRefund(array $refund): bool
    {
        return in_array($refund['type'] ?? '', ['return_refund', 'refund_with_goods'], true);
    }

    /**
     * 金额统一转分比较，避免 float 累加产生 99.999999 之类误判。
     */
    private function toCents(mixed $amount): int
    {
        return (int)round((float)$amount * 100);
    }

    private function fromCents(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
