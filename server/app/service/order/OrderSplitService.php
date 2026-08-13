<?php
declare(strict_types=1);

namespace app\service\order;

use app\model\order\OrderOrder;
use app\repository\order\OrderAdjustLogRepository;
use app\repository\order\OrderOrderRepository;
use app\service\order\concerns\OrderAdjustGuardTrait;
use app\service\store\StoreService;
use app\support\OrderItemAmountAllocator;
use core\base\Service;
use core\exception\BusinessException;

/**
 * 订单拆分（仅已付款订单）。
 *
 * 把部分商品行（整行或部分数量）拆到新的子订单，父/子订单金额
 * 全部用整数分计算并断言 父.pay + 子.pay === 原.pay，
 * 优惠/运费按商品金额比例向下取整分给子单、余数归父单。
 */
class OrderSplitService extends Service
{
    use OrderAdjustGuardTrait;

    protected OrderOrderRepository $orderOrderRepo;
    protected OrderAdjustLogRepository $orderAdjustLogRepository;
    protected StoreService $storeService;

    /**
     * @param array<int, array{item_id:int|string, quantity:int|string}> $lines 要拆出的行及数量
     * @return array{parent_order_id:int, child_order_id:int, child_order_no:string}
     */
    public function split(int $orderId, array $lines, int $adminId, string $remark = ''): array
    {
        $snapshot = $this->orderOrderRepo->find($orderId);
        if (!$snapshot) {
            throw new BusinessException('订单不存在');
        }
        if (($snapshot['status'] ?? '') !== OrderOrder::STATUS_PAID) {
            throw new BusinessException('仅已付款订单可以拆分');
        }

        $this->assertNoIssuedInvoice($orderId);
        $this->assertNoActiveRefund($orderId);

        $items = $this->orderItemRepository->findByOrderId($orderId);
        $this->assertNotFlashOrGroupOrder($orderId, $items);
        $this->assertNotVirtualOrder($orderId);
        $splitQuantities = $this->validateLines($lines, $items);

        $result = $this->runInTransaction(function () use (
            $orderId,
            $lines,
            $splitQuantities,
            $adminId,
            $remark
        ): array {
            // 锁序：订单 -> 商品行
            $order = $this->orderOrderRepo->findForUpdate($orderId);
            if (!$order || ($order['status'] ?? '') !== OrderOrder::STATUS_PAID) {
                throw new BusinessException('订单状态已变化，请重试');
            }
            $lockedItems = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
            // 锁内以最新行数据二次校验（数量/售后状态/全拆）
            $splitQuantities = $this->validateLines($lines, $lockedItems);

            $itemMap = [];
            foreach ($lockedItems as $row) {
                $itemMap[(int)$row['id']] = $row;
            }

            // ---- 金额计算（全部为分）----
            $origGoodsCents = $this->moneyToCents($order['goods_amount'] ?? 0);
            $origFreightCents = $this->moneyToCents($order['freight_amount'] ?? 0);
            $origDiscountCents = $this->moneyToCents($order['discount_amount'] ?? 0);
            $origPayCents = $this->moneyToCents($order['pay_amount'] ?? 0);

            $parentLines = [];   // [{item_id, quantity, price, total_cents}] 拆分后父单保留行
            $childLines = [];    // [{item_id, quantity, price, total_cents, full}] 子单行
            $totalGoodsCents = 0;
            $childGoodsCents = 0;

            foreach ($lockedItems as $row) {
                $itemId = (int)$row['id'];
                $quantity = (int)$row['quantity'];
                $priceCents = $this->moneyToCents($row['price'] ?? 0);
                $totalGoodsCents += $priceCents * $quantity;

                $splitQty = $splitQuantities[$itemId] ?? 0;
                $remainQty = $quantity - $splitQty;
                if ($splitQty > 0) {
                    $childGoodsCents += $priceCents * $splitQty;
                    $childLines[] = [
                        'item_id'     => $itemId,
                        'quantity'    => $splitQty,
                        'price'       => $this->centsToMoney($priceCents),
                        'total_cents' => $priceCents * $splitQty,
                        'full'        => $remainQty === 0,
                    ];
                }
                if ($remainQty > 0) {
                    $parentLines[] = [
                        'item_id'     => $itemId,
                        'quantity'    => $remainQty,
                        'price'       => $this->centsToMoney($priceCents),
                        'total_cents' => $priceCents * $remainQty,
                    ];
                }
            }

            if ($totalGoodsCents !== $origGoodsCents
                || $origPayCents !== $origGoodsCents + $origFreightCents - $origDiscountCents
            ) {
                throw new BusinessException('订单金额数据异常，无法拆分');
            }

            $parentGoodsCents = $totalGoodsCents - $childGoodsCents;

            // 优惠/运费按子单商品占比向下取整分给子单，余数归父单
            $childDiscountCents = intdiv($origDiscountCents * $childGoodsCents, $totalGoodsCents);
            $childFreightCents = intdiv($origFreightCents * $childGoodsCents, $totalGoodsCents);
            $parentDiscountCents = $origDiscountCents - $childDiscountCents;
            $parentFreightCents = $origFreightCents - $childFreightCents;

            $childPayCents = $childGoodsCents + $childFreightCents - $childDiscountCents;
            $parentPayCents = $parentGoodsCents + $parentFreightCents - $parentDiscountCents;
            if ($parentPayCents + $childPayCents !== $origPayCents) {
                throw new BusinessException('拆分金额校验失败，已回滚');
            }

            // 父/子两侧各自跑一次行内分摊
            $parentAllocations = OrderItemAmountAllocator::allocate(
                array_map(fn(array $l): string => $this->centsToMoney($l['total_cents']), $parentLines),
                $this->centsToMoney($parentDiscountCents),
                $this->centsToMoney($parentFreightCents)
            );
            $childAllocations = OrderItemAmountAllocator::allocate(
                array_map(fn(array $l): string => $this->centsToMoney($l['total_cents']), $childLines),
                $this->centsToMoney($childDiscountCents),
                $this->centsToMoney($childFreightCents)
            );

            // ---- 创建子订单 ----
            $childOrderNo = $this->orderOrderRepo->generateOrderNo();
            $child = $this->orderOrderRepo->create([
                'order_no'              => $childOrderNo,
                'user_id'               => (int)$order['user_id'],
                'status'                => OrderOrder::STATUS_PAID,
                'delivery_type'         => $order['delivery_type'] ?? 'express',
                'goods_amount'          => $this->centsToMoney($childGoodsCents),
                'freight_amount'        => $this->centsToMoney($childFreightCents),
                'discount_amount'       => $this->centsToMoney($childDiscountCents),
                'pay_amount'            => $this->centsToMoney($childPayCents),
                'address_snapshot'      => $order['address_snapshot'] ?? [],
                'pay_type'              => $order['pay_type'] ?? '',
                'pay_time'              => $order['pay_time'] ?? null,
                'parent_order_id'       => $orderId,
                'relation_type'         => 'split_child',
                'payment_root_order_id' => (int)($order['payment_root_order_id'] ?? 0) > 0
                    ? (int)$order['payment_root_order_id']
                    : $orderId,
                'auto_cancel_at'        => null,
            ]);
            $childOrderId = (int)$child['id'];

            // 自提子单：照抄 OrderService::create 的自提分支重新生成自提码，
            // 门店与超时时间直接复制父单
            if (($order['delivery_type'] ?? 'express') === 'pickup') {
                $pickupStoreId = (int)($order['pickup_store_id'] ?? 0);
                if ($pickupStoreId <= 0) {
                    throw new BusinessException('自提订单缺少门店信息，无法拆分');
                }
                $code = $this->storeService->generatePickupCode($pickupStoreId);
                $this->orderOrderRepo->update($childOrderId, [
                    'pickup_store_id'   => $pickupStoreId,
                    'pickup_code'       => $code,
                    'pickup_status'     => 'pending',
                    'pickup_timeout_at' => $order['pickup_timeout_at'] ?? null,
                ]);
                $this->trigger('order.pickup_code.generated', [
                    'order_id'        => $childOrderId,
                    'pickup_code'     => $code,
                    'pickup_store_id' => $pickupStoreId,
                ]);
            }

            // ---- 商品行迁移 ----
            foreach ($childLines as $index => $line) {
                $alloc = $childAllocations[$index];
                $itemId = (int)$line['item_id'];
                if ($line['full']) {
                    // 整行迁移
                    if (!$this->orderItemRepository->reassignToOrder($itemId, $childOrderId)) {
                        throw new BusinessException('商品行迁移失败，请重试');
                    }
                    if (!$this->orderItemRepository->updatePriceAndAllocation($itemId, [
                        'discount_amount' => $alloc['discount_amount'],
                        'freight_amount'  => $alloc['freight_amount'],
                        'pay_amount'      => $alloc['pay_amount'],
                    ])) {
                        throw new BusinessException('商品行分摊更新失败，请重试');
                    }
                    continue;
                }
                // 数量拆分：子单新建行，复制商品快照字段
                $source = $itemMap[$itemId];
                $this->orderItemRepository->create([
                    'order_id'           => $childOrderId,
                    'spu_id'             => (int)$source['spu_id'],
                    'sku_id'             => (int)$source['sku_id'],
                    'flash_item_id'      => 0,
                    'split_from_item_id' => $itemId,
                    'goods_name'         => (string)($source['goods_name'] ?? ''),
                    'goods_image'        => (string)($source['goods_image'] ?? ''),
                    'spec_text'          => (string)($source['spec_text'] ?? ''),
                    'price'              => $line['price'],
                    'quantity'           => $line['quantity'],
                    'total_amount'       => $this->centsToMoney($line['total_cents']),
                    'discount_amount'    => $alloc['discount_amount'],
                    'freight_amount'     => $alloc['freight_amount'],
                    'pay_amount'         => $alloc['pay_amount'],
                ]);
            }

            // 父单保留行：数量拆分行回落剩余数量 + 全部保留行重写分摊
            foreach ($parentLines as $index => $line) {
                $alloc = $parentAllocations[$index];
                if (!$this->orderItemRepository->updatePriceAndAllocation((int)$line['item_id'], [
                    'quantity'        => $line['quantity'],
                    'total_amount'    => $this->centsToMoney($line['total_cents']),
                    'discount_amount' => $alloc['discount_amount'],
                    'freight_amount'  => $alloc['freight_amount'],
                    'pay_amount'      => $alloc['pay_amount'],
                ])) {
                    throw new BusinessException('商品行分摊更新失败，请重试');
                }
            }

            // 父单聚合金额更新。
            // 注意：updateAmountsIfPending 是待付款改价专用（CAS status=pending），
            // 父单此刻是 paid；本事务开头已 findForUpdate 加行锁并校验 status=paid，
            // 锁内串行化后基类通用 update() 是安全的。
            $parentAfter = [
                'goods_amount'    => $this->centsToMoney($parentGoodsCents),
                'freight_amount'  => $this->centsToMoney($parentFreightCents),
                'discount_amount' => $this->centsToMoney($parentDiscountCents),
                'pay_amount'      => $this->centsToMoney($parentPayCents),
            ];
            if (!$this->orderOrderRepo->update($orderId, $parentAfter)) {
                throw new BusinessException('父订单金额更新失败，请重试');
            }

            $this->orderAdjustLogRepository->create([
                'order_id'          => $orderId,
                'action'            => 'split',
                'related_order_ids' => [$childOrderId],
                'admin_id'          => $adminId,
                'before_snapshot'   => [
                    'goods_amount'    => $this->centsToMoney($origGoodsCents),
                    'freight_amount'  => $this->centsToMoney($origFreightCents),
                    'discount_amount' => $this->centsToMoney($origDiscountCents),
                    'pay_amount'      => $this->centsToMoney($origPayCents),
                ],
                'after_snapshot'    => [
                    'parent' => $parentAfter,
                    'child'  => [
                        'order_no'        => $childOrderNo,
                        'goods_amount'    => $this->centsToMoney($childGoodsCents),
                        'freight_amount'  => $this->centsToMoney($childFreightCents),
                        'discount_amount' => $this->centsToMoney($childDiscountCents),
                        'pay_amount'      => $this->centsToMoney($childPayCents),
                    ],
                ],
                'remark'            => $remark,
            ]);

            return [
                'parent_order_id' => $orderId,
                'child_order_id'  => $childOrderId,
                'child_order_no'  => $childOrderNo,
                'delivery_type'   => $order['delivery_type'] ?? 'express',
                'address_snapshot' => $order['address_snapshot'] ?? [],
            ];
        });

        $this->trigger('order.split.completed', [
            'parent_order_id'  => $result['parent_order_id'],
            'child_order_id'   => $result['child_order_id'],
            'delivery_type'    => $result['delivery_type'],
            'address_snapshot' => $result['address_snapshot'],
        ]);

        return [
            'parent_order_id' => $result['parent_order_id'],
            'child_order_id'  => $result['child_order_id'],
            'child_order_no'  => $result['child_order_no'],
        ];
    }

    /**
     * 校验拆分行并返回 item_id => 拆出数量。
     *
     * @param array<int, mixed> $lines
     * @param array<int, array<string, mixed>> $items
     * @return array<int, int>
     */
    private function validateLines(array $lines, array $items): array
    {
        if (empty($lines)) {
            throw new BusinessException('请选择要拆分的商品行');
        }
        $itemMap = [];
        foreach ($items as $row) {
            $itemMap[(int)$row['id']] = $row;
        }

        $splitQuantities = [];
        foreach ($lines as $line) {
            $itemId = (int)($line['item_id'] ?? 0);
            $quantity = (int)($line['quantity'] ?? 0);
            if ($itemId <= 0 || $quantity <= 0) {
                throw new BusinessException('拆分行参数错误');
            }
            if (isset($splitQuantities[$itemId])) {
                throw new BusinessException("商品行(id={$itemId})重复");
            }
            $item = $itemMap[$itemId] ?? null;
            if (!$item) {
                throw new BusinessException("商品行(id={$itemId})不属于该订单");
            }
            if ((int)($item['refund_status'] ?? 0) !== 0) {
                throw new BusinessException("商品行(id={$itemId})存在售后记录，不能拆分");
            }
            if ($quantity > (int)$item['quantity']) {
                throw new BusinessException("商品行(id={$itemId})拆分数量超过可拆数量");
            }
            $splitQuantities[$itemId] = $quantity;
        }

        // 订单级禁止全拆：父单至少保留一行 quantity > 0
        $parentHasRemaining = false;
        foreach ($itemMap as $itemId => $item) {
            if ((int)$item['quantity'] - ($splitQuantities[$itemId] ?? 0) > 0) {
                $parentHasRemaining = true;
                break;
            }
        }
        if (!$parentHasRemaining) {
            throw new BusinessException('不能拆出全部商品，父订单至少保留一件');
        }

        return $splitQuantities;
    }
}
