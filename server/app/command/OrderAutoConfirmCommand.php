<?php
declare(strict_types=1);

namespace app\command;

use app\repository\system\SystemConfigRepository;
use app\service\order\OrderShipService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class OrderAutoConfirmCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('order:auto-confirm')
            ->setDescription('Auto confirm received after 7 days of shipping');
    }

    protected function execute(Input $input, Output $output): int
    {
        $days = (int)$this->resolveConfigRepository()->getConfigValue(
            'order.auto_confirm_days',
            7
        );
        if ($days <= 0) {
            $output->writeln('Auto confirm is disabled (days=0).');
            return 0;
        }
        $deadline = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $stats = $this->resolveOrderShipService()->autoConfirmExpired($deadline);
        $output->writeln(sprintf(
            'Auto confirm: completed %d, replayed %d, refund processing %d, skipped %d, failed %d.',
            $stats['completed'],
            $stats['replayed'],
            $stats['refund_processing'],
            $stats['skipped'],
            $stats['failed']
        ));
        return $stats['failed'] === 0 ? 0 : 1;
    }

    protected function resolveConfigRepository(): SystemConfigRepository
    {
        return app()->make(SystemConfigRepository::class);
    }

    protected function resolveOrderShipService(): OrderShipService
    {
        return app()->make(OrderShipService::class);
    }
}
