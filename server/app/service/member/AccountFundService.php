<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberRechargeOrderRepository;
use app\repository\user\BalanceLogRepository;
use app\repository\user\UserRepository;
use app\service\common\ExcelExportService;
use core\base\Service;
use core\exception\BusinessException;
use core\plugin\HookManager;

class AccountFundService extends Service
{
    protected BalanceLogRepository $balanceLogRepo;
    protected MemberRechargeOrderRepository $rechargeRepo;
    /** @var object|null */
    protected $withdrawalRepo = null;
    protected UserRepository $userRepo;
    protected ExcelExportService $excelExportService;
    /** @var object|null */
    protected $distributionWithdrawalService = null;

    private function withdrawalRepo(): ?object
    {
        if (is_object($this->withdrawalRepo)) {
            return $this->withdrawalRepo;
        }
        $repo = HookManager::apply('finance.withdrawal_repo', [], null);
        return $this->withdrawalRepo = is_object($repo) ? $repo : null;
    }

    private function distributionWithdrawalService(): object
    {
        if (is_object($this->distributionWithdrawalService)) {
            return $this->distributionWithdrawalService;
        }
        $svc = HookManager::apply('finance.withdrawal_service', [], null);
        if (!is_object($svc)) {
            throw new BusinessException('分销插件未安装');
        }
        return $this->distributionWithdrawalService = $svc;
    }

    /**
     * KPI 统计
     */
    public function getStats(): array
    {
        $balanceTotal = $this->userRepo->sumBalance();

        $repo = $this->withdrawalRepo();
        return [
            'balance_total'   => round($balanceTotal, 2),
            'recharge_month'  => round($this->rechargeRepo->sumThisMonth(), 2),
            'withdraw_month'  => round($repo ? $repo->sumPaidThisMonth() : 0, 2),
            'pending_count'   => $repo ? $repo->countPending() : 0,
        ];
    }

    /**
     * 余额变动列表
     */
    public function getBalanceLogs(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);

        if (!empty($params['keyword'])) {
            $params['user_ids'] = $this->userRepo->searchIdsByKeyword($params['keyword']);
            if (empty($params['user_ids'])) {
                return ['list' => [], 'pagination' => [
                    'current_page' => $page, 'per_page' => $limit, 'total' => 0, 'last_page' => 1,
                ]];
            }
        }

        return $this->balanceLogRepo->getSearchList($params, $page, $limit);
    }

    /**
     * 充值记录列表
     */
    public function getRechargeOrders(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        return $this->rechargeRepo->getPageList($params, $page, $limit);
    }

    /**
     * 提现申请列表
     */
    public function getWithdrawals(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        $repo = $this->withdrawalRepo();
        if ($repo === null) {
            return ['list' => [], 'pagination' => [
                'current_page' => $page, 'per_page' => $limit, 'total' => 0, 'last_page' => 1,
            ]];
        }
        if (!empty($params['keyword'])) {
            $params['user_ids'] = $this->userRepo->searchIdsByKeyword($params['keyword']);
        }
        return $repo->getPageList($params, $page, $limit);
    }

    /**
     * 提现审核通过
     */
    public function approveWithdrawal(int $id): bool
    {
        return $this->distributionWithdrawalService()->approve($id);
    }

    /**
     * 提现拒绝
     */
    public function rejectWithdrawal(int $id, string $remark): bool
    {
        return $this->distributionWithdrawalService()->reject($id, $remark);
    }

    /**
     * 提现打款
     */
    public function payWithdrawal(
        int $id,
        string $payoutReference,
        string $payoutProof = '',
        ?int $operatorId = null
    ): bool
    {
        return $this->distributionWithdrawalService()->pay(
            $id,
            $payoutReference,
            $payoutProof,
            $operatorId
        );
    }

    /**
     * 导出账户对账单 xlsx（按当前 tab dispatch）
     */
    public function exportXlsx(array $params): \think\Response
    {
        $tab = (string)($params['tab'] ?? 'bal');
        return match ($tab) {
            'bal'  => $this->exportBalanceLogs($params),
            'rech' => $this->exportRechargeOrders($params),
            'wd'   => $this->exportWithdrawals($params),
            default => throw new BusinessException('无效的 tab 参数: ' . $tab),
        };
    }

    /**
     * tab=bal: 余额变动 xlsx
     */
    private function exportBalanceLogs(array $params): \think\Response
    {
        if (!empty($params['keyword'])) {
            $params['user_ids'] = $this->userRepo->searchIdsByKeyword($params['keyword']) ?: [0];
        }
        $rows = $this->balanceLogRepo->getAllForExport($params, ExcelExportService::MAX_ROWS);
        $headers = ['流水号', '会员', '变动类型', '变动金额', '变动后余额', '操作员', '说明', '操作时间'];
        $data = array_map(fn($r) => [
            $r['id'],
            $r['user_nickname'] ?? '-',
            (string)($r['type'] ?? ''),
            $r['amount'] ?? 0,
            $r['after_balance'] ?? 0,
            $r['operator_name'] ?? '-',
            $r['remark'] ?? '',
            $r['created_at'] ?? '',
        ], $rows);
        return $this->excelExportService->streamXlsx(
            '余额变动_' . date('Ymd_His'),
            $headers,
            $data
        );
    }

    /**
     * tab=rech: 充值记录 xlsx
     */
    private function exportRechargeOrders(array $params): \think\Response
    {
        $rows = $this->rechargeRepo->getAllForExport($params, ExcelExportService::MAX_ROWS);
        $statusLabels = [
            0 => '待支付', 1 => '已支付',
        ];
        $headers = ['充值单号', '会员', '会员手机', '充值金额', '赠送金额', '支付方式', '状态', '创建时间', '支付时间'];
        $data = array_map(fn($r) => [
            $r['order_no'] ?? '',
            $r['user_nickname'] ?? '-',
            $r['user_mobile'] ?? '',
            $r['amount'] ?? 0,
            $r['gift_amount'] ?? 0,
            $r['pay_type'] ?? '',
            $statusLabels[(int)($r['status'] ?? -1)] ?? (string)($r['status'] ?? ''),
            $r['created_at'] ?? '',
            $r['paid_at'] ?? '',
        ], $rows);
        return $this->excelExportService->streamXlsx(
            '充值记录_' . date('Ymd_His'),
            $headers,
            $data
        );
    }

    /**
     * tab=wd: 提现申请 xlsx
     */
    private function exportWithdrawals(array $params): \think\Response
    {
        $repo = $this->withdrawalRepo();
        if ($repo === null) {
            throw new BusinessException('分销插件未安装');
        }
        if (!empty($params['keyword'])) {
            $params['user_ids'] = $this->userRepo->searchIdsByKeyword($params['keyword']) ?: [0];
        }
        $rows = $repo->getAllForExport($params, ExcelExportService::MAX_ROWS);
        $statusLabels = [
            'pending' => '待审核', 'approved' => '已审核', 'paid' => '已打款',
            'rejected' => '已拒绝', 'failed' => '失败',
        ];
        $headers = ['申请编号', '会员', '手机', '申请金额', '实际到账', '类型', '状态', '申请时间', '处理时间', '备注'];
        $data = array_map(fn($r) => [
            $r['withdraw_no'] ?? $r['id'],
            $r['user_nickname'] ?? '-',
            $r['user_mobile'] ?? '',
            $r['amount'] ?? 0,
            $r['actual_amount'] ?? 0,
            $r['type'] ?? '',
            $statusLabels[$r['status'] ?? ''] ?? ($r['status'] ?? ''),
            $r['created_at'] ?? '',
            $r['paid_at'] ?? '',
            $r['remark'] ?? '',
        ], $rows);
        return $this->excelExportService->streamXlsx(
            '提现申请_' . date('Ymd_His'),
            $headers,
            $data
        );
    }
}
