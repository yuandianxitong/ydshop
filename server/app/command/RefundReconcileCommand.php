<?php
declare(strict_types=1);

namespace app\command;

use app\service\order\OrderRefundService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

/** 查询并结算长时间停留在 refunding 的渠道退款。 */
class RefundReconcileCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('refund:reconcile')
            ->setDescription('Query stale provider refunds and finalize confirmed successes')
            ->addOption('batch', null, Option::VALUE_REQUIRED, '主键游标批次大小（1-1000）', 200)
            ->addOption('stale-minutes', null, Option::VALUE_REQUIRED, '退款静默多久后查询（1-1440 分钟）', 10);
    }

    protected function execute(Input $input, Output $output): int
    {
        $stats = $this->resolveService()->reconcileStaleRefunds(
            (int)$input->getOption('batch'),
            (int)$input->getOption('stale-minutes')
        );

        $output->writeln(sprintf(
            'Refund reconciliation before %s: scanned %d, success %d, pending %d, failed %d.',
            $stats['stale_before'],
            $stats['scanned'],
            $stats['success'],
            $stats['pending'],
            count($stats['failed'])
        ));
        foreach ($stats['failed'] as $failure) {
            $output->writeln(sprintf(
                '  [refund:%d] %s',
                $failure['refund_id'],
                $failure['error']
            ));
        }

        return $stats['failed'] === [] ? 0 : 1;
    }

    protected function resolveService(): OrderRefundService
    {
        return app()->make(OrderRefundService::class);
    }
}
