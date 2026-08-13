<?php
declare(strict_types=1);

namespace app\listener\order;

use app\repository\delivery\DeliveryOrderRepository;
use think\facade\Log;

/**
 * 拆单完成副作用：merchant 同城配送子单幂等补建 delivery_order。
 *
 * 与 OrderPaidListener::ensureMerchantDeliveryOrder 同构：
 * createOnceForOrder 幂等（重复事件不会重复建单），全程吞错落日志，
 * 失败不影响拆单主流程。
 */
class OrderSplitListener
{
    public function __construct(
        protected DeliveryOrderRepository $deliveryOrderRepository,
    ) {}

    public function handle(array $event): void
    {
        $childOrderId = (int)($event['child_order_id'] ?? 0);
        try {
            if ($childOrderId <= 0) {
                return;
            }
            if (($event['delivery_type'] ?? 'express') !== 'merchant') {
                return;
            }

            // address_snapshot 是 json 字段，Model accessor 返回 array；防御性兼容字符串
            $snapshot = $event['address_snapshot'] ?? [];
            $snap = is_array($snapshot) ? $snapshot : (json_decode((string)$snapshot, true) ?: []);
            $this->deliveryOrderRepository->createOnceForOrder([
                'order_id' => $childOrderId,
                'platform' => 'merchant',
                'status'   => 'pending',
                'distance' => 0,
                'fee'      => 0,
                'remark'   => '',
                'dest_lat' => isset($snap['lat']) ? (float)$snap['lat'] : 0,
                'dest_lng' => isset($snap['lng']) ? (float)$snap['lng'] : 0,
            ]);
            try {
                Log::info('merchant 拆单子单已建 delivery_order', ['order_id' => $childOrderId]);
            } catch (\Throwable) {
            }
        } catch (\Throwable $e) {
            try {
                Log::error('OrderSplitListener 处理异常：' . $e->getMessage(), [
                    'child_order_id' => $childOrderId,
                ]);
            } catch (\Throwable) {
            }
        }
    }
}
