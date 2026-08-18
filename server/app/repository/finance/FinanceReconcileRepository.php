<?php
declare(strict_types=1);

namespace app\repository\finance;

use app\model\order\OrderRefund;
use app\model\payment\PaymentOrder;
use core\base\Repository;
use core\plugin\HookManager;
use think\Model as ThinkModel;

/**
 * 财务流水补偿的数据源读取。
 *
 * 三类来源均按单调递增主键游标扫描，避免 offset 在大表上的性能退化，
 * 也避免运行期间新增记录导致分页漂移。这里只读取业务事实，不写状态。
 */
class FinanceReconcileRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new PaymentOrder();
    }

    /**
     * 扫描所有曾成功支付的本地支付单。全额退款后 status 会变为 refunded，
     * 但原支付收入依然是必须存在的现金流事实，因此也纳入扫描。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaidPaymentsAfterId(int $afterId, int $limit): array
    {
        return $this->model
            ->alias('payment')
            ->leftJoin(
                'order_orders order_record',
                "order_record.order_no = payment.business_order_no
                OR (
                    (payment.business_order_no IS NULL OR payment.business_order_no = '')
                    AND (
                        order_record.order_no = payment.order_no
                        OR CONCAT('ORD_', order_record.order_no) = payment.order_no
                    )
                )"
            )
            ->leftJoin(
                'member_recharge_orders recharge_prefixed',
                "CONCAT('RCH_', recharge_prefixed.order_no) = payment.order_no"
            )
            ->leftJoin(
                'member_recharge_orders recharge_direct',
                'recharge_direct.order_no = payment.order_no'
            )
            ->leftJoin(
                'finance_transactions reconciled',
                "reconciled.event_key = CONCAT(
                    'payment.success:',
                    CASE
                        WHEN payment.biz_type IN ('order', 'recharge') THEN payment.biz_type
                        WHEN order_record.id IS NOT NULL THEN 'order'
                        WHEN recharge_prefixed.id IS NOT NULL OR recharge_direct.id IS NOT NULL THEN 'recharge'
                        ELSE ''
                    END,
                    ':', payment.order_no
                )
                AND reconciled.type = 'income'
                AND reconciled.biz_type = CASE
                    WHEN payment.biz_type IN ('order', 'recharge') THEN payment.biz_type
                    WHEN order_record.id IS NOT NULL THEN 'order'
                    WHEN recharge_prefixed.id IS NOT NULL OR recharge_direct.id IS NOT NULL THEN 'recharge'
                    ELSE ''
                END
                AND reconciled.biz_no = payment.order_no
                AND ROUND(reconciled.amount * 100) = ROUND(payment.total_amount * 100)
                AND (reconciled.biz_id = 0 OR reconciled.biz_id = CASE
                    WHEN payment.biz_type = 'order' THEN COALESCE(order_record.id, 0)
                    WHEN payment.biz_type = 'recharge' THEN COALESCE(recharge_prefixed.id, recharge_direct.id, 0)
                    WHEN order_record.id IS NOT NULL THEN order_record.id
                    ELSE COALESCE(recharge_prefixed.id, recharge_direct.id, 0)
                END)
                AND (reconciled.user_id = 0 OR reconciled.user_id = COALESCE(
                    payment.user_id, order_record.user_id,
                    recharge_prefixed.user_id, recharge_direct.user_id, 0
                ))
                AND (reconciled.payment_channel = '' OR reconciled.payment_channel = payment.channel)
                AND (reconciled.trade_no = '' OR payment.trade_no = '' OR reconciled.trade_no = payment.trade_no)"
            )
            ->whereIn('payment.status', [PaymentOrder::STATUS_PAID, PaymentOrder::STATUS_REFUNDED])
            ->where('payment.id', '>', max(0, $afterId))
            ->whereNull('reconciled.id')
            ->field([
                'payment.id',
                'payment.order_no',
                'payment.business_order_no',
                'payment.biz_type',
                'payment.user_id',
                'payment.channel',
                'payment.trade_no',
                'payment.total_amount',
                'payment.status',
                'payment.paid_at',
                "CASE
                    WHEN payment.biz_type IN ('order', 'recharge') THEN payment.biz_type
                    WHEN order_record.id IS NOT NULL THEN 'order'
                    WHEN recharge_prefixed.id IS NOT NULL OR recharge_direct.id IS NOT NULL THEN 'recharge'
                    ELSE ''
                 END AS resolved_biz_type",
                'CASE
                    WHEN payment.biz_type = \'order\' THEN COALESCE(order_record.id, 0)
                    WHEN payment.biz_type = \'recharge\' THEN COALESCE(recharge_prefixed.id, recharge_direct.id, 0)
                    WHEN order_record.id IS NOT NULL THEN order_record.id
                    ELSE COALESCE(recharge_prefixed.id, recharge_direct.id, 0)
                 END AS resolved_biz_id',
                'COALESCE(payment.user_id, order_record.user_id, recharge_prefixed.user_id, recharge_direct.user_id, 0) AS resolved_user_id',
            ])
            ->order('payment.id', 'asc')
            ->limit($this->normalizeLimit($limit))
            ->select()
            ->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    public function getRefundedAfterId(int $afterId, int $limit): array
    {
        $refundModel = new OrderRefund();

        return $refundModel
            ->alias('refund')
            ->leftJoin('order_orders order_record', 'order_record.id = refund.order_id')
            ->leftJoin(
                'order_payments order_payment',
                'order_payment.order_id = refund.order_id AND order_payment.status IN (1, 2)'
            )
            ->leftJoin('payment_orders payment', 'payment.id = order_payment.payment_order_id')
            ->leftJoin(
                'finance_transactions reconciled',
                "reconciled.event_key = CONCAT('order.refund.completed:', refund.id)
                AND reconciled.type = 'refund'
                AND reconciled.biz_type = 'refund'
                AND (reconciled.biz_id = 0 OR reconciled.biz_id = refund.id)
                AND reconciled.biz_no = refund.refund_no
                AND ROUND(reconciled.amount * 100) = ROUND(refund.refund_amount * 100)
                AND (reconciled.user_id = 0 OR reconciled.user_id = refund.user_id)
                AND (reconciled.payment_channel = '' OR payment.channel IS NULL OR reconciled.payment_channel = payment.channel)
                AND (
                    reconciled.trade_no = ''
                    OR reconciled.trade_no = COALESCE(NULLIF(refund.refund_trade_no, ''), refund.refund_no)
                )"
            )
            ->where('refund.status', 'refunded')
            ->where('refund.id', '>', max(0, $afterId))
            ->whereNull('reconciled.id')
            ->field([
                'refund.id',
                'refund.refund_no',
                'refund.order_id',
                'refund.order_item_id',
                'refund.user_id',
                'refund.type',
                'refund.refund_amount',
                'refund.refund_trade_no',
                'refund.refund_trade_no_source',
                'COALESCE(refund.refunded_at, refund.updated_at) AS refunded_at',
                'order_record.order_no',
                'payment.channel AS payment_channel',
                'payment.trade_no AS payment_trade_no',
            ])
            ->order('refund.id', 'asc')
            ->limit($this->normalizeLimit($limit))
            ->select()
            ->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    public function getPaidWithdrawalsAfterId(int $afterId, int $limit): array
    {
        $rows = HookManager::apply('finance.reconcile_paid_withdrawals', [
            'after_id' => $afterId,
            'limit'    => $this->normalizeLimit($limit),
        ], []);
        return is_array($rows) ? $rows : [];
    }

    private function normalizeLimit(int $limit): int
    {
        return min(1000, max(1, $limit));
    }
}
