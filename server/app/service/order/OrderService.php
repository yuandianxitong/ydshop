<?php
declare(strict_types=1);

namespace app\service\order;

use app\model\order\OrderOrder;
use app\model\payment\PaymentOrder;
use app\repository\delivery\DeliveryOrderRepository;
use app\repository\goods\GoodsSkuRepository;
use app\repository\goods\GoodsSpuRepository;
use app\repository\goods\GoodsVirtualItemRepository;
use app\repository\order\OrderItemRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\payment\PaymentOrderRepository;
use app\repository\StoreRepository;
use app\repository\system\SystemConfigRepository;
use app\repository\user\UserRepository;
use app\service\goods\GoodsFreightTemplateService;
use app\service\payment\PaymentService;
use app\service\store\StoreService;
use app\support\OrderItemAmountAllocator;
use core\base\Service;
use core\exception\BusinessException;
use core\plugin\HookManager;

class OrderService extends Service
{
    public const AUTO_CANCEL_CANCELLED = 'cancelled';
    public const AUTO_CANCEL_REPLAYED = 'replayed';
    public const AUTO_CANCEL_SKIPPED = 'skipped';
    public const AUTO_CANCEL_CONTENDED = 'contended';

    protected OrderOrderRepository $orderOrderRepo;
    protected OrderItemRepository $orderItemRepository;
    protected OrderQueryService $orderQueryService;
    protected GoodsSkuRepository $goodsSkuRepository;
    protected GoodsSpuRepository $goodsSpuRepository;
    protected GoodsVirtualItemRepository $goodsVirtualItemRepository;
    protected StoreRepository $storeRepository;
    protected SystemConfigRepository $systemConfigRepository;
    protected StoreService $storeService;
    protected GoodsFreightTemplateService $goodsFreightTemplateService;
    protected UserRepository $userRepository;
    protected PaymentOrderRepository $paymentOrderRepository;
    protected PaymentService $paymentService;
    protected DeliveryOrderRepository $deliveryOrderRepository;
    protected OrderPayService $orderPayService;

    /**
     * 创建订单
     *
     * @param int    $userId      用户ID
     * @param array  $items       [['sku_id' => int, 'quantity' => int], ...]
     * @param array  $address     地址快照
     * @param string $buyerRemark 买家备注
     * @param array  $data        扩展参数（coupon_user_id 等）
     * @return array
     */
    public function create(int $userId, array $items, array $address, string $buyerRemark = '', array $data = []): array
    {
        if (empty($items)) {
            throw new BusinessException('购买商品不能为空');
        }

        $created = $this->runInTransaction(function () use ($userId, $items, $address, $buyerRemark, $data) {
            $goodsAmount    = '0.00';
            $orderItemsData = [];

            // 拼团关联（订单创建后再写入 marketing_group / members）
            $groupAssoc = null;

            foreach ($items as $item) {
                $skuId           = (int)($item['sku_id'] ?? 0);
                $quantity        = (int)($item['quantity'] ?? 0);
                $flashItemId     = (int)($item['flash_item_id'] ?? 0);
                $groupActivityId = (int)($item['group_activity_id'] ?? 0);
                $groupId         = (int)($item['group_id'] ?? 0);

                if ($skuId <= 0 || $quantity <= 0) {
                    throw new BusinessException('商品参数错误');
                }

                $sku = $this->goodsSkuRepository->findActiveById($skuId);
                if (!$sku) {
                    throw new BusinessException("商品SKU(id={$skuId})不存在或已下架");
                }

                $spu = $this->goodsSpuRepository->findOnSaleById((int)$sku['spu_id']);
                if (!$spu) {
                    throw new BusinessException("商品(id={$sku['spu_id']})不存在或已下架");
                }

                if (!$this->goodsSkuRepository->deductStock($skuId, $quantity)) {
                    throw new BusinessException("商品「{$spu['name']}」库存不足");
                }

                $lineState = HookManager::apply('order.prepare_line', [
                    'user_id'            => $userId,
                    'sku_id'             => $skuId,
                    'quantity'           => $quantity,
                    'flash_item_id'      => $flashItemId,
                    'group_activity_id'  => $groupActivityId,
                    'group_id'           => $groupId,
                    'sku'                => $sku,
                    'spu'                => $spu,
                ], [
                    'unit_price'    => (string) $sku['price'],
                    'handled_flash' => false,
                    'handled_group' => false,
                    'order_extra'   => [],
                ]);
                if ($flashItemId > 0 && empty($lineState['handled_flash'])) {
                    throw new BusinessException('秒杀活动不可用');
                }
                if (($groupActivityId > 0 || $groupId > 0) && empty($lineState['handled_group'])) {
                    throw new BusinessException('拼团活动不可用');
                }
                $unitPrice = (string) ($lineState['unit_price'] ?? $sku['price']);
                if (!empty($lineState['order_extra']) && is_array($lineState['order_extra'])) {
                    $groupAssoc = $lineState['order_extra'];
                }

                $lineTotal   = bcmul($unitPrice, (string)$quantity, 2);
                $goodsAmount = bcadd($goodsAmount, $lineTotal, 2);

                $orderItemsData[] = [
                    'sku'      => $sku,
                    'spu'      => $spu,
                    'quantity' => $quantity,
                    'price'    => $unitPrice,
                    'total'    => $lineTotal,
                    'flash_item_id' => $flashItemId,
                ];
            }

            $freightAmount = '0.00';

            // 同城配送：根据用户地址 lng/lat 与最近门店计算运费 + 校验配送范围
            if (($data['delivery_type'] ?? 'express') === 'merchant') {
                $freightAmount = $this->computeMerchantFreight(
                    isset($address['lng']) ? (float)$address['lng'] : 0,
                    isset($address['lat']) ? (float)$address['lat'] : 0,
                    (float)$goodsAmount,
                );
            } elseif (($data['delivery_type'] ?? 'express') === 'express') {
                $freightAmount = $this->goodsFreightTemplateService->calculateOrderFreight($orderItemsData, $address);
            }

            $freightAmount = $this->applyFreightBenefits(
                $userId,
                (float)$goodsAmount,
                array_map(fn($r) => (int)$r['spu']['id'], $orderItemsData),
                $freightAmount
            );

            // Build hook context for marketing plugins
            $hookContext = [
                'user_id'        => $userId,
                'goods_amount'   => (float)$goodsAmount,
                'spu_ids'        => array_map(fn($r) => (int)$r['spu']['id'], $orderItemsData),
                'coupon_user_id' => (int)($data['coupon_user_id'] ?? 0),
            ];

            // Execute discount hook chain: full-discount (priority 10) → coupon (priority 20)
            $discountAmount = '0.00';
            if (HookManager::has('order.calc_discount')) {
                $discount       = HookManager::apply('order.calc_discount', $hookContext, 0);
                $discountAmount = (string)min((float)$discount, (float)$goodsAmount);
            }

            $payAmount = bcsub(bcadd($goodsAmount, $freightAmount, 2), $discountAmount, 2);

            $lineAllocations = OrderItemAmountAllocator::allocate(
                array_column($orderItemsData, 'total'),
                $discountAmount,
                $freightAmount
            );
            foreach ($orderItemsData as $index => &$orderItemData) {
                $orderItemData['discount_amount'] = $lineAllocations[$index]['discount_amount'];
                $orderItemData['freight_amount'] = $lineAllocations[$index]['freight_amount'];
                $orderItemData['pay_amount'] = $lineAllocations[$index]['pay_amount'];
            }
            unset($orderItemData);

            $orderNo = OrderOrder::generateOrderNo();

            $autoCancelMinutes = (int)$this->systemConfigRepository->getConfigValue('order.auto_cancel_minutes', 30);
            $autoCancelAt      = $autoCancelMinutes > 0 ? date('Y-m-d H:i:s', time() + $autoCancelMinutes * 60) : null;

            $order = $this->orderOrderRepo->create([
                'order_no'         => $orderNo,
                'user_id'          => $userId,
                'status'           => OrderOrder::STATUS_PENDING,
                'delivery_type'    => $data['delivery_type'] ?? 'express',
                'goods_amount'     => $goodsAmount,
                'freight_amount'   => $freightAmount,
                'discount_amount'  => $discountAmount,
                'pay_amount'       => $payAmount,
                'buyer_remark'     => $buyerRemark,
                'address_snapshot' => $address,
                'auto_cancel_at'   => $autoCancelAt,
            ]);

            $orderId = (int)$order['id'];

            foreach ($orderItemsData as $row) {
                $skuRow = $row['sku'];
                $spuRow = $row['spu'];

                $images = $spuRow['images'] ?? [];
                if (is_string($images)) {
                    $decoded = json_decode($images, true);
                    $images  = is_array($decoded) ? $decoded : [];
                }
                $goodsImage = !empty($images) ? (string)($images[0] ?? '') : (string)($skuRow['image'] ?? '');

                $this->orderItemRepository->create([
                    'order_id'     => $orderId,
                    'spu_id'       => (int)$spuRow['id'],
                    'sku_id'       => (int)$skuRow['id'],
                    'flash_item_id'=> (int)($row['flash_item_id'] ?? 0),
                    'goods_name'   => (string)$spuRow['name'],
                    'goods_image'  => $goodsImage,
                    'spec_text'    => (string)($skuRow['spec_text'] ?? ''),
                    'price'        => $row['price'],
                    'quantity'     => $row['quantity'],
                    'total_amount' => $row['total'],
                    'discount_amount' => $row['discount_amount'],
                    'freight_amount'  => $row['freight_amount'],
                    'pay_amount'      => $row['pay_amount'],
                ]);

                $this->goodsSpuRepository->incSalesCount((int)$spuRow['id'], $row['quantity']);
            }

            // 优惠券核销 / 开团参团：插件 hook，必须在主事务内；失败则整单回滚。
            HookManager::apply('order.after_create', [
                'order_id'       => $orderId,
                'user_id'        => $userId,
                'coupon_user_id' => (int) ($data['coupon_user_id'] ?? 0),
                'order_extra'    => $groupAssoc ?? [],
            ]);

            // 自提订单：生成自提码并写入门店信息
            if (($data['delivery_type'] ?? 'express') === 'pickup') {
                $pickupStoreId = (int)($data['pickup_store_id'] ?? 0);
                if ($pickupStoreId <= 0) {
                    throw new BusinessException('请选择自提门店');
                }
                $code        = $this->storeService->generatePickupCode($pickupStoreId);
                $timeoutDays = (int)$this->systemConfigRepository->getConfigValue('pickup_timeout_days', 7);
                $this->orderOrderRepo->update($orderId, [
                    'pickup_store_id'   => $pickupStoreId,
                    'pickup_code'       => $code,
                    'pickup_status'     => 'pending',
                    'pickup_timeout_at' => date('Y-m-d H:i:s', strtotime("+{$timeoutDays} days")),
                ]);
                // 触发事件（事务内；Listener 失败不影响主流程可放事务外，但 code 已生成放内保证一致）
                $this->trigger('order.pickup_code.generated', [
                    'order_id'        => $orderId,
                    'pickup_code'     => $code,
                    'pickup_store_id' => $pickupStoreId,
                ]);
            }

            // 优惠券/运费权益全额抵扣后：事务内完成零元支付，避免卡在 pending
            $zeroPay = null;
            if ($this->moneyToCents($payAmount) === 0) {
                $zeroPay = $this->orderPayService->completeZeroPay($orderId);
            }

            return [
                'order_id' => $orderId,
                'zero_pay' => $zeroPay,
            ];
        });

        $orderId = (int)$created['order_id'];
        $zeroPay = $created['zero_pay'] ?? null;

        $this->trigger('order.created', [
            'order_id'       => $orderId,
            'order_no'       => $this->orderOrderRepo->find($orderId)['order_no'] ?? '',
            'user_id'        => $userId,
            'coupon_user_id' => (int)($data['coupon_user_id'] ?? 0),
        ]);

        if (is_array($zeroPay)) {
            $this->orderPayService->dispatchZeroPaidEvent($zeroPay);
        }

        return $this->orderQueryService->getDetail($orderId);
    }

    /**
     * 试算订单金额（不创建订单），cart/checkout 预下单用
     *
     * @param int   $userId
     * @param array $skus           [{sku_id, quantity}]
     * @param int   $couponUserId   用户优惠券 id（0 表示不使用）
     */
    public function calculate(
        int $userId,
        array $skus,
        int $couponUserId = 0,
        string $deliveryType = 'express',
        ?float $lng = null,
        ?float $lat = null,
        ?string $regionCode = null,
        ?string $province = null
    ): array
    {
        $goodsAmount = '0.00';
        $spuIds      = [];
        $freightItems = [];

        // 一次性批量取 SKU，避免 foreach 内 N 次 find
        $skuIds = array_values(array_unique(array_filter(array_map(
            fn($row) => (int)($row['sku_id'] ?? 0),
            $skus
        ))));
        $skuMap = [];
        foreach ($this->goodsSkuRepository->findByIds($skuIds) as $row) {
            $skuMap[(int)$row['id']] = $row;
        }
        $allSpuIds = array_values(array_unique(array_map(fn($row) => (int)($row['spu_id'] ?? 0), $skuMap)));
        $spuMap = [];
        foreach ($this->goodsSpuRepository->findByIds($allSpuIds) as $row) {
            $spuMap[(int)$row['id']] = $row;
        }

        foreach ($skus as $row) {
            $skuId = (int)($row['sku_id'] ?? 0);
            if ($skuId <= 0) continue;
            $sku = $skuMap[$skuId] ?? null;
            if (!$sku) {
                throw new BusinessException('SKU 不存在: ' . $skuId);
            }
            $qty = max(1, (int)($row['quantity'] ?? 1));

            $quoted = HookManager::apply('order.quote_line', [
                'sku_id'            => $skuId,
                'quantity'          => $qty,
                'flash_item_id'     => (int) ($row['flash_item_id'] ?? 0),
                'group_activity_id' => (int) ($row['group_activity_id'] ?? 0),
                'group_id'          => (int) ($row['group_id'] ?? 0),
                'sku'               => $sku,
            ], [
                'unit_price' => (string) $sku['price'],
            ]);
            $unitPrice = (string) ($quoted['unit_price'] ?? $sku['price']);

            $lineTotal   = bcmul($unitPrice, (string)$qty, 2);
            $goodsAmount = bcadd($goodsAmount, $lineTotal, 2);
            $spuIds[]    = (int)$sku['spu_id'];
            $freightItems[] = [
                'sku'      => $sku,
                'spu'      => $spuMap[(int)$sku['spu_id']] ?? [],
                'quantity' => $qty,
                'total'    => $lineTotal,
            ];
        }

        // 自提免运费；同城走距离计费；快递按商品关联的运费模板分组计算。
        $freightAmount = '0.00';
        if ($deliveryType === 'merchant') {
            $freightAmount = $this->computeMerchantFreight((float)($lng ?? 0), (float)($lat ?? 0), (float)$goodsAmount);
        } elseif ($deliveryType === 'express') {
            $freightAmount = $this->goodsFreightTemplateService->calculateOrderFreight($freightItems, [
                'region_code' => $regionCode ?? '',
                'province'    => $province ?? '',
            ]);
        }
        $freightAmount = $this->applyFreightBenefits($userId, (float)$goodsAmount, $spuIds, $freightAmount);

        $hookContext = [
            'user_id'        => $userId,
            'goods_amount'   => (float)$goodsAmount,
            'spu_ids'        => array_values(array_unique($spuIds)),
            'coupon_user_id' => $couponUserId,
        ];

        $discountAmount = '0.00';
        if (HookManager::has('order.calc_discount')) {
            $discount       = HookManager::apply('order.calc_discount', $hookContext, 0);
            $discountAmount = (string)min((float)$discount, (float)$goodsAmount);
        }

        $payAmount = bcsub(bcadd($goodsAmount, $freightAmount, 2), $discountAmount, 2);

        return [
            'goods_amount'    => $goodsAmount,
            'freight_amount'  => $freightAmount,
            'discount_amount' => $discountAmount,
            'pay_amount'      => $payAmount,
        ];
    }

    /**
     * 用户取消订单
     */
    public function cancel(int $orderId, int $userId, string $reason = ''): void
    {
        $snapshot = $this->orderOrderRepo->findByIdAndUser($orderId, $userId);
        if (!$snapshot) {
            throw new BusinessException('订单不存在');
        }
        if (($snapshot['status'] ?? '') === OrderOrder::STATUS_CANCELLED) {
            $this->dispatchOrderCancelled($snapshot, (string)($snapshot['cancel_reason'] ?? $reason));
            return;
        }
        $this->assertPendingCancellation($snapshot);
        $this->paymentService->closePendingBusinessOrder(
            (string)$snapshot['order_no'],
            $userId,
            $snapshot['pay_amount'] ?? 0
        );

        if ($this->cancelPendingInTransaction($orderId, $userId, $reason)) {
            $this->dispatchOrderCancelled([
                'id' => $orderId,
                'user_id' => $userId,
            ], $reason);
        }
    }

    /**
     * 管理员取消订单
     */
    public function adminCancel(int $orderId, string $reason = ''): void
    {
        $snapshot = $this->orderOrderRepo->find($orderId);
        if (!$snapshot) {
            throw new BusinessException('订单不存在');
        }
        if (($snapshot['status'] ?? '') === OrderOrder::STATUS_CANCELLED) {
            $this->dispatchOrderCancelled($snapshot, (string)($snapshot['cancel_reason'] ?? $reason));
            return;
        }
        $this->assertPendingCancellation($snapshot);
        $this->paymentService->closePendingBusinessOrder(
            (string)$snapshot['order_no'],
            (int)$snapshot['user_id'],
            $snapshot['pay_amount'] ?? 0
        );

        if ($this->cancelPendingInTransaction($orderId, (int)$snapshot['user_id'], $reason)) {
            $this->dispatchOrderCancelled($snapshot, $reason);
        }
    }

    /**
     * 自动取消单笔超时待付款订单。
     *
     * 候选记录可能被多个定时任务进程同时扫到，因此这里会重新读取
     * 状态，并复用与用户/管理员取消完全相同的渠道关单和锁/CAS 链路。
     * 只有赢得 pending -> cancelled CAS 的进程才会恢复库存并发出事件。
     */
    public function autoCancelExpired(int $orderId, string $deadline): string
    {
        if (!$this->isCanonicalDateTime($deadline)) {
            throw new BusinessException('自动取消截止时间格式非法');
        }

        $snapshot = $this->orderOrderRepo->find($orderId);
        if (!$snapshot) {
            return self::AUTO_CANCEL_SKIPPED;
        }
        if (($snapshot['status'] ?? '') === OrderOrder::STATUS_CANCELLED) {
            $this->dispatchOrderCancelled(
                $snapshot,
                (string)($snapshot['cancel_reason'] ?? '超时未支付自动取消')
            );
            return self::AUTO_CANCEL_REPLAYED;
        }
        if (!$this->isExpiredPendingOrder($snapshot, $deadline)) {
            return self::AUTO_CANCEL_SKIPPED;
        }

        $reason = '超时未支付自动取消';
        try {
            $this->paymentService->closePendingBusinessOrder(
                (string)$snapshot['order_no'],
                (int)$snapshot['user_id'],
                $snapshot['pay_amount'] ?? 0
            );
        } catch (BusinessException $e) {
            // 另一进程可能已在候选扫描后完成取消，此时是合法重放。
            $latest = $this->orderOrderRepo->find($orderId);
            if (($latest['status'] ?? '') === OrderOrder::STATUS_CANCELLED) {
                $this->dispatchOrderCancelled(
                    $latest,
                    (string)($latest['cancel_reason'] ?? $reason)
                );
                return self::AUTO_CANCEL_REPLAYED;
            }
            // 并发支付回调若已将订单推进为已付款，自动取消必须静默跳过。
            if ($latest && ($latest['status'] ?? '') !== OrderOrder::STATUS_PENDING) {
                return self::AUTO_CANCEL_SKIPPED;
            }
            // 关单由另一工作进程持有时，本进程不抢占也不恢复库存；
            // 下一轮游标扫描会重新检查，真正的 provider 失败仍继续抛出。
            if ($this->isPaymentCancellationContended($e)) {
                return self::AUTO_CANCEL_CONTENDED;
            }
            throw $e;
        }

        if ($this->cancelPendingInTransaction(
            $orderId,
            (int)$snapshot['user_id'],
            $reason
        )) {
            $this->dispatchOrderCancelled($snapshot, $reason);
            return self::AUTO_CANCEL_CANCELLED;
        }

        $latest = $this->orderOrderRepo->find($orderId);
        if (($latest['status'] ?? '') === OrderOrder::STATUS_CANCELLED) {
            $this->dispatchOrderCancelled($latest, (string)($latest['cancel_reason'] ?? $reason));
            return self::AUTO_CANCEL_REPLAYED;
        }
        return self::AUTO_CANCEL_SKIPPED;
    }

    /**
     * 取消主事务已提交后可靠重放副作用。失败状态落库，自动取消任务会继续扫描；
     * 用户/管理员重复点击取消也会再次触发同一幂等事件。
     *
     * @param array<string, mixed> $order
     */
    private function dispatchOrderCancelled(array $order, string $reason): void
    {
        $orderId = (int)($order['id'] ?? 0);
        if ($orderId <= 0) {
            throw new BusinessException('取消订单副作用缺少订单 ID');
        }
        try {
            $this->trigger('order.cancelled', [
                'order_id' => $orderId,
                'user_id' => (int)($order['user_id'] ?? 0),
                'reason' => $reason,
                'event_key' => 'order.cancelled:' . $orderId,
            ]);
            $this->orderOrderRepo->markCancelEffects($orderId, 'completed');
        } catch (\Throwable $e) {
            $this->orderOrderRepo->markCancelEffects($orderId, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /** @param array<string, mixed> $order */
    private function assertPendingCancellation(array $order): void
    {
        if (($order['status'] ?? '') !== OrderOrder::STATUS_PENDING) {
            throw new BusinessException('仅待付款订单可以直接取消；已付款订单请走退款流程');
        }
    }

    /** @param array<string, mixed> $order */
    private function isExpiredPendingOrder(array $order, string $deadline): bool
    {
        $autoCancelAt = trim((string)($order['auto_cancel_at'] ?? ''));
        return ($order['status'] ?? '') === OrderOrder::STATUS_PENDING
            && $this->isCanonicalDateTime($autoCancelAt)
            && $autoCancelAt <= $deadline;
    }

    private function isCanonicalDateTime(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        return $date !== false && $date->format('Y-m-d H:i:s') === $value;
    }

    private function isPaymentCancellationContended(BusinessException $exception): bool
    {
        return in_array($exception->getMessage(), [
            '支付订单正在创建，请稍后重试取消',
            '支付订单正在关闭，请稍后重试取消',
            '支付单状态已变化，请重试',
        ], true);
    }

    private function cancelPendingInTransaction(
        int $orderId,
        int $expectedUserId,
        string $reason
    ): bool {
        return $this->runInTransaction(function () use (
            $orderId,
            $expectedUserId,
            $reason
        ): bool {
            $order = $this->orderOrderRepo->findForUpdate($orderId);
            if (!$order || (int)($order['user_id'] ?? 0) !== $expectedUserId) {
                throw new BusinessException('订单不存在');
            }
            if (($order['status'] ?? '') === OrderOrder::STATUS_CANCELLED) {
                return false;
            }
            $this->assertPendingCancellation($order);

            // 支付创建同样使用 order -> payment 锁序。即使初查没有支付单，
            // 这里仍二次加锁检查，阻止并发创建留下可支付的已取消订单。
            $payments = $this->paymentOrderRepository->getBusinessOrderCandidatesForUpdate(
                (string)$order['order_no']
            );
            $this->assertPaymentsClosedForCancellation($payments, $order);

            if ($this->orderOrderRepo->cancelIfPending($orderId, $reason) !== 1) {
                throw new BusinessException('订单状态已变化，请重试');
            }

            $this->restoreOrderStock($orderId);
            $this->returnOrderCoupon($orderId);
            return true;
        });
    }

    /** 优惠券回退保留为可测试边界；实现由插件 hook。 */
    protected function returnOrderCoupon(int $orderId): void
    {
        HookManager::apply('order.return_coupon', ['order_id' => $orderId]);
    }

    /**
     * 断言核心已抽取到 PaymentService::assertBusinessPaymentClosed，这里保留
     * 原方法名转调，行为与抽取前一致。
     *
     * @param array<int, array<string, mixed>> $payments
     * @param array<string, mixed> $order
     */
    private function assertPaymentsClosedForCancellation(array $payments, array $order): void
    {
        $this->paymentService->assertBusinessPaymentClosed(
            $payments,
            (string)$order['order_no'],
            (int)$order['user_id'],
            (string)($order['pay_amount'] ?? 0)
        );
    }

    /**
     * 卖家备注
     */
    public function addSellerRemark(int $orderId, string $remark): void
    {
        $order = $this->orderOrderRepo->find($orderId);
        if (!$order) {
            throw new BusinessException('订单不存在');
        }
        $this->orderOrderRepo->update($orderId, ['seller_remark' => $remark]);
    }

    /**
     * 后台修改收货地址（仅未发货快递/同城订单）。
     *
     * @param array<string, mixed> $address
     */
    public function adminUpdateAddress(int $orderId, array $address): void
    {
        $order = $this->orderOrderRepo->find($orderId);
        if (!$order) {
            throw new BusinessException('订单不存在');
        }
        $status = (string)($order['status'] ?? '');
        if (!in_array($status, [OrderOrder::STATUS_PENDING, OrderOrder::STATUS_PAID], true)) {
            throw new BusinessException('仅待付款或待发货订单可修改地址');
        }
        $deliveryType = (string)($order['delivery_type'] ?? 'express');
        if ($deliveryType === 'pickup') {
            throw new BusinessException('自提订单无需修改收货地址');
        }

        $prev = is_array($order['address_snapshot'] ?? null) ? $order['address_snapshot'] : [];
        $snapshot = array_merge($prev, [
            'name'     => trim((string)($address['name'] ?? '')),
            'phone'    => trim((string)($address['phone'] ?? '')),
            'province' => trim((string)($address['province'] ?? '')),
            'city'     => trim((string)($address['city'] ?? '')),
            'district' => trim((string)($address['district'] ?? '')),
            'detail'   => trim((string)($address['detail'] ?? '')),
        ]);
        if (array_key_exists('lng', $address) && $address['lng'] !== '' && $address['lng'] !== null) {
            $snapshot['lng'] = (float)$address['lng'];
        }
        if (array_key_exists('lat', $address) && $address['lat'] !== '' && $address['lat'] !== null) {
            $snapshot['lat'] = (float)$address['lat'];
        }

        $this->orderOrderRepo->update($orderId, ['address_snapshot' => $snapshot]);

        // 同城配送：同步目的地坐标（地址文本以订单快照为准）
        if ($deliveryType === 'merchant') {
            $delivery = $this->deliveryOrderRepository->findByOrderId($orderId);
            if ($delivery && isset($snapshot['lat'], $snapshot['lng'])) {
                $this->deliveryOrderRepository->update((int)$delivery['id'], [
                    'dest_lat' => $snapshot['lat'],
                    'dest_lng' => $snapshot['lng'],
                ]);
            }
        }
    }

    /**
     * 后台软删除订单（仅已取消 / 已关闭）。
     */
    public function adminDelete(int $orderId): void
    {
        $order = $this->orderOrderRepo->find($orderId);
        if (!$order) {
            throw new BusinessException('订单不存在');
        }
        $status = (string)($order['status'] ?? '');
        if (!in_array($status, [OrderOrder::STATUS_CANCELLED, OrderOrder::STATUS_CLOSED], true)) {
            throw new BusinessException('仅已取消或已关闭的订单可删除');
        }
        if (!$this->orderOrderRepo->delete($orderId)) {
            throw new BusinessException('删除失败');
        }
    }

    /**
     * 自提核销
     *
     * @throws BusinessException
     */
    public function verifyPickup(int $orderId, string $code, int $adminId): bool
    {
        $event = $this->runInTransaction(function () use ($orderId, $code, $adminId): array {
            $order = $this->orderOrderRepo->findForUpdate($orderId);
            if (!$order) {
                throw new BusinessException('订单不存在');
            }

            if (($order['delivery_type'] ?? 'express') !== 'pickup') {
                throw new BusinessException('非自提订单');
            }
            if (!hash_equals((string)($order['pickup_code'] ?? ''), $code)) {
                throw new BusinessException('自提码错误');
            }

            if (($order['pickup_status'] ?? '') === 'verified'
                && ($order['status'] ?? '') === OrderOrder::STATUS_COMPLETED) {
                return [
                    'order_id' => $orderId,
                    'user_id' => (int)$order['user_id'],
                    'admin_id' => $adminId,
                    'replayed' => true,
                ];
            }
            if (($order['pickup_status'] ?? '') !== 'pending') {
                $msg = match ($order['pickup_status'] ?? '') {
                    'verified'  => '订单已核销',
                    'timeout'   => '订单已超时，请联系店主',
                    'cancelled' => '订单已取消',
                    default     => '订单状态异常',
                };
                throw new BusinessException($msg);
            }
            if (($order['status'] ?? '') !== OrderOrder::STATUS_PAID) {
                throw new BusinessException('仅已支付订单可以核销');
            }

            $items = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
            $hasFulfillableItem = false;
            foreach ($items as $item) {
                $refundStatus = (int)($item['refund_status'] ?? 0);
                if ($refundStatus === 1) {
                    throw new BusinessException('订单存在处理中的售后申请，暂不能核销');
                }
                $hasFulfillableItem = $hasFulfillableItem || $refundStatus === 0;
            }
            if (!$hasFulfillableItem) {
                throw new BusinessException('订单没有可核销商品');
            }

            if ($this->orderOrderRepo->completePickupIfPaid($orderId, $adminId) !== 1) {
                throw new BusinessException('订单状态已变化，请重试');
            }
            return [
                'order_id' => $orderId,
                'user_id' => (int)$order['user_id'],
                'admin_id' => $adminId,
                'replayed' => false,
            ];
        });

        $this->trigger('order.pickup.verified', [
            'order_id' => $event['order_id'],
            'admin_id' => $event['admin_id'],
            'replayed' => $event['replayed'],
        ]);
        $this->trigger('order.completed', [
            'order_id' => $event['order_id'],
            'user_id' => $event['user_id'],
            'replayed' => $event['replayed'],
        ]);

        return true;
    }

    /**
     * 恢复订单商品库存及 SPU 销量
     */
    private function restoreOrderStock(int $orderId): void
    {
        $items = $this->orderItemRepository->findByOrderId($orderId);
        foreach ($items as $item) {
            $this->goodsSkuRepository->restoreStock((int)$item['sku_id'], (int)$item['quantity']);
            $this->goodsSpuRepository->decSalesCount((int)$item['spu_id'], (int)$item['quantity']);
            HookManager::apply('order.restore_stock_item', [
                'flash_item_id' => (int) ($item['flash_item_id'] ?? 0),
                'sku_id'        => (int) ($item['sku_id'] ?? 0),
                'quantity'      => (int) ($item['quantity'] ?? 0),
            ]);
        }
    }

    /**
     * 同城配送运费计算 + 范围校验
     *
     * 规则（配置位于 system_configs.local_delivery 分组）：
     *   - local_delivery_radius (km)：超出抛 BusinessException
     *   - local_delivery_fee (元)：起步费
     *   - local_delivery_per_km_fee (元)：每公里费率
     *   - local_delivery_free_amount (元)：满 X 包邮（>0 时生效）
     *
     * 距离 = 用户地址坐标 → 最近一家启用门店的 haversine 距离。
     * 用户地址 lng/lat 缺失（老地址）时抛错提示去补全。
     */
    private function computeMerchantFreight(float $userLng, float $userLat, float $orderAmount): string
    {
        if ($userLng <= 0 || $userLat <= 0) {
            throw new BusinessException('地址缺少经纬度，请重新选择地图位置后再下单');
        }

        $stores = $this->storeRepository->getActiveWithCoordinates();
        if (empty($stores)) {
            throw new BusinessException('暂无门店可同城配送，请改选其他配送方式');
        }

        $minDist = PHP_FLOAT_MAX;
        foreach ($stores as $s) {
            $d = $this->haversineKm($userLat, $userLng, (float)$s['lat'], (float)$s['lng']);
            if ($d < $minDist) $minDist = $d;
        }

        $radius = (float)$this->systemConfigRepository->getConfigValue('local_delivery_radius', 5);
        if ($minDist > $radius) {
            throw new BusinessException(sprintf('超出同城配送范围（实测 %.1fkm > %dkm），请改选快递或自提', $minDist, (int)$radius));
        }

        $freeAmount = (float)$this->systemConfigRepository->getConfigValue('local_delivery_free_amount', 0);
        if ($freeAmount > 0 && $orderAmount >= $freeAmount) {
            return '0.00';
        }

        $baseFee  = (float)$this->systemConfigRepository->getConfigValue('local_delivery_fee', 0);
        $perKmFee = (float)$this->systemConfigRepository->getConfigValue('local_delivery_per_km_fee', 0);
        // ceil 公里数：不到 1km 也按 1km 计费
        $fee = $baseFee + ceil($minDist) * $perKmFee;
        return number_format($fee, 2, '.', '');
    }

    /** 满减包邮与会员等级包邮优先于模板/同城计算结果。 */
    private function applyFreightBenefits(int $userId, float $goodsAmount, array $spuIds, string $freight): string
    {
        if ((float)$freight <= 0) {
            return '0.00';
        }
        $freight = (string) HookManager::apply('order.freight_benefit', [
            'user_id'      => $userId,
            'goods_amount' => $goodsAmount,
            'spu_ids'      => $spuIds,
        ], $freight);
        if ((float) $freight <= 0) {
            return '0.00';
        }
        $profile = $this->userRepository->findProfile($userId);
        if ((int)($profile['member_level']['free_freight'] ?? 0) === 1) {
            return '0.00';
        }
        return number_format((float)$freight, 2, '.', '');
    }

    /**
     * 虚拟商品订单自动发卡密 + 完成订单（在事务内执行）
     *
     * 由 OrderPaidListener 在 order.paid 事件中调用。CLAUDE.md 要求事务管理留在
     * Service 层，因此该方法持有事务，Listener 只需传 orderId。
     *
     * @param int $orderId
     * @return bool 自动完成是否成功（无明细返回 false）
     */
    public function autoCompleteVirtualOrder(int $orderId): bool
    {
        // 先持久化 pending，再进入实际发卡事务。进程若在两事务之间退出，支付
        // 补偿任务仍能看到该订单并重放，不会形成“已支付但永不履约”的静默单。
        $ready = $this->runInTransaction(function () use ($orderId): bool {
            $order = $this->orderOrderRepo->findForUpdate($orderId);
            if (!$order) {
                return false;
            }
            if (($order['status'] ?? '') === OrderOrder::STATUS_COMPLETED) {
                return true;
            }
            if (($order['status'] ?? '') !== OrderOrder::STATUS_PAID) {
                return false;
            }
            $this->orderOrderRepo->markVirtualFulfillmentStatus($orderId, 'pending');
            return true;
        });
        if (!$ready) {
            return false;
        }

        try {
            $result = $this->runInTransaction(function () use ($orderId): array {
                $order = $this->orderOrderRepo->findForUpdate($orderId);
                if (!$order) {
                    return ['transitioned' => false, 'trigger' => false, 'user_id' => 0];
                }
                if (($order['status'] ?? '') === OrderOrder::STATUS_COMPLETED) {
                    return [
                        'transitioned' => false,
                        'trigger' => true,
                        'user_id' => (int)$order['user_id'],
                    ];
                }
                if (($order['status'] ?? '') !== OrderOrder::STATUS_PAID) {
                    return ['transitioned' => false, 'trigger' => false, 'user_id' => 0];
                }

                $items = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
                if (empty($items)) {
                    throw new BusinessException('虚拟订单缺少商品明细');
                }

                $hasFulfillableItem = false;
                foreach ($items as $item) {
                    $refundStatus = (int)($item['refund_status'] ?? 0);
                    if ($refundStatus === 1) {
                        throw new BusinessException('订单存在处理中的售后申请，暂不能完成');
                    }
                    if ($refundStatus === 2) {
                        continue;
                    }
                    $hasFulfillableItem = true;
                    for ($i = 0; $i < (int)$item['quantity']; $i++) {
                        $claimedId = $this->goodsVirtualItemRepository->claimUnused(
                            (int)$item['sku_id'],
                            (int)$item['id'],
                        );
                        if ($claimedId === null) {
                            throw new BusinessException(sprintf(
                                '虚拟商品卡密不足（SKU:%d，订单项:%d）',
                                (int)$item['sku_id'],
                                (int)$item['id']
                            ));
                        }
                    }
                }

                if (!$hasFulfillableItem) {
                    return ['transitioned' => false, 'trigger' => false, 'user_id' => 0];
                }

                if ($this->orderOrderRepo->markVirtualCompletedIfPaid($orderId) !== 1) {
                    throw new BusinessException('订单状态已变化，请重试');
                }

                return [
                    'transitioned' => true,
                    'trigger' => true,
                    'user_id' => (int)$order['user_id'],
                ];
            });
        } catch (\Throwable $e) {
            $this->runInTransaction(function () use ($orderId, $e): void {
                $order = $this->orderOrderRepo->findForUpdate($orderId);
                if ($order && ($order['status'] ?? '') === OrderOrder::STATUS_PAID) {
                    $this->orderOrderRepo->markVirtualFulfillmentStatus(
                        $orderId,
                        'failed',
                        $e->getMessage()
                    );
                }
            });
            throw $e;
        }

        if ($result['trigger']) {
            $this->trigger('order.completed', [
                'order_id' => $orderId,
                'user_id' => $result['user_id'],
                'replayed' => !$result['transitioned'],
            ]);
        }
        return $result['transitioned'];
    }

    /**
     * 订单状态机：判断从 $from 是否能流转到 $to（替代 Model.canTransitionTo）
     *
     * 表与 OrderOrder::$transitions 保持一致；改在 Service 层便于配合
     * Repository 返数组的契约。
     */
    private function canTransitionTo(string $from, string $to): bool
    {
        static $transitions = [
            'pending'   => ['paid', 'cancelled'],
            'paid'      => ['shipped'],
            'shipped'   => ['completed'],
            'completed' => ['closed'],
            'cancelled' => [],
            'closed'    => [],
        ];
        return in_array($to, $transitions[$from] ?? [], true);
    }

    private function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new BusinessException('订单金额格式非法');
        }
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        return ((int)$integer * 100) + (int)str_pad($decimal, 2, '0');
    }

    /**
     * 球面距离（haversine 公式），单位 km
     */
    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $latRad1     = deg2rad($lat1);
        $latRad2     = deg2rad($lat2);
        $dLat        = deg2rad($lat2 - $lat1);
        $dLng        = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos($latRad1) * cos($latRad2) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
