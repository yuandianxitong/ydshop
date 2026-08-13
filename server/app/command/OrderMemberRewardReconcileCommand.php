<?php

declare(strict_types=1);

namespace app\command;

use app\service\member\OrderMemberRewardService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class OrderMemberRewardReconcileCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('member:reconcile-order-rewards')
            ->setDescription('Reconcile completed-order member rewards and refund reversals');
    }

    protected function execute(Input $input, Output $output): int
    {
        $stats = $this->resolveService()->reconcile();
        $output->writeln(sprintf(
            'Member rewards: awarded %d, reversed %d, skipped %d, failed %d.',
            $stats['awarded'],
            $stats['reversed'],
            $stats['skipped'],
            $stats['failed']
        ));
        return $stats['failed'] === 0 ? 0 : 1;
    }

    protected function resolveService(): OrderMemberRewardService
    {
        return app()->make(OrderMemberRewardService::class);
    }
}
