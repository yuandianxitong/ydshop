<?php
declare(strict_types=1);

namespace app\listener\delivery;

use app\service\delivery\DeliveryOrderService;
use think\facade\Log;

/**
 * 支付成功后自动向三方同城配送平台发单
 *
 * 副作用监听器：发单失败不影响支付主流程，全部吞异常并 Log::warning。
 * 事件注册由 event.php 统一维护（本类只提供 handle）。
 */
class DeliveryAutoDispatchListener
{
    public function __construct(protected DeliveryOrderService $deliveryOrderService)
    {
    }

    public function handle(array $event): void
    {
        $orderId = (int)($event['order_id'] ?? 0);
        if ($orderId <= 0) {
            return;
        }
        try {
            $this->deliveryOrderService->autoDispatchIfEnabled($orderId);
        } catch (\Throwable $e) {
            try {
                Log::warning('同城配送自动发单失败：' . $e->getMessage(), ['order_id' => $orderId]);
            } catch (\Throwable) {
                // 日志器故障也不能影响支付主流程
            }
        }
    }
}
