<?php
declare(strict_types=1);

namespace app\command;

use app\service\payment\PaymentResyncService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/** 自动重放发布边界后的本地支付成功消费者。 */
class PaymentReconcileCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('payment:reconcile')
            ->setDescription('Reconcile local paid payment consumers after the release boundary');
    }

    protected function execute(Input $input, Output $output): int
    {
        $service = $this->resolveService();
        $provider = $service->reconcileProvider();
        $result = $service->reconcileAutomatic();
        $output->writeln(sprintf(
            'Provider reconciliation from %s (safe before %s): scanned %d, success %d, failed %d, cursor %d.',
            $provider['from'], $provider['updated_before'], $provider['scanned'], $provider['success'], count($provider['failed']), $provider['cursor']
        ));
        foreach ($provider['failed'] as $failure) {
            $output->writeln(sprintf('  [provider payment:%d] %s', $failure['payment_order_id'], $failure['error']));
        }
        $output->writeln(sprintf(
            'Payment reconciliation from %s: scanned %d, success %d, failed %d.',
            $result['from'],
            $result['scanned'],
            $result['success'],
            count($result['failed'])
        ));
        foreach ($result['failed'] as $failure) {
            $output->writeln(sprintf(
                '  [payment:%d] %s',
                $failure['payment_order_id'],
                $failure['error']
            ));
        }
        return ($result['failed'] === [] && $provider['failed'] === []) ? 0 : 1;
    }

    protected function resolveService(): PaymentResyncService
    {
        return app()->make(PaymentResyncService::class);
    }
}
