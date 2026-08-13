<?php
declare(strict_types=1);

namespace app\command;

use app\service\payment\PaymentResyncService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;

/**
 * 重放本地 payment_orders.status=paid 的幂等消费者。
 *
 * 用法：
 *   php think payment:resync                # 列出全部本地 paid 支付单（不动数据）
 *   php think payment:resync --apply        # 重放全部本地 paid 支付单消费者
 *   php think payment:resync --no=ORDER_NO  # 仅列出指定 paid 支付单（dry-run）
 *   php think payment:resync --no=ORDER_NO --apply  # 重放指定 paid 支付单消费者
 */
class PaymentResync extends Command
{
    protected function configure(): void
    {
        $this->setName('payment:resync')
            ->setDescription('扫描本地已支付单并幂等重放订单、充值及其他支付消费者')
            ->addOption('no', null, Option::VALUE_REQUIRED, '指定 order_no 单条重放')
            ->addOption('apply', null, Option::VALUE_NONE, '实际重放（默认 dry-run 只列出）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $apply = (bool)$input->getOption('apply');
        $singleNo = (string)$input->getOption('no');

        $service = $this->resolveResyncService();
        $payments = $service->scan($singleNo !== '' ? $singleNo : null);
        if ($payments === []) {
            $output->writeln($singleNo ? "[$singleNo] 找不到本地 paid payment_order" : '没有本地 paid payment_orders');
            return 0;
        }

        $output->writeln(sprintf('发现 %d 条本地已支付记录，可重放幂等消费者：', count($payments)));
        foreach ($payments as $i => $payment) {
            $output->writeln(sprintf(
                '  [%d] order_no=%s | payment_id=%d | biz_type=%s | amount=%s | trade_no=%s | paid_at=%s',
                $i + 1,
                $payment['order_no'],
                $payment['payment_order_id'],
                $payment['biz_type'] !== '' ? $payment['biz_type'] : '(待本地推断)',
                $payment['amount'],
                $payment['trade_no'] !== '' ? $payment['trade_no'] : '(空)',
                $payment['paid_at'] !== '' ? $payment['paid_at'] : '(空)',
            ));
        }

        if (!$apply) {
            $output->writeln('');
            $output->writeln('🔍 dry-run 模式，未重放。加 --apply 执行本地消费者修复（不会请求支付平台）');
            return 0;
        }

        $result = $service->replay(array_column($payments, 'payment_order_id'));
        $failedById = [];
        foreach ($result['failed'] as $failure) {
            $failedById[(int)$failure['payment_order_id']] = (string)$failure['error'];
        }
        foreach ($payments as $payment) {
            $paymentId = (int)$payment['payment_order_id'];
            if (isset($failedById[$paymentId])) {
                $output->writeln("  ✗ {$payment['order_no']} 重放失败: {$failedById[$paymentId]}");
            } else {
                $output->writeln("  ✓ {$payment['order_no']} 重放成功");
            }
        }

        $output->writeln('');
        $failureCount = count($result['failed']);
        $output->writeln(sprintf('完成：成功 %d 条，失败 %d 条', $result['success'], $failureCount));
        return $failureCount > 0 ? 1 : 0;
    }

    protected function resolveResyncService(): PaymentResyncService
    {
        return app(PaymentResyncService::class);
    }
}
