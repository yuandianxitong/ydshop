<?php
declare(strict_types=1);

namespace app\service\order\concerns;

use app\repository\order\OrderInvoiceRepository;
use app\repository\order\OrderItemRepository;
use app\repository\order\OrderRefundRepository;
use core\exception\BusinessException;
use core\plugin\HookManager;

/**
 * 订单拆分 / 合并 / 改价共享守护。
 *
 * 使用方是继承 core\base\Service 的订单调整 Service，
 * 声明的 protected 类型属性由基类 DI 自动注入。
 */
trait OrderAdjustGuardTrait
{
    protected OrderInvoiceRepository $orderInvoiceRepository;
    protected OrderItemRepository $orderItemRepository;
    protected OrderRefundRepository $orderRefundRepository;

    /** 已开票订单不允许拆分/合并/改价（票面金额与订单金额会失配）。 */
    protected function assertNoIssuedInvoice(int $orderId): void
    {
        $invoice = $this->orderInvoiceRepository->findByOrderId($orderId);
        if ($invoice && ($invoice['status'] ?? '') === 'issued') {
            throw new BusinessException('订单已开具发票，不能执行该操作');
        }
    }

    /** 存在活跃售后（申请中/退款中等）的订单禁止结构性调整。 */
    protected function assertNoActiveRefund(int $orderId): void
    {
        if ($this->orderRefundRepository->hasActiveByOrderId($orderId)) {
            throw new BusinessException('订单存在处理中的售后申请，不能执行该操作');
        }
    }

    /**
     * 秒杀 / 拼团订单的金额与库存由营销插件闭环管理，禁止拆合改价。
     *
     * @param array<int, array<string, mixed>> $items 订单商品行
     */
    protected function assertNotFlashOrGroupOrder(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            if ((int)($item['flash_item_id'] ?? 0) > 0) {
                throw new BusinessException('秒杀订单不支持该操作');
            }
        }
        if ($this->orderHasPluginConstraint($orderId)) {
            throw new BusinessException('营销活动订单不支持该操作');
        }
    }

    /** 虚拟商品订单支付后立即自动履约，不存在可拆分的物流单元。 */
    protected function assertNotVirtualOrder(int $orderId): void
    {
        if ($this->orderItemRepository->hasVirtualItem($orderId)) {
            throw new BusinessException('虚拟商品订单不支持该操作');
        }
    }

    /** 保留为可测试边界；拼团等插件通过 hook 声明约束。 */
    protected function orderHasPluginConstraint(int $orderId): bool
    {
        return (bool) HookManager::apply('order.has_plugin_constraint', [
            'order_id' => $orderId,
        ], false);
    }

    /**
     * 金额转分（与 OrderService::moneyToCents 同规则：仅接受非负、最多两位小数）。
     */
    protected function moneyToCents(string|int|float $amount): int
    {
        $normalized = trim((string)$amount);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new BusinessException('订单金额格式非法');
        }
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '');
        return ((int)$integer * 100) + (int)str_pad($decimal, 2, '0');
    }

    /** 分转金额字符串（两位小数，落库/快照用）。 */
    protected function centsToMoney(int $cents): string
    {
        if ($cents < 0) {
            throw new BusinessException('金额不能为负数');
        }
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
