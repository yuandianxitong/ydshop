<?php
declare(strict_types=1);

namespace app\listener\order;

use app\repository\order\OrderInvoiceRepository;

class OrderCancelledInvoiceListener
{
    protected OrderInvoiceRepository $orderInvoiceRepository;

    public function __construct(OrderInvoiceRepository $repo)
    {
        $this->orderInvoiceRepository = $repo;
    }

    /**
     * 订单取消时软删发票申请
     *
     * @param array $payload ['order_id' => int, 'user_id' => int, 'reason' => string]
     */
    public function handle(array $payload): void
    {
        $orderId = (int)($payload['order_id'] ?? 0);
        if ($orderId <= 0) {
            return;
        }

        try {
            $invoice = $this->orderInvoiceRepository->findByOrderId($orderId);
            if ($invoice) {
                $this->orderInvoiceRepository->delete((int)$invoice['id']);
            }
        } catch (\Throwable $e) {
            try {
                \think\facade\Log::error(
                    'OrderCancelledInvoiceListener 软删发票失败: ' . $e->getMessage(),
                    ['order_id' => $orderId]
                );
            } catch (\Throwable) {
            }
            // 主订单已取消，错误必须上抛给 Service 持久化 failed 并由任务重放。
            throw $e;
        }
    }
}
