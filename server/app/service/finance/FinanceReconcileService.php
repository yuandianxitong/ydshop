<?php
declare(strict_types=1);

namespace app\service\finance;

use app\repository\finance\FinanceReconcileRepository;
use core\base\Service;

/** 按业务事实幂等补齐 finance_transactions。 */
class FinanceReconcileService extends Service
{
    protected FinanceReconcileRepository $financeReconcileRepository;
    protected FinanceService $financeService;

    /**
     * @return array{
     *   apply:bool,
     *   batch_size:int,
     *   scanned:array{payments:int,refunds:int,withdrawals:int},
     *   processed:array{payments:int,refunds:int,withdrawals:int},
     *   would_process:array{payments:int,refunds:int,withdrawals:int},
     *   last_ids:array{payments:int,refunds:int,withdrawals:int},
     *   failed:array<int,array{source:string,id:int,error:string}>
     * }
     */
    public function reconcile(bool $apply = true, int $batchSize = 200): array
    {
        $batchSize = min(1000, max(1, $batchSize));
        $stats = [
            'apply' => $apply,
            'batch_size' => $batchSize,
            'scanned' => ['payments' => 0, 'refunds' => 0, 'withdrawals' => 0],
            'processed' => ['payments' => 0, 'refunds' => 0, 'withdrawals' => 0],
            'would_process' => ['payments' => 0, 'refunds' => 0, 'withdrawals' => 0],
            'last_ids' => ['payments' => 0, 'refunds' => 0, 'withdrawals' => 0],
            'failed' => [],
        ];

        $this->scanSource(
            'payments',
            $batchSize,
            fn (int $afterId, int $limit): array =>
                $this->financeReconcileRepository->getPaidPaymentsAfterId($afterId, $limit),
            fn (array $row): ?array => $this->financeService->recordPaymentSuccess(
                $this->buildPaymentEvent($row)
            ),
            $apply,
            $stats
        );
        $this->scanSource(
            'refunds',
            $batchSize,
            fn (int $afterId, int $limit): array =>
                $this->financeReconcileRepository->getRefundedAfterId($afterId, $limit),
            fn (array $row): ?array => $this->financeService->recordRefundCompleted(
                $this->buildRefundEvent($row)
            ),
            $apply,
            $stats
        );
        $this->scanSource(
            'withdrawals',
            $batchSize,
            fn (int $afterId, int $limit): array =>
                $this->financeReconcileRepository->getPaidWithdrawalsAfterId($afterId, $limit),
            fn (array $row): ?array => $this->financeService->recordWithdrawalPaid(
                $this->buildWithdrawalEvent($row)
            ),
            $apply,
            $stats
        );

        return $stats;
    }

    /**
     * @param callable(int,int):array<int,array<string,mixed>> $fetch
     * @param callable(array<string,mixed>):?array<string,mixed> $write
     * @param array<string,mixed> $stats
     */
    private function scanSource(
        string $source,
        int $batchSize,
        callable $fetch,
        callable $write,
        bool $apply,
        array &$stats
    ): void {
        $cursor = 0;

        do {
            $rows = $fetch($cursor, $batchSize);
            if ($rows === []) {
                break;
            }

            $cursorBeforeBatch = $cursor;
            foreach ($rows as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id <= $cursor) {
                    $stats['failed'][] = [
                        'source' => $source,
                        'id' => $id,
                        'error' => '补偿数据源未按递增主键游标返回',
                    ];
                    continue;
                }

                $cursor = $id;
                $stats['last_ids'][$source] = $id;
                $stats['scanned'][$source]++;

                try {
                    // dry-run 也构造并验证可信事件，但不写财务流水。
                    if (!$apply) {
                        $this->validateCandidate($source, $row);
                        $stats['would_process'][$source]++;
                        continue;
                    }

                    $result = $write($row);
                    if ($result === null) {
                        throw new \UnexpectedValueException('业务事实无法生成财务流水');
                    }
                    $stats['processed'][$source]++;
                } catch (\Throwable $e) {
                    $stats['failed'][] = [
                        'source' => $source,
                        'id' => $id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // 防御错误 Repository/测试桩造成死循环。
            if ($cursor <= $cursorBeforeBatch) {
                break;
            }
        } while (count($rows) === $batchSize);
    }

    /** @param array<string,mixed> $row */
    private function validateCandidate(string $source, array $row): void
    {
        match ($source) {
            'payments' => $this->buildPaymentEvent($row),
            'refunds' => $this->buildRefundEvent($row),
            'withdrawals' => $this->buildWithdrawalEvent($row),
            default => throw new \UnexpectedValueException('未知财务补偿来源'),
        };
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function buildPaymentEvent(array $row): array
    {
        $bizType = trim((string)($row['resolved_biz_type'] ?? $row['biz_type'] ?? ''));
        $bizId = (int)($row['resolved_biz_id'] ?? 0);
        $orderNo = trim((string)($row['order_no'] ?? ''));
        $amount = (float)($row['total_amount'] ?? 0);
        $occurredAt = $this->requireOccurredAt($row['paid_at'] ?? null, '已支付记录');
        if (!in_array($bizType, ['order', 'recharge'], true)
            || $bizId <= 0
            || $orderNo === ''
            || $amount <= 0) {
            throw new \UnexpectedValueException('已支付记录缺少可信业务归属或金额');
        }

        $event = [
            'payment_order_id' => (int)$row['id'],
            'biz_id' => $bizId,
            'biz_type' => $bizType,
            'order_no' => $orderNo,
            'amount' => $amount,
            'channel' => (string)($row['channel'] ?? ''),
            'trade_no' => (string)($row['trade_no'] ?? ''),
            'user_id' => (int)($row['resolved_user_id'] ?? $row['user_id'] ?? 0),
            'occurred_at' => $occurredAt,
        ];
        $event[$bizType === 'order' ? 'order_id' : 'recharge_order_id'] = $bizId;
        return $event;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function buildRefundEvent(array $row): array
    {
        $refundId = (int)($row['id'] ?? 0);
        $refundNo = trim((string)($row['refund_no'] ?? ''));
        $amount = (float)($row['refund_amount'] ?? 0);
        $occurredAt = $this->requireOccurredAt($row['refunded_at'] ?? null, '已退款记录');
        if ($refundId <= 0 || $refundNo === '' || $amount <= 0) {
            throw new \UnexpectedValueException('已退款记录缺少退款单号或金额');
        }

        $refundTradeNo = trim((string)($row['refund_trade_no'] ?? ''));
        $referenceSource = trim((string)($row['refund_trade_no_source'] ?? ''));
        if ($refundTradeNo === '') {
            // 历史记录没有落 provider refund_id 时，只能使用真实的商户退款单号；
            // 不允许退回到 payment_trade_no（那是原支付交易号）。
            $refundTradeNo = $refundNo;
            $referenceSource = 'legacy_merchant_refund_no';
        }

        return [
            'refund_id' => $refundId,
            'refund_no' => $refundNo,
            'order_id' => (int)($row['order_id'] ?? 0),
            'order_no' => (string)($row['order_no'] ?? ''),
            'order_item_id' => (int)($row['order_item_id'] ?? 0),
            'user_id' => (int)($row['user_id'] ?? 0),
            'type' => (string)($row['type'] ?? ''),
            'refund_amount' => $amount,
            'payment_channel' => (string)($row['payment_channel'] ?? ''),
            'payment_trade_no' => (string)($row['payment_trade_no'] ?? ''),
            'refund_trade_no' => $refundTradeNo,
            'refund_trade_no_source' => $referenceSource,
            'occurred_at' => $occurredAt,
        ];
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function buildWithdrawalEvent(array $row): array
    {
        $id = (int)($row['id'] ?? 0);
        $actualAmount = (float)($row['actual_amount'] ?? 0);
        $occurredAt = $this->requireOccurredAt($row['paid_at'] ?? null, '已打款提现记录');
        if ($id <= 0 || $actualAmount <= 0) {
            throw new \UnexpectedValueException('已打款提现记录缺少实际到账金额');
        }

        return [
            'withdrawal_id' => $id,
            'withdrawal_no' => 'WD' . $id,
            'user_id' => (int)($row['user_id'] ?? 0),
            'amount' => (float)($row['amount'] ?? 0),
            'gross_amount' => (float)($row['amount'] ?? 0),
            'actual_amount' => $actualAmount,
            'fee' => (float)($row['fee'] ?? 0),
            'type' => (string)($row['type'] ?? ''),
            'occurred_at' => $occurredAt,
        ];
    }

    private function requireOccurredAt(mixed $value, string $label): string
    {
        $occurredAt = trim((string)$value);
        $timestamp = $occurredAt !== '' ? strtotime($occurredAt) : false;
        if ($timestamp === false || $timestamp > time() + 300) {
            // 不允许缺失的历史记录以 NOW 入账污染今日/月度报表。
            throw new \UnexpectedValueException($label . '缺少可信业务发生时间，需人工核对');
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
}
