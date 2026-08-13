<?php
declare(strict_types=1);

namespace app\command;

use app\model\order\OrderOrder;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Event;

class PickupScanTimeoutCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('pickup:scan-timeout')
            ->setDescription('扫描自提订单超时，置 pickup_status=timeout');
    }

    protected function execute(Input $input, Output $output): int
    {
        $now = date('Y-m-d H:i:s');
        $orders = OrderOrder::where('delivery_type', 'pickup')
            ->where('pickup_status', 'pending')
            ->where('pickup_timeout_at', '<', $now)
            ->select();

        $count = 0;
        foreach ($orders as $order) {
            try {
                OrderOrder::where('id', $order->id)->update([
                    'pickup_status' => 'timeout',
                    'updated_at'    => $now,
                ]);
                Event::trigger('order.pickup_timeout', ['order_id' => $order->id]);
                $count++;
            } catch (\Throwable $e) {
                $output->writeln("Failed to mark order #{$order->id} timeout: {$e->getMessage()}");
            }
        }

        $output->writeln(sprintf('[%s] 处理超时订单 %d 单', $now, $count));
        return 0;
    }
}
