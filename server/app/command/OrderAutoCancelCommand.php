<?php
declare(strict_types=1);

namespace app\command;

use app\repository\order\OrderOrderRepository;
use app\service\order\OrderService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class OrderAutoCancelCommand extends Command
{
    private const DEFAULT_BATCH_SIZE = 200;

    protected function configure(): void
    {
        $this->setName('order:auto-cancel')
            ->setDescription('Auto cancel unpaid orders after their configured deadline');
    }

    protected function execute(Input $input, Output $output): int
    {
        $deadline = $this->currentDateTime();
        $batchSize = $this->batchSize();
        $orderRepository = $this->resolveOrderRepository();
        $orderService = $this->resolveOrderService();
        $afterId = 0;
        $stats = [
            'cancelled' => 0,
            'replayed' => 0,
            'contended' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        do {
            $cursorBeforeBatch = $afterId;
            $orders = $orderRepository->getExpiredPendingAfterId(
                $deadline,
                $afterId,
                $batchSize
            );
            foreach ($orders as $order) {
                $orderId = (int)($order['id'] ?? 0);
                if ($orderId <= $afterId) {
                    $stats['failed']++;
                    $output->writeln(sprintf(
                        'Failed to scan auto-cancel candidate: non-increasing order id %d after %d.',
                        $orderId,
                        $afterId
                    ));
                    continue;
                }
                $afterId = $orderId;

                try {
                    $result = $orderService->autoCancelExpired(
                        $orderId,
                        $deadline
                    );
                    if (array_key_exists($result, $stats)) {
                        $stats[$result]++;
                    } else {
                        $stats['failed']++;
                        $output->writeln(sprintf(
                            'Failed to cancel order %s: unknown result %s.',
                            (string)($order['order_no'] ?? $orderId),
                            $result
                        ));
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $output->writeln(sprintf(
                        'Failed to cancel order %s: %s',
                        (string)($order['order_no'] ?? $orderId),
                        $e->getMessage()
                    ));
                }
            }

            // Repository 按 id 升序保证游标前进；此防线避免异常数据导致死循环。
            if ($orders !== [] && $afterId <= $cursorBeforeBatch) {
                break;
            }
        } while (count($orders) === $batchSize);

        $output->writeln(sprintf(
            'Auto cancel: cancelled %d, replayed %d, contended %d, skipped %d, failed %d.',
            $stats['cancelled'],
            $stats['replayed'],
            $stats['contended'],
            $stats['skipped'],
            $stats['failed']
        ));
        return $stats['failed'] === 0 ? 0 : 1;
    }

    protected function resolveOrderRepository(): OrderOrderRepository
    {
        return app()->make(OrderOrderRepository::class);
    }

    protected function resolveOrderService(): OrderService
    {
        return app()->make(OrderService::class);
    }

    protected function currentDateTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function batchSize(): int
    {
        return self::DEFAULT_BATCH_SIZE;
    }
}
