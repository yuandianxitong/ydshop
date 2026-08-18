<?php

declare(strict_types=1);

namespace app\service\finance;

use app\repository\finance\FinanceTransactionRepository;
use app\repository\member\MemberRechargeOrderRepository;
use app\repository\order\OrderOrderRepository;
use app\service\common\ExcelExportService;
use core\base\Service;
use core\plugin\HookManager;

class FinanceService extends Service
{
    protected FinanceTransactionRepository $financeTransactionRepo;
    /** @var object|null duck-typed 分销提现仓储；未安装分销插件时为 null */
    protected $withdrawalRepo = null;
    protected OrderOrderRepository $orderOrderRepository;
    protected MemberRechargeOrderRepository $memberRechargeOrderRepository;
    protected ExcelExportService $excelExportService;

    private function withdrawalRepo(): ?object
    {
        if (is_object($this->withdrawalRepo)) {
            return $this->withdrawalRepo;
        }
        $repo = HookManager::apply('finance.withdrawal_repo', [], null);
        return $this->withdrawalRepo = is_object($repo) ? $repo : null;
    }

    /**
     * 记录财务流水
     *
     * @param string $type           交易类型：income / expense / refund
     * @param string $bizType        业务类型：order / recharge / withdrawal / refund
     * @param int    $bizId          业务记录ID
     * @param string $bizNo          业务单号
     * @param float  $amount         金额
     * @param string $channel        支付渠道
     * @param string $tradeNo        第三方交易号
     * @param int    $userId         用户ID
     * @param string $remark         备注
     * @param string|null $eventKey  领域事件幂等键；手工流水可不传
     * @param string|null $occurredAt 可信业务发生时间；在线事件不传则使用当前时间
     * @return array<string, mixed>
     */
    public function recordTransaction(
        string $type,
        string $bizType,
        int    $bizId,
        string $bizNo,
        float  $amount,
        string $channel,
        string $tradeNo,
        int    $userId,
        string $remark = '',
        ?string $eventKey = null,
        ?string $occurredAt = null
    ): array {
        $data = [
            'event_key'       => $eventKey,
            'type'            => $type,
            'biz_type'        => $bizType,
            'biz_id'          => $bizId,
            'biz_no'          => $bizNo,
            'amount'          => $amount,
            'payment_channel' => $channel,
            'trade_no'        => $tradeNo,
            'user_id'         => $userId,
            'remark'          => $remark,
        ];
        if ($occurredAt !== null && trim($occurredAt) !== '') {
            $data['created_at'] = $this->normalizeOccurredAt($occurredAt);
        }
        return $this->financeTransactionRepo->createIdempotent($data);
    }

    /**
     * payment.success -> 订单/充值收入流水。
     *
     * 支付事件目前只保证携带支付单号，业务 ID 优先读事件中的显式字段，
     * 缺失时再通过业务单号安全解析；历史异常数据无法解析时兼容写 0。
     *
     * @return array<string, mixed>|null
     */
    public function recordPaymentSuccess(array $event): ?array
    {
        $bizType = (string)($event['biz_type'] ?? '');
        $orderNo = trim((string)($event['order_no'] ?? ''));
        $amount = (float)($event['amount'] ?? 0);
        if ($orderNo === '' || $amount <= 0 || !in_array($bizType, ['order', 'recharge'], true)) {
            return null;
        }

        $bizId = $this->resolvePaymentBizId($bizType, $orderNo, $event);
        $remark = $bizType === 'order' ? '订单支付' : '余额充值';

        return $this->recordTransaction(
            'income',
            $bizType,
            $bizId,
            $orderNo,
            $amount,
            (string)($event['channel'] ?? ''),
            (string)($event['trade_no'] ?? ''),
            (int)($event['user_id'] ?? 0),
            $remark,
            sprintf('payment.success:%s:%s', $bizType, $orderNo),
            $this->eventOccurredAt($event)
        );
    }

    /**
     * order.refund.completed -> 订单退款流水。
     *
     * @return array<string, mixed>|null
     */
    public function recordRefundCompleted(array $event): ?array
    {
        $refundId = (int)($event['refund_id'] ?? 0);
        $amount = (float)($event['refund_amount'] ?? 0);
        if ($refundId <= 0 || $amount <= 0) {
            return null;
        }

        $refundNo = trim((string)($event['refund_no'] ?? ''));
        $orderId = (int)($event['order_id'] ?? 0);
        $orderNo = trim((string)($event['order_no'] ?? ''));
        $remark = '订单退款';
        if ($orderId > 0 || $orderNo !== '') {
            $remark .= sprintf('（订单ID：%d，订单号：%s）', $orderId, $orderNo !== '' ? $orderNo : '-');
        }

        // 退款流水只能记录 provider 退款标识；历史数据没有 provider 标识时使用
        // 商户退款单号，绝不能把原支付 trade_no 冒充退款交易号。
        $tradeNo = trim((string)($event['refund_trade_no'] ?? ''));
        if ($tradeNo === '') {
            $tradeNo = $refundNo !== '' ? $refundNo : 'RF' . $refundId;
        }
        $referenceSource = trim((string)($event['refund_trade_no_source'] ?? ''));
        if ($referenceSource !== '') {
            $referenceLabels = [
                'wechat_refund_id' => '微信 refund_id',
                'alipay_out_request_no' => '支付宝 out_request_no',
                'merchant_refund_no' => '商户退款单号',
                'legacy_merchant_refund_no' => '历史商户退款单号',
            ];
            $remark .= '；退款标识来源：' . ($referenceLabels[$referenceSource] ?? $referenceSource);
        }

        return $this->recordTransaction(
            'refund',
            'refund',
            $refundId,
            $refundNo !== '' ? $refundNo : 'RF' . $refundId,
            $amount,
            (string)($event['payment_channel'] ?? $event['channel'] ?? ''),
            $tradeNo,
            (int)($event['user_id'] ?? 0),
            $remark,
            'order.refund.completed:' . $refundId,
            $this->eventOccurredAt($event)
        );
    }

    /**
     * distribution.withdrawal.paid -> 提现现金支出流水。
     *
     * expense 金额始终使用实际到账 net；申请 gross 与手续费 fee 只写备注，
     * 防止报表把手续费重复计为另一笔提现支出。
     *
     * @return array<string, mixed>|null
     */
    public function recordWithdrawalPaid(array $event): ?array
    {
        $withdrawalId = (int)($event['withdrawal_id'] ?? 0);
        if ($withdrawalId <= 0) {
            return null;
        }

        $withdrawal = $this->withdrawalRepo()?->find($withdrawalId) ?? [];
        $net = (float)($event['actual_amount'] ?? $withdrawal['actual_amount'] ?? 0);
        if ($net <= 0) {
            return null;
        }

        $hasGross = array_key_exists('gross_amount', $event)
            || array_key_exists('amount', $event)
            || array_key_exists('amount', $withdrawal);
        $hasFee = array_key_exists('fee', $event) || array_key_exists('fee', $withdrawal);
        $gross = (float)($event['gross_amount'] ?? $event['amount'] ?? $withdrawal['amount'] ?? 0);
        $fee = (float)($event['fee'] ?? $withdrawal['fee'] ?? 0);

        if (!$hasGross && $hasFee) {
            $gross = $net + $fee;
            $hasGross = true;
        } elseif ($hasGross && !$hasFee) {
            $fee = max(0.0, $gross - $net);
            $hasFee = true;
        }

        if ($hasGross && $hasFee) {
            $remark = sprintf(
                '分销提现打款：申请金额 ¥%s，手续费 ¥%s，实际到账 ¥%s',
                number_format($gross, 2, '.', ''),
                number_format($fee, 2, '.', ''),
                number_format($net, 2, '.', '')
            );
        } else {
            $remark = sprintf(
                '分销提现打款：实际到账 ¥%s；申请金额/手续费未随事件提供',
                number_format($net, 2, '.', '')
            );
        }

        return $this->recordTransaction(
            'expense',
            'withdrawal',
            $withdrawalId,
            (string)($event['withdrawal_no'] ?? 'WD' . $withdrawalId),
            $net,
            (string)($event['channel'] ?? $event['type'] ?? $withdrawal['type'] ?? ''),
            (string)($event['trade_no'] ?? $withdrawal['trade_no'] ?? ''),
            (int)($event['user_id'] ?? $withdrawal['user_id'] ?? 0),
            $remark,
            'distribution.withdrawal.paid:' . $withdrawalId,
            $this->eventOccurredAt($event, (string)($withdrawal['paid_at'] ?? ''))
        );
    }

    private function eventOccurredAt(array $event, string $fallback = ''): ?string
    {
        $value = trim((string)($event['occurred_at'] ?? $fallback));
        return $value !== '' ? $value : null;
    }

    private function normalizeOccurredAt(string $occurredAt): string
    {
        $timestamp = strtotime($occurredAt);
        if ($timestamp === false || $timestamp > time() + 300) {
            throw new \UnexpectedValueException('财务业务发生时间无效');
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function resolvePaymentBizId(string $bizType, string $orderNo, array $event): int
    {
        if ($bizType === 'order') {
            $explicitId = (int)($event['order_id'] ?? $event['biz_id'] ?? 0);
            if ($explicitId > 0) {
                return $explicitId;
            }

            return (int)($this->orderOrderRepository->findByOrderNo($orderNo)['id'] ?? 0);
        }

        $explicitId = (int)($event['recharge_order_id'] ?? $event['biz_id'] ?? 0);
        if ($explicitId > 0) {
            return $explicitId;
        }

        $businessOrderNo = str_starts_with($orderNo, 'RCH_') ? substr($orderNo, 4) : $orderNo;
        return (int)($this->memberRechargeOrderRepository->findWhere(['order_no' => $businessOrderNo])['id'] ?? 0);
    }

    /**
     * 获取财务流水分页列表
     *
     * Supported params: type, biz_type, start_date, end_date, keyword, page, limit
     */
    public function getList(array $params): array
    {
        $page  = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 15);
        return $this->financeTransactionRepo->getPageList($params, $page, $limit);
    }

    /**
     * 财务概览数据
     *
     * 返回：today_income, today_refund, pending_withdrawal, month_income
     */
    public function getOverview(): array
    {
        $todayIncome  = $this->financeTransactionRepo->todayIncome();
        $todayRefund  = $this->financeTransactionRepo->todayRefund();
        $monthIncome  = $this->financeTransactionRepo->monthIncome();

        $pendingWithdrawal = $this->withdrawalRepo()?->sumPending() ?? 0;

        return [
            'today_income'       => $todayIncome,
            'today_refund'       => $todayRefund,
            'pending_withdrawal' => $pendingWithdrawal,
            'month_income'       => $monthIncome,
        ];
    }

    /**
     * 近 N 天收入与退款趋势
     *
     * @param int $days 天数，如 7 / 30
     */
    public function getTrend(int $days = 7): array
    {
        return $this->financeTransactionRepo->getDailyTrend($days);
    }

    /**
     * 收入来源构成（按业务类型分组）
     *
     * 字段：type / label / amount —— 与前端 finance/overview 页面对齐。
     */
    public function getIncomeComposition(): array
    {
        $rows = $this->financeTransactionRepo->getIncomeComposition();

        $bizTypeLabels = [
            'order'    => '订单收款',
            'recharge' => '余额充值',
        ];

        return array_map(function (array $row) use ($bizTypeLabels) {
            return [
                'type'   => $row['biz_type'],
                'label'  => $bizTypeLabels[$row['biz_type']] ?? $row['biz_type'],
                'amount' => (float) $row['total'],
            ];
        }, $rows);
    }

    /**
     * 提现统计
     *
     * 返回 4 个状态（待审核 / 已审核 / 已打款 / 已拒绝）的金额与笔数 +
     * 本月已打款汇总，供财务概览页直接消费。
     */
    public function getWithdrawalStats(): array
    {
        $repo = $this->withdrawalRepo();
        if ($repo === null) {
            return [
                'pending_amount'   => 0.0,
                'pending_count'    => 0,
                'approved_amount'  => 0.0,
                'approved_count'   => 0,
                'rejected_amount'  => 0.0,
                'rejected_count'   => 0,
                'reviewing_amount' => 0.0,
                'reviewing_count'  => 0,
                'month_amount'     => 0.0,
                'month_count'      => 0,
            ];
        }
        $byStatus = $repo->getStatsByStatus();
        $monthly  = $repo->getMonthlyPaidStats();

        $pick = static fn (string $s) => [
            'amount' => (float) ($byStatus[$s]['amount'] ?? 0),
            'count'  => (int)   ($byStatus[$s]['count']  ?? 0),
        ];
        $pending  = $pick('pending');
        $approved = $pick('approved');
        $paid     = $pick('paid');
        $rejected = $pick('rejected');

        return [
            'pending_amount'   => $pending['amount'],
            'pending_count'    => $pending['count'],
            'approved_amount'  => $paid['amount'],
            'approved_count'   => $paid['count'],
            'rejected_amount'  => $rejected['amount'],
            'rejected_count'   => $rejected['count'],
            'reviewing_amount' => $approved['amount'],
            'reviewing_count'  => $approved['count'],
            'month_amount'     => (float) ($monthly['amount'] ?? 0),
            'month_count'      => (int)   ($monthly['count']  ?? 0),
        ];
    }

    /**
     * 导出资金流水 xlsx
     */
    public function exportTransactions(array $params): \think\Response
    {
        $rows = $this->financeTransactionRepo->getAllForExport($params, ExcelExportService::MAX_ROWS);
        $bizTypeLabels = [
            'order' => '订单收款', 'recharge' => '余额充值',
            'withdrawal' => '提现', 'refund' => '退款',
        ];
        $typeLabels = ['income' => '收入', 'expense' => '支出', 'refund' => '退款'];
        $headers = ['流水号', '业务单号', '类型', '业务类型', '金额', '支付渠道', '第三方交易号', '用户ID', '备注', '时间'];
        $data = array_map(fn ($r) => [
            $r['transaction_no'] ?? $r['id'],
            $r['biz_no'] ?? '',
            $typeLabels[$r['type'] ?? ''] ?? ($r['type'] ?? ''),
            $bizTypeLabels[$r['biz_type'] ?? ''] ?? ($r['biz_type'] ?? ''),
            $r['amount'] ?? 0,
            $r['payment_channel'] ?? '',
            $r['trade_no'] ?? '',
            $r['user_id'] ?? '',
            $r['remark'] ?? '',
            $r['created_at'] ?? '',
        ], $rows);
        return $this->excelExportService->streamXlsx(
            '资金流水_' . date('Ymd_His'),
            $headers,
            $data
        );
    }

    /**
     * 导出近 30 天月度报表 xlsx
     */
    public function exportMonthlyReport(): \think\Response
    {
        $rows = $this->financeTransactionRepo->getMonthlyReport();
        $headers = ['日期', 'GMV', '订单数', '退款金额', '净销售额', '其他收入', '支出', '净现金流'];
        $data = array_map(fn ($r) => [
            $r['date'],
            $r['revenue'],
            $r['orders'],
            $r['refunds'],
            $r['net_sales'],
            $r['other_income'],
            $r['expenses'],
            $r['cash_flow'],
        ], $rows);
        return $this->excelExportService->streamXlsx(
            '财务月报_' . date('Ymd'),
            $headers,
            $data
        );
    }
}
