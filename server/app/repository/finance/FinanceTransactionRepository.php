<?php

declare(strict_types=1);

namespace app\repository\finance;

use app\model\finance\FinanceTransaction;
use core\base\Repository;
use core\exception\BusinessException;
use think\db\exception\DuplicateException;
use think\Model as ThinkModel;

class FinanceTransactionRepository extends Repository
{
    private const MAX_INSERT_ATTEMPTS = 5;

    protected function getModel(): ThinkModel
    {
        return new FinanceTransaction();
    }

    /** 创建流水记录；event_key 存在时自动使用幂等写入。 */
    public function create(array $data): array
    {
        return $this->createIdempotent($data);
    }

    /**
     * 依赖数据库唯一键的原子 create-or-get。
     *
     * 先读取 event_key 以支持兼容接管历史 NULL 行；最终并发正确性仍由数据库
     * 唯一键保证，DuplicateException 后读取胜出记录。transaction_no 碰撞时
     * 会换号重试。
     */
    public function createIdempotent(array $data): array
    {
        $eventKey = trim((string)($data['event_key'] ?? ''));
        $data['event_key'] = $eventKey !== '' ? $eventKey : null;
        $lastDuplicate = null;

        if ($eventKey !== '') {
            $existing = $this->findByEventKey($eventKey);
            if ($existing !== null) {
                $this->assertSameBusinessFact($existing, $data);
                return $existing;
            }

            // 升级前流水没有 event_key。先按可信业务身份寻找唯一旧流水并原子
            // 接管；冲突时宁可报错人工核对，也不能再插入一笔造成财务重复。
            $legacyCandidates = $this->findLegacyCandidates($data);
            if (count($legacyCandidates) > 1) {
                throw new BusinessException('发现多条无事件键的历史财务流水，需人工核对');
            }
            if ($legacyCandidates !== []) {
                $legacy = $legacyCandidates[0];
                $this->assertSameBusinessFact($legacy, $data);
                if ($this->claimLegacyEventKey((int)$legacy['id'], $eventKey)) {
                    $legacy['event_key'] = $eventKey;
                    return $legacy;
                }

                // 并发接管：只有同一 event_key 的胜出者可被视为幂等成功。
                $existing = $this->findByEventKey($eventKey);
                if ($existing !== null) {
                    $this->assertSameBusinessFact($existing, $data);
                    return $existing;
                }
                throw new BusinessException('历史财务流水已被其他事件接管，需人工核对');
            }
        }

        for ($attempt = 0; $attempt < self::MAX_INSERT_ATTEMPTS; $attempt++) {
            $data['transaction_no'] = FinanceTransaction::generateTransactionNo();

            try {
                return $this->insertTransaction($data);
            } catch (\Throwable $e) {
                if (!$this->isDuplicateKey($e)) {
                    throw $e;
                }

                $lastDuplicate = $e;
                if ($eventKey !== '') {
                    $existing = $this->findByEventKey($eventKey);
                    if ($existing !== null) {
                        $this->assertSameBusinessFact($existing, $data);
                        return $existing;
                    }
                }
                // 未找到同 event_key，说明更可能是 transaction_no 碰撞，换号重试。
            }
        }

        throw $lastDuplicate ?? new \RuntimeException('财务流水写入失败');
    }

    public function findByEventKey(string $eventKey): ?array
    {
        $row = $this->model->where('event_key', $eventKey)->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function findLegacyCandidates(array $data): array
    {
        $bizId = (int)($data['biz_id'] ?? 0);
        $bizNo = trim((string)($data['biz_no'] ?? ''));
        if ($bizId <= 0 && $bizNo === '') {
            return [];
        }

        $query = $this->model
            ->whereNull('event_key')
            ->where('type', (string)($data['type'] ?? ''))
            ->where('biz_type', (string)($data['biz_type'] ?? ''));

        $query->where(function ($identity) use ($bizId, $bizNo): void {
            if ($bizId > 0) {
                $identity->where('biz_id', $bizId);
            }
            if ($bizNo !== '') {
                if ($bizId > 0) {
                    $identity->whereOr('biz_no', $bizNo);
                } else {
                    $identity->where('biz_no', $bizNo);
                }
            }
        });

        return $query->order('id', 'asc')->limit(2)->select()->toArray();
    }

    protected function claimLegacyEventKey(int $id, string $eventKey): bool
    {
        return $this->model
            ->where('id', $id)
            ->whereNull('event_key')
            ->update(['event_key' => $eventKey]) === 1;
    }

    /**
     * 同一业务事实允许历史行缺少非关键字段，但任何已存在的金额、身份、渠道
     * 或交易号冲突都必须停止，禁止把另一笔流水错误接管。
     */
    protected function assertSameBusinessFact(array $existing, array $expected): void
    {
        foreach (['type', 'biz_type'] as $field) {
            if ((string)($existing[$field] ?? '') !== (string)($expected[$field] ?? '')) {
                throw new BusinessException('财务事件键对应的业务类型冲突');
            }
        }

        $existingCents = (int)round((float)($existing['amount'] ?? 0) * 100);
        $expectedCents = (int)round((float)($expected['amount'] ?? 0) * 100);
        if ($existingCents !== $expectedCents) {
            throw new BusinessException('历史财务流水金额与当前业务事实冲突');
        }

        foreach (['biz_id', 'user_id'] as $field) {
            $old = (int)($existing[$field] ?? 0);
            $new = (int)($expected[$field] ?? 0);
            if ($old > 0 && $new > 0 && $old !== $new) {
                throw new BusinessException('历史财务流水业务归属冲突');
            }
        }

        foreach (['biz_no', 'payment_channel', 'trade_no'] as $field) {
            $old = trim((string)($existing[$field] ?? ''));
            $new = trim((string)($expected[$field] ?? ''));
            if ($old !== '' && $new !== '' && !hash_equals($old, $new)) {
                throw new BusinessException('历史财务流水标识与当前业务事实冲突');
            }
        }
    }

    /** @return array<string, mixed> */
    protected function insertTransaction(array $data): array
    {
        return $this->model->create($data)->toArray();
    }

    protected function isDuplicateKey(\Throwable $e): bool
    {
        return $e instanceof DuplicateException
            || str_contains($e->getMessage(), '1062 Duplicate entry');
    }

    /**
     * 分页列表，支持过滤：type, biz_type, start_date, end_date, keyword(biz_no/transaction_no)
     */
    public function getPageList(array $params = [], int $page = 1, int $limit = 15): array
    {
        $query = $this->model->order('id', 'desc');

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['biz_type'])) {
            $query->where('biz_type', $params['biz_type']);
        }

        if (!empty($params['start_date'])) {
            $query->where('created_at', '>=', $params['start_date'] . ' 00:00:00');
        }

        if (!empty($params['end_date'])) {
            $query->where('created_at', '<=', $params['end_date'] . ' 23:59:59');
        }

        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('biz_no', 'like', "%{$keyword}%")
                  ->whereOr('transaction_no', 'like', "%{$keyword}%");
            });
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int) ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 导出所有匹配的流水（不分页）
     *
     * 复用 getPageList 的过滤逻辑（type / biz_type / start_date / end_date / keyword）。
     */
    public function getAllForExport(array $params, int $maxRows): array
    {
        $query = $this->model->order('id', 'desc');

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (!empty($params['biz_type'])) {
            $query->where('biz_type', $params['biz_type']);
        }
        if (!empty($params['start_date'])) {
            $query->where('created_at', '>=', $params['start_date'] . ' 00:00:00');
        }
        if (!empty($params['end_date'])) {
            $query->where('created_at', '<=', $params['end_date'] . ' 23:59:59');
        }
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('biz_no', 'like', "%{$keyword}%")
                  ->whereOr('transaction_no', 'like', "%{$keyword}%");
            });
        }

        return $query->limit($maxRows + 1)->select()->toArray();
    }

    /**
     * 月度报表数据：近 30 天每日汇总（单 GROUP BY 查询）
     *
     * 返回每天一行：
     * - revenue: 仅订单收入（GMV）
     * - other_income: 充值等非订单现金流入
     * - refunds: 订单退款
     * - expenses: 提现等现金流出
     * - net_income/net_sales: 订单收入 - 退款（net_income 保留为旧字段兼容）
     * - cash_flow: 全部收入 - 退款 - 支出
     * 缺失日期填 0。
     */
    public function getMonthlyReport(): array
    {
        $since = date('Y-m-d', strtotime('-29 days'));
        $sinceTime = $since . ' 00:00:00';

        $rows = $this->model->field([
            'DATE(created_at) as date',
            "SUM(CASE WHEN type = 'income' AND biz_type = 'order' THEN amount ELSE 0 END) as revenue",
            "SUM(CASE WHEN type = 'income' AND biz_type <> 'order' THEN amount ELSE 0 END) as other_income",
            "SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as refunds",
            "SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses",
            "COUNT(DISTINCT CASE WHEN type = 'income' AND biz_type = 'order' THEN COALESCE(NULLIF(biz_no, ''), CONCAT('id:', biz_id)) END) as orders",
        ])
        ->where('created_at', '>=', $sinceTime)
        ->group('DATE(created_at)')
        ->order('date', 'asc')
        ->select()
        ->toArray();

        // 转 map 方便填充缺失日期
        $byDate = [];
        foreach ($rows as $r) {
            $revenue = (float)($r['revenue'] ?? 0);
            $otherIncome = (float)($r['other_income'] ?? 0);
            $refunds = (float)($r['refunds'] ?? 0);
            $expenses = (float)($r['expenses'] ?? 0);
            $netSales = round($revenue - $refunds, 2);
            $byDate[$r['date']] = [
                'date'       => $r['date'],
                'revenue'    => $revenue,
                'other_income' => $otherIncome,
                'refunds'    => $refunds,
                'expenses'   => $expenses,
                'orders'     => (int)($r['orders'] ?? 0),
                'net_sales'  => $netSales,
                'net_income' => $netSales,
                'cash_flow'  => round($revenue + $otherIncome - $refunds - $expenses, 2),
            ];
        }

        // 填充缺失日期为 0
        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = $byDate[$date] ?? [
                'date'       => $date,
                'revenue'    => 0.0,
                'other_income' => 0.0,
                'refunds'    => 0.0,
                'expenses'   => 0.0,
                'orders'     => 0,
                'net_sales'  => 0.0,
                'net_income' => 0.0,
                'cash_flow'  => 0.0,
            ];
        }
        return $result;
    }

    /**
     * 今日收入合计
     */
    public function todayIncome(): float
    {
        return (float) $this->model->where('type', 'income')
            ->whereDay('created_at')
            ->sum('amount');
    }

    /**
     * 今日退款合计
     */
    public function todayRefund(): float
    {
        return (float) $this->model->where('type', 'refund')
            ->whereDay('created_at')
            ->sum('amount');
    }

    /**
     * 本月总收入
     */
    public function monthIncome(): float
    {
        return (float) $this->model->where('type', 'income')
            ->whereMonth('created_at')
            ->sum('amount');
    }

    /**
     * 近 N 天每日收入与退款趋势（单 GROUP BY 查询）
     *
     * 返回：[['date'=>'2026-03-31','income'=>xxx,'refund'=>xxx], ...]
     * 缺失日期填 0。
     */
    public function getDailyTrend(int $days): array
    {
        $since = date('Y-m-d', strtotime('-' . max(0, $days - 1) . ' days'));
        $sinceTime = $since . ' 00:00:00';

        $rows = $this->model->field([
            'DATE(created_at) as date',
            "SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income",
            "SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as refund",
        ])
        ->where('created_at', '>=', $sinceTime)
        ->group('DATE(created_at)')
        ->order('date', 'asc')
        ->select()
        ->toArray();

        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r['date']] = [
                'date'   => $r['date'],
                'income' => (float) $r['income'],
                'refund' => (float) $r['refund'],
            ];
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = $byDate[$date] ?? [
                'date'   => $date,
                'income' => 0.0,
                'refund' => 0.0,
            ];
        }
        return $result;
    }

    /**
     * 收入来源构成（按 biz_type 分组统计）
     */
    public function getIncomeComposition(): array
    {
        return $this->model->where('type', 'income')
            ->field('biz_type, SUM(amount) as total')
            ->group('biz_type')
            ->select()
            ->toArray();
    }
}
