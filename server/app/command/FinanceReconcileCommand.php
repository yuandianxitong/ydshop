<?php
declare(strict_types=1);

namespace app\command;

use app\service\finance\FinanceReconcileService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

/** 按本地业务事实幂等补齐财务流水；默认执行，--dry-run 仅审计。 */
class FinanceReconcileCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('finance:reconcile')
            ->setDescription('Reconcile paid payments, completed refunds and paid withdrawals')
            ->addOption('batch', null, Option::VALUE_REQUIRED, '主键游标批次大小（1-1000）', 200)
            ->addOption('dry-run', null, Option::VALUE_NONE, '只扫描和校验，不写财务流水');
    }

    protected function execute(Input $input, Output $output): int
    {
        $batchSize = min(1000, max(1, (int)$input->getOption('batch')));
        $apply = !(bool)$input->getOption('dry-run');
        $stats = $this->resolveService()->reconcile($apply, $batchSize);

        $mode = $apply ? 'apply' : 'dry-run';
        $output->writeln(sprintf(
            'Finance reconciliation (%s): scanned payments=%d refunds=%d withdrawals=%d.',
            $mode,
            $stats['scanned']['payments'],
            $stats['scanned']['refunds'],
            $stats['scanned']['withdrawals']
        ));
        $output->writeln(sprintf(
            'Processed payments=%d refunds=%d withdrawals=%d; would-process=%d; failed=%d.',
            $stats['processed']['payments'],
            $stats['processed']['refunds'],
            $stats['processed']['withdrawals'],
            array_sum($stats['would_process']),
            count($stats['failed'])
        ));

        foreach ($stats['failed'] as $failure) {
            $output->writeln(sprintf(
                '  [%s:%d] %s',
                $failure['source'],
                $failure['id'],
                $failure['error']
            ));
        }

        return $stats['failed'] === [] ? 0 : 1;
    }

    protected function resolveService(): FinanceReconcileService
    {
        return app()->make(FinanceReconcileService::class);
    }
}
