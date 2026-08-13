<?php
declare(strict_types=1);

namespace app\service\payment;

use app\repository\payment\PaymentOrderRepository;
use app\repository\system\SystemConfigRepository;
use core\base\Service;

/** 本地 paid 支付单的只读扫描与幂等消费者重放。 */
class PaymentResyncService extends Service
{
    protected PaymentOrderRepository $paymentOrderRepository;
    protected PaymentService $paymentService;
    protected SystemConfigRepository $systemConfigRepository;

    /** @return array<int, array<string, mixed>> */
    public function scan(?string $orderNo = null): array
    {
        return array_map(static fn (array $row): array => [
            'payment_order_id' => (int)$row['id'],
            'order_no'        => (string)$row['order_no'],
            'biz_type'        => (string)($row['biz_type'] ?? ''),
            'channel'         => (string)$row['channel'],
            'amount'          => (string)$row['total_amount'],
            'trade_no'        => (string)($row['trade_no'] ?? ''),
            'paid_at'         => (string)($row['paid_at'] ?? ''),
        ], $this->paymentOrderRepository->getPaidForResync($orderNo));
    }

    /**
     * @param int[] $paymentOrderIds
     * @return array{success:int, failed:array<int, array{payment_order_id:int,error:string}>}
     */
    public function replay(array $paymentOrderIds): array
    {
        $success = 0;
        $failed = [];
        foreach (array_values(array_unique(array_map('intval', $paymentOrderIds))) as $paymentOrderId) {
            if ($paymentOrderId <= 0) {
                continue;
            }
            try {
                $this->paymentService->replayPaidLocal($paymentOrderId);
                $success++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'payment_order_id' => $paymentOrderId,
                    'error' => $e->getMessage(),
                ];
            }
        }
        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * 定时任务入口：无参数、仅本地、严格上抛消费者错误，并受发布边界保护。
     *
     * @return array{from:string,scanned:int,success:int,failed:array<int,array{payment_order_id:int,error:string}>}
     */
    public function reconcileAutomatic(): array
    {
        $from = trim((string)$this->systemConfigRepository->getConfigValue(
            'payment.reconcile_from',
            null
        ));
        $timestamp = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $from) === 1
            ? strtotime($from)
            : false;
        if ($timestamp === false || date('Y-m-d H:i:s', $timestamp) !== $from) {
            throw new \UnexpectedValueException('支付补偿发布边界配置无效');
        }

        // 仅重放核心业务消费者未完成的支付。财务缺口由独立 finance:reconcile
        // 补齐，避免每天对边界后的全部 paid 永久重复广播整条事件链。
        $rows = $this->paymentOrderRepository->getUnsettledPaidForAutomaticReconcile($from);
        $result = $this->replay(array_column($rows, 'id'));
        return [
            'from' => $from,
            'scanned' => count($rows),
            'success' => $result['success'],
            'failed' => $result['failed'],
        ];
    }

    /**
     * 近期支付渠道对账：受发布边界与安全延迟约束，并按每行 next_at 退避。
     * 全局 cursor 仅保留运维可见性，不参与筛选，避免坏行队首阻塞和新增
     * 流量持续大于批量时旧记录永久饥饿。
     */
    public function reconcileProvider(int $limit = 100): array
    {
        $cursor = max(0, (int)$this->systemConfigRepository->getConfigValue('payment.provider_reconcile_cursor', 0));
        $from = (string)$this->systemConfigRepository->getConfigValue('payment.provider_reconcile_from', date('Y-m-d H:i:s', strtotime('-1 day')));
        $delay = max(0, (int)$this->systemConfigRepository->getConfigValue('payment.provider_reconcile_safe_delay_seconds', 300));
        $before = date('Y-m-d H:i:s', time() - $delay);
        $now = date('Y-m-d H:i:s');
        $rows = $this->paymentOrderRepository->getDueProviderReconcileCandidates(
            $limit,
            $from,
            $before,
            $now
        );
        $success = 0; $failed = [];
        foreach ($rows as $row) {
            $paymentOrderId = (int)$row['id'];
            try {
                $this->paymentService->reconcileProviderPayment($row);
                $this->paymentOrderRepository->recordProviderReconcileSuccess(
                    $paymentOrderId,
                    date('Y-m-d H:i:s', time() + 60)
                );
                $success++;
            } catch (\Throwable $e) {
                $failed[] = ['payment_order_id' => $paymentOrderId, 'error' => $e->getMessage()];
                $retryCount = max(0, (int)($row['provider_reconcile_retry_count'] ?? 0));
                $backoff = min(3600, 30 * (2 ** min(7, $retryCount)));
                $this->paymentOrderRepository->recordProviderReconcileFailure(
                    $paymentOrderId,
                    date('Y-m-d H:i:s', time() + $backoff),
                    $e->getMessage()
                );
            }
            $cursor = $paymentOrderId;
        }
        if ($rows !== []) {
            $this->systemConfigRepository->upsertConfigValue('payment.provider_reconcile_cursor', $cursor, [
                'config_group' => 'payment', 'config_type' => 'number', 'config_name' => '支付渠道对账最近处理记录',
                'config_desc' => '仅供运维观察；实际调度使用每行 next_at', 'sort_order' => 90,
            ]);
        }
        return ['from' => $from, 'updated_before' => $before, 'cursor' => $cursor, 'scanned' => count($rows), 'success' => $success, 'failed' => $failed];
    }
}
