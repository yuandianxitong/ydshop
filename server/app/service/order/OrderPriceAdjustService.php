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
 * 订单改价（仅待付款订单）。
 *
 * 流程与取消订单同构：先在事务外关闭渠道支付单，
 * 再进事务按 order -> payment -> items 锁序做 CAS 更新，
 * 保证用户不可能按旧金额完成支付。
 */
class OrderPriceAdjustService extends Service
{
    use OrderAdjustGuardTrait;

    protected OrderOrderRepository $orderOrderRepo;
    protected PaymentOrderRepository $paymentOrderRepository;
    protected PaymentService $paymentService;
    protected OrderQueryService $orderQueryService;
    protected OrderAdjustLogRepository $orderAdjustLogRepository;
    protected SystemConfigRepository $systemConfigRepository;

    /**
     * @param array<int, array{item_id:int|string, price:string|int|float}> $items 需要改价的行（可只传部分行）
     * @param string|null $freightAmount  null 表示沿用原运费
     * @param string|null $discountAmount null 表示沿用原优惠
     */
    public function adjust(
        int $orderId,
        array $items,
        ?string $freightAmount,
        ?string $discountAmount,
        int $adminId,
        string $remark = ''
    ): array {
        $snapshot = $this->orderOrderRepo->find($orderId);
        if (!$snapshot) {
            throw new BusinessException('订单不存在');
        }
        if (($snapshot['status'] ?? '') !== OrderOrder::STATUS_PENDING) {
            throw new BusinessException('仅待付款订单可以改价');
        }

        $priceOverrides = $this->normalizePriceOverrides($items);

        // 事务外先关闭渠道侧待支付单（与取消订单一致），失败则不进入改价事务。
        $this->paymentService->closePendingBusinessOrder(
            (string)$snapshot['order_no'],
            (int)$snapshot['user_id'],
            $snapshot['pay_amount'] ?? 0
        );

        $this->runInTransaction(function () use (
            $orderId,
            $priceOverrides,
            $freightAmount,
            $discountAmount,
            $adminId,
            $remark
        ): void {
            $order = $this->orderOrderRepo->findForUpdate($orderId);
            if (!$order || ($order['status'] ?? '') !== OrderOrder::STATUS_PENDING) {
                throw new BusinessException('订单状态已变化，请重试');
            }

            // 锁内二次断言：支付单要么不存在、要么已安全关闭且与订单一致。
            $payments = $this->paymentOrderRepository->getBusinessOrderCandidatesForUpdate(
                (string)$order['order_no']
            );
            $this->paymentService->assertBusinessPaymentClosed(
                $payments,
                (string)$order['order_no'],
                (int)$order['user_id'],
                (string)$order['pay_amount']
            );

            $lockedItems = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
            if (empty($lockedItems)) {
                throw new BusinessException('订单缺少商品明细');
            }

            $itemIds = array_map(static fn(array $row): int => (int)$row['id'], $lockedItems);
            foreach (array_keys($priceOverrides) as $itemId) {
                if (!in_array($itemId, $itemIds, true)) {
                    throw new BusinessException("商品行(id={$itemId})不属于该订单");
                }
            }

            // 覆盖行单价并重算行小计（bcmath，两位小数）
            $lineTotals = [];
            $lineUpdates = [];
            $goodsCents = 0;
            foreach ($lockedItems as $item) {
                $itemId = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $price = array_key_exists($itemId, $priceOverrides)
                    ? $priceOverrides[$itemId]
                    : $this->centsToMoney($this->moneyToCents($item['price'] ?? 0));
                $total = bcmul($price, (string)$quantity, 2);
                $goodsCents += $this->moneyToCents($total);
                $lineTotals[] = $total;
                $lineUpdates[$itemId] = ['price' => $price, 'total_amount' => $total];
            }

            $freight = $freightAmount !== null
                ? $this->centsToMoney($this->moneyToCents($freightAmount))
                : $this->centsToMoney($this->moneyToCents($order['freight_amount'] ?? 0));
            $discount = $discountAmount !== null
                ? $this->centsToMoney($this->moneyToCents($discountAmount))
                : $this->centsToMoney($this->moneyToCents($order['discount_amount'] ?? 0));

            $discountCents = $this->moneyToCents($discount);
            $freightCents = $this->moneyToCents($freight);
            if ($discountCents > $goodsCents) {
                throw new BusinessException('优惠金额不能超过商品总额');
            }

            $payCents = $goodsCents + $freightCents - $discountCents;
            if ($payCents <= 0) {
                throw new BusinessException('改价后实付金额必须大于 0');
            }

            // 行内重新分摊优惠/运费/净实付
            $allocations = OrderItemAmountAllocator::allocate($lineTotals, $discount, $freight);
            foreach (array_keys($lineUpdates) as $index => $itemId) {
                if (!$this->orderItemRepository->updatePriceAndAllocation($itemId, [
                    'price'           => $lineUpdates[$itemId]['price'],
                    'total_amount'    => $lineUpdates[$itemId]['total_amount'],
                    'discount_amount' => $allocations[$index]['discount_amount'],
                    'freight_amount'  => $allocations[$index]['freight_amount'],
                    'pay_amount'      => $allocations[$index]['pay_amount'],
                ])) {
                    throw new BusinessException('商品行改价失败，请重试');
                }
            }

            $afterAmounts = [
                'goods_amount'    => $this->centsToMoney($goodsCents),
                'freight_amount'  => $freight,
                'discount_amount' => $discount,
                'pay_amount'      => $this->centsToMoney($payCents),
            ];
            // 改价后重置自动取消倒计时（与合单一致）：给用户完整支付窗口，
            // 避免临近超时改价后订单在用户按新价支付前被 order:auto-cancel 取消
            $autoCancelMinutes = (int)$this->systemConfigRepository->getConfigValue('order.auto_cancel_minutes', 30);
            $update = $afterAmounts + [
                'auto_cancel_at' => $autoCancelMinutes > 0
                    ? date('Y-m-d H:i:s', time() + $autoCancelMinutes * 60)
                    : null,
            ];
            if ($this->orderOrderRepo->updateAmountsIfPending($orderId, $update) !== 1) {
                throw new BusinessException('订单状态已变化，请重试');
            }

            $this->orderAdjustLogRepository->create([
                'order_id'          => $orderId,
                'action'            => 'price_adjust',
                'related_order_ids' => [],
                'admin_id'          => $adminId,
                'before_snapshot'   => $this->amountSnapshot($order),
                'after_snapshot'    => $afterAmounts,
                'remark'            => $remark,
            ]);
        });

        return $this->orderQueryService->getDetail($orderId);
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, string> item_id => 规范化两位小数价格
     */
    private function normalizePriceOverrides(array $items): array
    {
        if (empty($items)) {
            throw new BusinessException('请至少提供一条改价商品行');
        }
        $overrides = [];
        foreach ($items as $row) {
            $itemId = (int)($row['item_id'] ?? 0);
            if ($itemId <= 0) {
                throw new BusinessException('商品行参数错误');
            }
            if (isset($overrides[$itemId])) {
                throw new BusinessException("商品行(id={$itemId})重复");
            }
            if (!isset($row['price'])) {
                throw new BusinessException("商品行(id={$itemId})缺少改价金额");
            }
            $priceCents = $this->moneyToCents($row['price']);
            if ($priceCents <= 0) {
                throw new BusinessException('商品单价必须大于 0');
            }
            $overrides[$itemId] = $this->centsToMoney($priceCents);
        }
        return $overrides;
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, string>
     */
    private function amountSnapshot(array $order): array
    {
        return [
            'goods_amount'    => $this->centsToMoney($this->moneyToCents($order['goods_amount'] ?? 0)),
            'freight_amount'  => $this->centsToMoney($this->moneyToCents($order['freight_amount'] ?? 0)),
            'discount_amount' => $this->centsToMoney($this->moneyToCents($order['discount_amount'] ?? 0)),
            'pay_amount'      => $this->centsToMoney($this->moneyToCents($order['pay_amount'] ?? 0)),
        ];
    }
}
