<?php
declare(strict_types=1);

namespace app\listener\order;

use app\repository\delivery\DeliveryOrderRepository;
use app\repository\goods\GoodsSpuRepository;
use app\repository\order\OrderItemRepository;
use app\repository\order\OrderOrderRepository;
use app\service\order\OrderService;
use core\exception\BusinessException;
use think\facade\Log;

class OrderPaidListener
{
    public function __construct(
        protected OrderService $orderService,
        protected OrderOrderRepository $orderOrderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected GoodsSpuRepository $goodsSpuRepository,
        protected DeliveryOrderRepository $deliveryOrderRepository,
    ) {}

    public function handle(array $event): void
    {
        $orderId = (int)($event['order_id'] ?? 0);
        if (!$orderId) {
            throw new BusinessException('订单支付事件缺少订单 ID');
        }

        try {
            $order = $this->orderOrderRepository->find($orderId);
            if (!$order) {
                throw new BusinessException('已支付业务订单不存在');
            }

            // 商家同城配送：付款后自动创建 delivery_order，admin 可派单
            // 幂等：仅当不存在时创建（重复支付/查询触发的二次 order.paid 不会重复建单）
            if (($order['delivery_type'] ?? 'express') === 'merchant') {
                $this->ensureMerchantDeliveryOrder($orderId, $order['address_snapshot'] ?? []);
            }

            $items = $this->orderItemRepository->findByOrderId($orderId);
            if (empty($items)) {
                throw new BusinessException('已支付业务订单缺少商品明细');
            }

            // 全部为虚拟商品时，委托 Service 在事务内完成自动发卡密 + 关单
            // 任一明细非 virtual 即跳过（spec 行为）
            // 批量加载 SPU 避免 N+1
            $spuIds  = array_unique(array_column($items, 'spu_id'));
            $spuRows = $this->goodsSpuRepository->getByIds($spuIds, count($spuIds));
            $spuMap  = [];
            foreach ($spuRows as $s) {
                $spuMap[(int)$s['id']] = $s;
            }
            foreach ($items as $item) {
                $spu = $spuMap[(int)$item['spu_id']] ?? null;
                if (!$spu || ($spu['type'] ?? '') !== 'virtual') {
                    return;
                }
            }

            $this->orderService->autoCompleteVirtualOrder($orderId);
        } catch (\Throwable $e) {
            $this->reportError('OrderPaidListener 处理异常：' . $e->getMessage(), ['order_id' => $orderId]);
            throw $e;
        }
    }

    /**
     * 同城配送订单付款后自动建 delivery_order（pending 待派单），从订单地址快照
     * 取目的地经纬度（缺失则置 0，admin 派单时可补）
     *
     * @param array<string, mixed> $addressSnapshot
     */
    private function ensureMerchantDeliveryOrder(int $orderId, $addressSnapshot): void
    {
        // address_snapshot 是 json 字段，Model accessor 返回 array；防御性兼容字符串
        $snap = is_array($addressSnapshot) ? $addressSnapshot : (json_decode((string)$addressSnapshot, true) ?: []);
        $this->deliveryOrderRepository->createOnceForOrder([
            'order_id' => $orderId,
            'platform' => 'merchant',
            'status'   => 'pending',
            'distance' => 0,
            'fee'      => 0,
            'remark'   => '',
            'dest_lat' => isset($snap['lat']) ? (float)$snap['lat'] : 0,
            'dest_lng' => isset($snap['lng']) ? (float)$snap['lng'] : 0,
        ]);
        try {
            Log::info('merchant 订单已建 delivery_order', ['order_id' => $orderId]);
        } catch (\Throwable) {
        }
    }

    /** 记录器故障不能覆盖原始订单结算异常。 */
    protected function reportError(string $message, array $context = []): void
    {
        try {
            Log::error($message, $context);
        } catch (\Throwable) {
        }
    }
}
