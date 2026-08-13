<?php
declare(strict_types=1);

namespace app\command;

use app\model\order\OrderItem;
use app\model\order\OrderOrder;
use app\model\order\OrderReview;
use app\model\system\SystemConfig;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class OrderAutoReviewCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('order:auto-review')
            ->setDescription('Auto good review after 15 days of completion');
    }

    protected function execute(Input $input, Output $output): int
    {
        $days = (int) SystemConfig::getConfigValue('order.auto_review_days', 15);
        if ($days <= 0) {
            $output->writeln('Auto review is disabled (days=0).');
            return 0;
        }
        $deadline = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $orders = OrderOrder::where('status', OrderOrder::STATUS_COMPLETED)
            ->where('receive_time', '<=', $deadline)
            ->select();

        $count = 0;
        foreach ($orders as $order) {
            $items = OrderItem::where('order_id', $order->id)
                ->where('is_reviewed', 0)
                ->select();

            foreach ($items as $item) {
                try {
                    OrderReview::create([
                        'order_item_id' => $item->id,
                        'user_id'       => $order->user_id,
                        'spu_id'        => $item->spu_id,
                        'sku_id'        => $item->sku_id,
                        'rating'        => 5,
                        'content'       => '系统默认好评',
                        'images'        => [],
                        'is_anonymous'  => 0,
                    ]);
                    $item->is_reviewed = 1;
                    $item->save();
                    $count++;
                } catch (\Exception $e) {
                    $output->writeln("Failed to auto review item {$item->id}: {$e->getMessage()}");
                }
            }
        }

        $output->writeln("Auto reviewed {$count} items.");
        return 0;
    }
}
