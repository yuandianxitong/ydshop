<?php

declare(strict_types=1);

namespace app\listener\finance;

use app\service\finance\FinanceService;
use core\exception\BusinessException;
use think\facade\Log;

/**
 * 财务流水自动记录监听器
 *
 * 监听三类事件，通过事件数据中的特征字段区分：
 *   - payment.success            → income (order/recharge)
 *   - order.refund.completed     → refund
 *   - distribution.withdrawal.paid → expense
 */
class FinanceTransactionListener
{
    public function __construct(
        protected FinanceService $financeService,
    ) {
    }

    public function handle(array $event): void
    {
        $criticalPaymentSettlement = false;
        try {
            // 按特征字段判断事件类型
            if (isset($event['withdrawal_id'])) {
                $this->financeService->recordWithdrawalPaid($event);
            } elseif (isset($event['refund_id']) || isset($event['refund_no'])) {
                $this->financeService->recordRefundCompleted($event);
            } elseif (isset($event['biz_type'])) {
                $criticalPaymentSettlement = true;
                if ($this->financeService->recordPaymentSuccess($event) === null) {
                    throw new BusinessException('支付成功事件无法生成财务流水');
                }
            }
        } catch (\Throwable $e) {
            $this->reportError('FinanceTransactionListener 记录流水失败: ' . $e->getMessage(), $event);
            // payment.success 财务入账是 payment:resync 必须可观测的核心步骤；
            // 退款和提现沿用历史 best-effort，避免反向污染已完成状态机。
            if ($criticalPaymentSettlement) {
                throw $e;
            }
        }
    }

    /** 记录器故障不能覆盖原始财务异常。 */
    protected function reportError(string $message, array $context = []): void
    {
        try {
            Log::error($message, $context);
        } catch (\Throwable) {
        }
    }
}
