<?php
declare(strict_types=1);

namespace app\service\order;

use app\model\order\OrderOrder;
use app\repository\order\OrderLogisticsRepository;
use app\repository\order\OrderItemRepository;
use app\repository\order\OrderOrderRepository;
use app\service\delivery\WaybillService;
use core\base\Service;
use core\exception\BusinessException;
use think\facade\Log;

class OrderShipService extends Service
{
    protected WaybillService $waybillService;
    protected OrderOrderRepository $orderOrderRepository;
    protected OrderItemRepository $orderItemRepository;
    protected OrderLogisticsRepository $orderLogisticsRepository;

    /**
     * 发货。
     *
     * @param int   $orderId 订单ID
     * @param array{
     *   delivery_mode?: string,
     *   ship_mode?: string,
     *   express_company?: string,
     *   express_no?: string,
     *   waybill_template_id?: int|null
     * } $params
     * @return array{express_company:string,express_no:string,print_template_html:?string,delivery_mode:string,ship_mode:string}
     */
    public function ship(int $orderId, array $params = []): array
    {
        $deliveryMode = (string)($params['delivery_mode'] ?? 'express');
        if (!in_array($deliveryMode, ['express', 'none'], true)) {
            throw new BusinessException('配送方式无效');
        }

        $shipMode = (string)($params['ship_mode'] ?? 'manual');
        if ($deliveryMode === 'none') {
            $shipMode = 'manual';
        }
        if (!in_array($shipMode, ['manual', 'waybill'], true)) {
            throw new BusinessException('发货方式无效');
        }

        $expressCompany = trim((string)($params['express_company'] ?? ''));
        $expressNo = trim((string)($params['express_no'] ?? ''));
        $templateId = (int)($params['waybill_template_id'] ?? 0);
        $printHtml = null;

        if ($deliveryMode === 'express' && $shipMode === 'manual') {
            if ($expressCompany === '' || $expressNo === '') {
                throw new BusinessException('请填写快递公司与快递单号');
            }
        }

        if ($deliveryMode === 'express' && $shipMode === 'waybill') {
            if ($templateId <= 0) {
                throw new BusinessException('请选择面单模版');
            }
            // 生成面单前先做可发货预检，避免面单成功后发货事务失败
            $this->assertCanShip($orderId);
            // 面单必须在标已发货前成功，失败则阻断
            $waybillResult = $this->waybillService->generate($orderId, '', $templateId);
            if (!$waybillResult->success) {
                throw new BusinessException('电子面单生成失败：' . ($waybillResult->errorMessage ?: '未知原因'));
            }
            $expressNo = (string)$waybillResult->waybillNo;
            $printHtml = $waybillResult->printTemplateHtml;
            $logistics = $this->orderLogisticsRepository->findByOrderId($orderId);
            if (is_array($logistics) && !empty($logistics['express_company'])) {
                $expressCompany = (string)$logistics['express_company'];
            }
            if ($expressCompany === '') {
                $expressCompany = '电子面单';
            }
        }

        if ($deliveryMode === 'none') {
            $expressCompany = '';
            $expressNo = '';
        }

        $this->runInTransaction(function () use ($orderId, $expressCompany, $expressNo, $shipMode) {
            $this->assertCanShip($orderId, true);

            $this->orderOrderRepository->update($orderId, [
                'status'    => OrderOrder::STATUS_SHIPPED,
                'ship_time' => date('Y-m-d H:i:s'),
            ]);

            $existing = $this->orderLogisticsRepository->findByOrderId($orderId);
            if ($existing) {
                $this->orderLogisticsRepository->updateByOrderId($orderId, [
                    'express_company' => $expressCompany,
                    'express_no'      => $expressNo,
                    'status'          => 'shipping',
                ]);
            } else {
                $this->orderLogisticsRepository->create([
                    'order_id'        => $orderId,
                    'express_company' => $expressCompany,
                    'express_no'      => $expressNo,
                    'status'          => 'shipping',
                ]);
            }

            if ($shipMode === 'waybill' && $expressNo !== '') {
                $this->orderLogisticsRepository->upsertWaybillByOrderId($orderId, [
                    'waybill_no'      => $expressNo,
                    'express_no'      => $expressNo,
                    'express_company' => $expressCompany,
                ]);
            }
        });

        $this->trigger('order.shipped', [
            'order_id'        => $orderId,
            'express_company' => $expressCompany,
            'express_no'      => $expressNo,
            'delivery_mode'   => $deliveryMode,
            'ship_mode'       => $shipMode,
        ]);

        return [
            'express_company'     => $expressCompany,
            'express_no'          => $expressNo,
            'print_template_html' => $printHtml,
            'delivery_mode'       => $deliveryMode,
            'ship_mode'           => $shipMode,
        ];
    }

    /**
     * 校验订单是否可发货。
     *
     * @param bool $forUpdate 是否在事务内加行锁读取
     */
    private function assertCanShip(int $orderId, bool $forUpdate = false): void
    {
        $order = $forUpdate
            ? $this->orderOrderRepository->findForUpdate($orderId)
            : $this->orderOrderRepository->find($orderId);
        if (!$order) {
            throw new BusinessException('订单不存在');
        }
        if (($order['status'] ?? '') !== OrderOrder::STATUS_PAID) {
            throw new BusinessException('当前订单状态不支持发货');
        }

        $items = $forUpdate
            ? $this->orderItemRepository->findByOrderIdForUpdate($orderId)
            : $this->orderItemRepository->findByOrderId($orderId);
        if ($items === []) {
            throw new BusinessException('订单没有可发货商品');
        }
        foreach ($items as $item) {
            if ((int)($item['refund_status'] ?? 0) === 1) {
                throw new BusinessException('订单存在处理中的售后申请，暂不能发货');
            }
        }
        $fulfillableItems = array_filter(
            $items,
            static fn (array $item): bool => (int)($item['refund_status'] ?? 0) === 0
        );
        if ($fulfillableItems === []) {
            throw new BusinessException('订单没有可发货商品');
        }
    }

    /**
     * 确认收货
     *
     * @param int $orderId 订单ID
     * @param int $userId  用户ID
     */
    public function confirmReceive(int $orderId, int $userId): void
    {
        $event = $this->runInTransaction(function () use ($orderId, $userId): array {
            $order = $this->orderOrderRepository->findForUpdate($orderId);
            if (!$order || (int)($order['user_id'] ?? 0) !== $userId) {
                throw new BusinessException('订单不存在');
            }

            $status = (string)($order['status'] ?? '');
            if ($status === OrderOrder::STATUS_COMPLETED) {
                return ['order_id' => $orderId, 'user_id' => $userId, 'replayed' => true];
            }
            if ($status !== OrderOrder::STATUS_SHIPPED) {
                throw new BusinessException('当前订单状态不支持确认收货');
            }

            $items = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
            foreach ($items as $item) {
                if ((int)($item['refund_status'] ?? 0) === 1) {
                    throw new BusinessException('订单存在处理中的售后申请，暂不能确认收货');
                }
            }
            if ($this->orderOrderRepository->markCompletedIfStatus(
                $orderId,
                OrderOrder::STATUS_SHIPPED
            ) !== 1) {
                throw new BusinessException('订单状态已变化，请重试');
            }
            return ['order_id' => $orderId, 'user_id' => $userId, 'replayed' => false];
        });

        $this->trigger('order.completed', $event);
    }

    /**
     * 自动确认单笔订单。处理中售后不会被越过；completed 重放仍补投事件。
     *
     * @return string completed|replayed|refund_processing|skipped
     */
    public function autoConfirmOne(int $orderId): string
    {
        $event = $this->runInTransaction(function () use ($orderId): ?array {
            $order = $this->orderOrderRepository->findForUpdate($orderId);
            if (!$order) {
                return null;
            }
            $status = (string)($order['status'] ?? '');
            if ($status === OrderOrder::STATUS_COMPLETED) {
                return [
                    'order_id' => $orderId,
                    'user_id' => (int)$order['user_id'],
                    'replayed' => true,
                    'result' => 'replayed',
                ];
            }
            if ($status !== OrderOrder::STATUS_SHIPPED) {
                return null;
            }
            $items = $this->orderItemRepository->findByOrderIdForUpdate($orderId);
            foreach ($items as $item) {
                if ((int)($item['refund_status'] ?? 0) === 1) {
                    return [
                        'order_id' => $orderId,
                        'user_id' => (int)$order['user_id'],
                        'replayed' => false,
                        'result' => 'refund_processing',
                    ];
                }
            }
            if ($this->orderOrderRepository->markCompletedIfStatus(
                $orderId,
                OrderOrder::STATUS_SHIPPED
            ) !== 1) {
                return null;
            }
            return [
                'order_id' => $orderId,
                'user_id' => (int)$order['user_id'],
                'replayed' => false,
                'result' => 'completed',
            ];
        });

        if ($event === null) {
            return 'skipped';
        }
        $result = (string)$event['result'];
        unset($event['result']);
        if ($result !== 'refund_processing') {
            $this->trigger('order.completed', $event);
        }
        return $result;
    }

    /** @return array{completed:int,replayed:int,refund_processing:int,skipped:int,failed:int} */
    public function autoConfirmExpired(string $deadline, int $batchSize = 200): array
    {
        $stats = [
            'completed' => 0,
            'replayed' => 0,
            'refund_processing' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
        $afterId = 0;
        do {
            $orders = $this->orderOrderRepository->getShippedBeforeAfterId(
                $deadline,
                $afterId,
                $batchSize
            );
            foreach ($orders as $order) {
                $afterId = max($afterId, (int)$order['id']);
                try {
                    $result = $this->autoConfirmOne((int)$order['id']);
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    Log::error('自动确认收货失败', [
                        'order_id' => (int)$order['id'],
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        } while (count($orders) === $batchSize);
        return $stats;
    }

    /**
     * 更新物流信息
     *
     * @param int    $orderId        订单ID
     * @param string $expressCompany 快递公司
     * @param string $expressNo      快递单号
     */
    public function updateLogistics(int $orderId, string $expressCompany, string $expressNo): void
    {
        $logistics = $this->orderLogisticsRepository->findByOrderId($orderId);
        if (!$logistics) {
            throw new BusinessException('物流记录不存在');
        }

        $this->orderLogisticsRepository->updateByOrderId($orderId, [
            'express_company' => $expressCompany,
            'express_no'      => $expressNo,
        ]);
    }
}
