<?php
declare(strict_types=1);

namespace app\service\order;

use app\model\order\OrderOrder;
use app\repository\order\OrderAdjustLogRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\payment\PaymentOrderRepository;
use app\repository\system\SystemConfigRepository;
use app\service\order\concerns\OrderAdjustGuardTrait;
use app\service\payment\PaymentService;
use app\support\OrderItemAmountAllocator;
use core\base\Service;
use core\exception\BusinessException;

/**
 * 订单合并（仅待付款订单）。
 *
 * 同一用户、同配送方式的多笔待付款订单合并为一笔：
 * 存活单 = created_at 最早（并列取 id 最小），其余订单商品行全部
 * 迁入存活单后按 CAS 取消（cancelForMerge 内部跳过取消副作用重放）。
 * 金额 = 各单聚合后重新推导 pay = goods + freight - discount（分）。
 */
class OrderMergeService extends Service
{
    use OrderAdjustGuardTrait;

    protected OrderOrderRepository $orderOrderRepo;
    protected OrderAdjustLogRepository $orderAdjustLogRepository;
    protected SystemConfigRepository $systemConfigRepository;
    protected PaymentOrderRepository $paymentOrderRepository;
    protected PaymentService $paymentService;

    /**
     * @param array<int, int|string> $orderIds
     * @return array{survivor_order_id:int, survivor_order_no:string, closed_order_ids:array<int, int>}
     */
    public function merge(array $orderIds, int $adminId, string $remark = ''): array
    {
        $ids = array_values(array_unique(array_map('intval', $orderIds)));
        $ids = array_filter($ids, static fn(int $id): bool => $id > 0);
        if (count($ids) < 2) {
            throw new BusinessException('请至少选择两个订单进行合并');
        }
        sort($ids);

        $orders = $this->orderOrderRepo->findByIds($ids);
        if (count($orders) !== count($ids)) {
            throw new BusinessException('部分订单不存在');
        }

        $userId = (int)$orders[array_key_first($orders)]['user_id'];
        $deliveryType = (string)($orders[array_key_first($orders)]['delivery_type'] ?? 'express');
        $pickupStoreId = (int)($orders[array_key_first($orders)]['pickup_store_id'] ?? 0);
        foreach ($orders as $order) {
            if ((int)$order['user_id'] !== $userId) {
                throw new BusinessException('仅同一用户的订单可以合并');
            }
            if (($order['status'] ?? '') !== OrderOrder::STATUS_PENDING) {
                throw new BusinessException('仅待付款订单可以合并');
            }
            if ((string)($order['delivery_type'] ?? 'express') !== $deliveryType) {
                throw new BusinessException('配送方式不同的订单不能合并');
            }
            if ($deliveryType === 'pickup' && (int)($order['pickup_store_id'] ?? 0) !== $pickupStoreId) {
                throw new BusinessException('自提门店不同的订单不能合并');
            }
            $orderId = (int)$order['id'];
            $this->assertNoIssuedInvoice($orderId);
            $this->assertNoActiveRefund($orderId);
            $this->assertNotFlashOrGroupOrder($orderId, $this->orderItemRepository->findByOrderId($orderId));
        }

        $survivorId = $this->pickSurvivorId($orders);

        // 事务外先逐单关闭渠道侧待支付单：合并后各单金额都会失效，
        // 不允许用户按任何一笔旧金额完成支付。
        foreach ($orders as $order) {
            $this->paymentService->closePendingBusinessOrder(
                (string)$order['order_no'],
                (int)$order['user_id'],
                $order['pay_amount'] ?? 0
            );
        }

        $result = $this->runInTransaction(function () use (
            $ids,
            $survivorId,
            $userId,
            $deliveryType,
            $adminId,
            $remark
        ): array {
            // 固定锁序防死锁：按 id 升序逐个 findForUpdate
            $lockedOrders = [];
            foreach ($ids as $id) {
                $order = $this->orderOrderRepo->findForUpdate($id);
                if (!$order
                    || (int)$order['user_id'] !== $userId
                    || ($order['status'] ?? '') !== OrderOrder::STATUS_PENDING
                    || (string)($order['delivery_type'] ?? 'express') !== $deliveryType
                ) {
                    throw new BusinessException('订单状态已变化，请重试');
                }
                $lockedOrders[$id] = $order;
            }

            // 锁内二次断言各单支付单均已关闭（order -> payment 锁序）
            foreach ($lockedOrders as $order) {
                $payments = $this->paymentOrderRepository->getBusinessOrderCandidatesForUpdate(
                    (string)$order['order_no']
                );
                $this->paymentService->assertBusinessPaymentClosed(
                    $payments,
                    (string)$order['order_no'],
                    (int)$order['user_id'],
                    (string)$order['pay_amount']
                );
            }

            $survivor = $lockedOrders[$survivorId];
            $survivorNo = (string)$survivor['order_no'];
            $absorbedIds = array_values(array_filter($ids, static fn(int $id): bool => $id !== $survivorId));

            // 锁定全部商品行（按订单 id 升序），迁移非存活单行到存活单
            $allItems = [];
            foreach ($ids as $id) {
                foreach ($this->orderItemRepository->findByOrderIdForUpdate($id) as $item) {
                    $allItems[] = $item;
                }
            }
            foreach ($allItems as $item) {
                if ((int)$item['order_id'] === $survivorId) {
                    continue;
                }
                if (!$this->orderItemRepository->reassignToOrder((int)$item['id'], $survivorId)) {
                    throw new BusinessException('商品行迁移失败，请重试');
                }
            }

            // ---- 金额聚合（分）----
            $goodsCents = 0;
            $freightCents = 0;
            $discountCents = 0;
            foreach ($lockedOrders as $order) {
                $goodsCents += $this->moneyToCents($order['goods_amount'] ?? 0);
                $freightCents += $this->moneyToCents($order['freight_amount'] ?? 0);
                $discountCents += $this->moneyToCents($order['discount_amount'] ?? 0);
            }
            $payCents = $goodsCents + $freightCents - $discountCents;
            if ($payCents <= 0) {
                throw new BusinessException('合并后实付金额必须大于 0');
            }

            // 存活单全部行重跑行内分摊
            $allocations = OrderItemAmountAllocator::allocate(
                array_map(
                    fn(array $item): string => $this->centsToMoney($this->moneyToCents($item['total_amount'] ?? 0)),
                    $allItems
                ),
                $this->centsToMoney($discountCents),
                $this->centsToMoney($freightCents)
            );
            foreach ($allItems as $index => $item) {
                if (!$this->orderItemRepository->updatePriceAndAllocation((int)$item['id'], [
                    'discount_amount' => $allocations[$index]['discount_amount'],
                    'freight_amount'  => $allocations[$index]['freight_amount'],
                    'pay_amount'      => $allocations[$index]['pay_amount'],
                ])) {
                    throw new BusinessException('商品行分摊更新失败，请重试');
                }
            }

            // 买家备注拼接 + 重置支付倒计时（与 OrderService::create 同配置）
            $remarks = [];
            foreach ($lockedOrders as $order) {
                $buyerRemark = trim((string)($order['buyer_remark'] ?? ''));
                if ($buyerRemark !== '') {
                    $remarks[] = $buyerRemark;
                }
            }
            $autoCancelMinutes = (int)$this->systemConfigRepository->getConfigValue('order.auto_cancel_minutes', 30);
            $autoCancelAt = $autoCancelMinutes > 0
                ? date('Y-m-d H:i:s', time() + $autoCancelMinutes * 60)
                : null;

            $mergedAmounts = [
                'goods_amount'    => $this->centsToMoney($goodsCents),
                'freight_amount'  => $this->centsToMoney($freightCents),
                'discount_amount' => $this->centsToMoney($discountCents),
                'pay_amount'      => $this->centsToMoney($payCents),
                'buyer_remark'    => implode("\n---\n", $remarks),
                'auto_cancel_at'  => $autoCancelAt,
            ];
            if ($this->orderOrderRepo->updateMergedAmounts($survivorId, $mergedAmounts) !== 1) {
                throw new BusinessException('订单状态已变化，请重试');
            }

            // 非存活单 CAS 取消（内部跳过取消副作用重放：不回库存、不退券）
            foreach ($absorbedIds as $absorbedId) {
                if ($this->orderOrderRepo->cancelForMerge(
                    $absorbedId,
                    $survivorId,
                    "已合并至订单 {$survivorNo}"
                ) !== 1) {
                    throw new BusinessException('订单状态已变化，请重试');
                }
                // 优惠券改绑到存活单，保证存活单取消/退款时可正确退还
                $this->rebindOrderCoupon($absorbedId, $survivorId);
            }

            $this->orderAdjustLogRepository->create([
                'order_id'          => $survivorId,
                'action'            => 'merge',
                'related_order_ids' => $absorbedIds,
                'admin_id'          => $adminId,
                'before_snapshot'   => [
                    'goods_amount'    => $this->centsToMoney($this->moneyToCents($survivor['goods_amount'] ?? 0)),
                    'freight_amount'  => $this->centsToMoney($this->moneyToCents($survivor['freight_amount'] ?? 0)),
                    'discount_amount' => $this->centsToMoney($this->moneyToCents($survivor['discount_amount'] ?? 0)),
                    'pay_amount'      => $this->centsToMoney($this->moneyToCents($survivor['pay_amount'] ?? 0)),
                ],
                'after_snapshot'    => [
                    'goods_amount'    => $mergedAmounts['goods_amount'],
                    'freight_amount'  => $mergedAmounts['freight_amount'],
                    'discount_amount' => $mergedAmounts['discount_amount'],
                    'pay_amount'      => $mergedAmounts['pay_amount'],
                ],
                'remark'            => $remark,
            ]);

            return [
                'survivor_order_id' => $survivorId,
                'survivor_order_no' => $survivorNo,
                'closed_order_ids'  => $absorbedIds,
            ];
        });

        $this->trigger('order.merged', [
            'survivor_order_id' => $result['survivor_order_id'],
            'merged_order_ids'  => $result['closed_order_ids'],
        ]);

        return $result;
    }

    /**
     * 存活单 = created_at 最早，并列取 id 最小。
     *
     * @param array<int, array<string, mixed>> $orders
     */
    private function pickSurvivorId(array $orders): int
    {
        $survivorId = 0;
        $earliest = null;
        foreach ($orders as $order) {
            $createdAt = (string)($order['created_at'] ?? '');
            $orderId = (int)$order['id'];
            if ($earliest === null
                || $createdAt < $earliest
                || ($createdAt === $earliest && $orderId < $survivorId)
            ) {
                $earliest = $createdAt;
                $survivorId = $orderId;
            }
        }
        return $survivorId;
    }

    /** 优惠券改绑保留为可测试边界；实现由插件 hook。 */
    protected function rebindOrderCoupon(int $fromOrderId, int $toOrderId): void
    {
        \core\plugin\HookManager::apply('order.rebind_coupon', [
            'from_order_id' => $fromOrderId,
            'to_order_id'   => $toOrderId,
        ]);
    }
}
