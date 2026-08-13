<?php
declare(strict_types=1);

namespace app\service\user;

use app\model\user\BalanceLog;
use app\model\user\PointsLog;
use app\repository\member\MemberAddressRepository;
use app\repository\member\MemberLevelRepository;
use app\repository\member\MemberRemarkRepository;
use app\repository\user\BalanceLogRepository;
use app\repository\user\PointsLogRepository;
use app\repository\user\PointsDebtLogRepository;
use app\repository\user\UserLoginLogRepository;
use app\repository\user\UserRepository;
use app\repository\user\UserTagRelationRepository;
use app\repository\user\UserOperationLogRepository;
use app\service\common\ExcelExportService;
use core\base\Service;

class UserManageService extends Service
{
    protected UserRepository $userRepository;
    protected BalanceLogRepository $balanceLogRepository;
    protected PointsLogRepository $pointsLogRepository;
    protected PointsDebtLogRepository $pointsDebtLogRepository;
    protected \app\repository\order\OrderOrderRepository $orderOrderRepository;
    protected ExcelExportService $excelExportService;
    protected UserTagRelationRepository $userTagRelationRepository;
    protected UserOperationLogRepository $userOperationLogRepository;
    protected MemberAddressRepository $memberAddressRepository;
    protected MemberRemarkRepository $memberRemarkRepository;
    protected MemberLevelRepository $memberLevelRepository;
    protected UserLoginLogRepository $userLoginLogRepository;
    protected UserOperationLogService $opLog;

    /**
     * 单用户聚合 KPI（会员详情顶部档案条用）
     *
     * @return array{gmv: float, orders: int, avg_amount: float, last_order_at: ?string, balance: float, points: int}
     */
    public function getUserStats(int $userId): array
    {
        $user = $this->userRepository->findModel($userId);
        if (!$user) {
            $this->throwBusinessException('用户不存在');
        }
        $stats = $this->orderOrderRepository->getStatsByUserId($userId);
        $stats['balance'] = (float)($user->balance ?? 0);
        $stats['points']  = (int)($user->points ?? 0);
        return $stats;
    }

    public function getUserList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->userRepository->getSearchList($params, $page, $limit);
    }

    public function getUserDetail(int $id): ?array
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return null;
        }

        // 附加 tags 数组（详情页右栏标签云用）
        $user['tags'] = $this->userTagRelationRepository->getTagsByUserId($id);

        // 附加 level_name（如有）
        $levelId = (int)($user['member_level_id'] ?? 0);
        if ($levelId > 0) {
            $level = $this->memberLevelRepository->find($levelId);
            $user['level_name'] = $level ? (string)($level['name'] ?? '') : '';
        } else {
            $user['level_name'] = '';
        }

        // 附加聚合计数
        $user['address_count'] = $this->memberAddressRepository->countByUserId($id);
        $user['remarks_count'] = $this->memberRemarkRepository->countByUserId($id);

        return $user;
    }

    public function updateProfile(int $id, array $data): bool
    {
        $user = $this->userRepository->findModel($id);
        if (!$user) {
            $this->throwBusinessException('用户不存在');
        }
        $allowed = ['nickname', 'mobile', 'email', 'gender', 'birthday', 'avatar'];
        $patch = array_intersect_key($data, array_flip($allowed));
        if (empty($patch)) {
            return true;
        }
        $ok = $this->userRepository->update($id, $patch);
        if ($ok) {
            $changed = array_keys($patch);
            $this->opLog->recordProfile(
                $id,
                '资料修改',
                '管理员修改字段：' . implode('、', $changed),
                ['fields' => $changed]
            );
        }
        return $ok;
    }

    public function updateStatus(int $id, int $status): bool
    {
        return $this->userRepository->update($id, ['status' => $status]);
    }

    public function adjustBalance(
        int $userId,
        float $amount,
        string $remark = '',
        int $type = BalanceLog::TYPE_ADMIN_ADJUST,
        string $source = 'admin_adjust',
        ?int $operatorId = null,
        ?string $eventKey = null
    ): bool {
        $eventKey = trim((string)$eventKey);
        $result = $this->runInTransaction(function () use ($userId, $amount, $type, $source, $remark, $operatorId, $eventKey) {
            $user = $this->userRepository->findForUpdate($userId);
            if (!$user) {
                $this->throwBusinessException('用户不存在');
            }

            if (
                ($eventKey !== '' && $this->balanceLogRepository->existsByEventKey($userId, $eventKey))
                || ($source !== '' && $source !== 'admin_adjust' && $this->balanceLogRepository->existsBySource($source))
            ) {
                return ['applied' => false];
            }

            $beforeBalance = (float) $user->balance;
            $afterBalance  = $beforeBalance + $amount;

            if ($afterBalance < 0) {
                $this->throwBusinessException('余额不足');
            }

            if (!$this->userRepository->updateBalance($userId, $afterBalance)) {
                $this->throwBusinessException('更新用户余额失败');
            }

            $this->balanceLogRepository->create([
                'user_id'        => $userId,
                'amount'         => $amount,
                'before_balance' => $beforeBalance,
                'after_balance'  => $afterBalance,
                'type'           => $type,
                'source'         => $source,
                'event_key'      => $eventKey !== '' ? $eventKey : null,
                'remark'         => $remark,
                'operator_id'    => $operatorId,
            ]);

            return ['applied' => true];
        });

        if ($result['applied']) {
            $this->trigger('user.balance.adjusted', [
                'user_id'   => $userId,
                'amount'    => $amount,
                'remark'    => $remark,
                'type_text' => \app\model\user\BalanceLog::TYPE_MAP[$type] ?? '',
                'source'    => $source,
                'event_key' => $eventKey,
            ]);
        }
        return (bool)$result['applied'];
    }

    public function adjustPoints(
        int $userId,
        int $points,
        string $remark = '',
        int $type = PointsLog::TYPE_ADMIN_ADJUST,
        string $source = 'admin_adjust',
        ?int $operatorId = null,
        ?string $eventKey = null
    ): bool {
        $eventKey = trim((string)$eventKey);
        $result = $this->runInTransaction(function () use ($userId, $points, $type, $source, $remark, $operatorId, $eventKey) {
            $user = $this->userRepository->findForUpdate($userId);
            if (!$user) {
                $this->throwBusinessException('用户不存在');
            }

            if (
                ($eventKey !== '' && $this->pointsLogRepository->existsByEventKey($userId, $eventKey))
                || ($source !== '' && $source !== 'admin_adjust'
                    && $this->pointsLogRepository->existsBySourceForUser($userId, $source))
            ) {
                return [
                    'applied' => false,
                    'actual_points' => 0,
                    'debt_paid' => 0,
                ];
            }

            $beforePoints = (int) $user->points;
            $beforeDebt = max(0, (int)($user->points_debt ?? 0));
            $debtPaid = 0;
            $actualPoints = $points;
            if ($points > 0 && $beforeDebt > 0) {
                $debtPaid = min($points, $beforeDebt);
                $actualPoints = $points - $debtPaid;
            }
            $afterDebt = $beforeDebt - $debtPaid;
            $afterPoints = $beforePoints + $actualPoints;

            if ($afterPoints < 0) {
                $this->throwBusinessException('积分不足');
            }

            $stateChanged = $afterPoints !== $beforePoints || $afterDebt !== $beforeDebt;
            if ($stateChanged && !$this->userRepository->updatePointsAndDebt(
                $userId,
                $afterPoints,
                $afterDebt,
                $actualPoints > 0 ? $actualPoints : 0
            )) {
                $this->throwBusinessException('更新用户积分失败');
            }

            if ($debtPaid > 0) {
                $this->pointsDebtLogRepository->create([
                    'user_id'     => $userId,
                    'delta'       => -$debtPaid,
                    'before_debt' => $beforeDebt,
                    'after_debt'  => $afterDebt,
                    'source'      => $source,
                    'event_key'   => $eventKey !== '' ? $eventKey . ':debt' : null,
                    'remark'      => $remark !== ''
                        ? $remark . '（优先抵扣积分债务）'
                        : '积分入账优先抵扣债务',
                ]);
            }

            // 全部用于抵债时仍写 0 入账流水，作为该正向事件的幂等 claim。
            $this->pointsLogRepository->create([
                'user_id'       => $userId,
                'points'        => $actualPoints,
                'before_points' => $beforePoints,
                'after_points'  => $afterPoints,
                'type'          => $type,
                'source'        => $source,
                'event_key'     => $eventKey !== '' ? $eventKey : null,
                'remark'        => $remark,
                'operator_id'   => $operatorId,
            ]);

            return [
                'applied' => true,
                'actual_points' => $actualPoints,
                'debt_paid' => $debtPaid,
            ];
        });

        if ($result['applied']) {
            $this->trigger('user.points.adjusted', [
                'user_id'   => $userId,
                'points'    => $result['actual_points'],
                'requested_points' => $points,
                'points_debt_paid' => $result['debt_paid'],
                'remark'    => $remark,
                'type_text' => \app\model\user\PointsLog::TYPE_MAP[$type] ?? '',
                'source'    => $source,
                'event_key' => $eventKey,
            ]);
        }
        return (bool)$result['applied'];
    }

    public function getBalanceLogs(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);

        $params = $this->resolveKeywordToUserIds($params);

        return $this->balanceLogRepository->getSearchList($params, $page, $limit);
    }

    public function getPointsLogs(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);

        $params = $this->resolveKeywordToUserIds($params);

        return $this->pointsLogRepository->getSearchList($params, $page, $limit);
    }

    public function getUserBalance(int $userId): array
    {
        $user = $this->userRepository->find($userId);
        return ['balance' => $user['balance'] ?? '0.00'];
    }

    public function getUserPoints(int $userId): array
    {
        $user = $this->userRepository->find($userId);
        return ['points' => $user['points'] ?? 0];
    }

    public function getUserBalanceLogs(int $userId, array $params): array
    {
        [$page, $limit] = $this->extractPagination($params, 10);
        return $this->balanceLogRepository->getUserLogs($userId, $page, $limit);
    }

    public function getUserPointsLogs(int $userId, array $params): array
    {
        [$page, $limit] = $this->extractPagination($params, 10);
        return $this->pointsLogRepository->getUserLogs($userId, $page, $limit);
    }

    /**
     * 导出余额流水 xlsx
     */
    public function exportBalanceLogs(array $params): \think\Response
    {
        $params = $this->resolveKeywordToUserIds($params);
        $rows = $this->balanceLogRepository->getAllForExport($params, ExcelExportService::MAX_ROWS);
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
            '余额流水_' . date('Ymd_His'),
            $headers,
            $data
        );
    }

    /**
     * 导出积分流水 xlsx
     */
    public function exportPointsLogs(array $params): \think\Response
    {
        $params = $this->resolveKeywordToUserIds($params);
        $rows = $this->pointsLogRepository->getAllForExport($params, ExcelExportService::MAX_ROWS);
        $headers = ['流水号', '会员', '变动类型', '变动积分', '变动后积分', '操作员', '备注', '时间'];
        $data = array_map(fn($r) => [
            $r['id'],
            $r['user_nickname'] ?? '-',
            (string)($r['type'] ?? ''),
            $r['points'] ?? 0,
            $r['after_points'] ?? 0,
            $r['operator_name'] ?? '-',
            $r['remark'] ?? '',
            $r['created_at'] ?? '',
        ], $rows);
        return $this->excelExportService->streamXlsx(
            '积分流水_' . date('Ymd_His'),
            $headers,
            $data
        );
    }

    /**
     * 把 params 中的 keyword 解析为 user_ids 过滤条件
     * 与 getBalanceLogs / getPointsLogs / export* 共用
     */
    private function resolveKeywordToUserIds(array $params): array
    {
        if (!empty($params['keyword'])) {
            $params['user_ids'] = $this->userRepository->searchIdsByKeyword($params['keyword']) ?: [0];
        }
        return $params;
    }

    /**
     * 单用户偏好聚合 + 收货地区分布
     */
    public function getUserPreference(int $userId): array
    {
        $base = $this->orderOrderRepository->getUserPreference($userId);
        $base['districts'] = $this->memberAddressRepository->districtDistribution($userId);
        return $base;
    }

    /**
     * 单用户生命周期推导（5-7 个阶段 + 下一阶段建议）
     */
    public function getUserLifecycle(int $userId): array
    {
        $user = $this->userRepository->findModel($userId);
        if (!$user) {
            $this->throwBusinessException('用户不存在');
        }

        $stages   = [];
        $registerAt = (string)($user->created_at ?? '');
        if ($registerAt) {
            $stages[] = ['key' => '注册', 'date' => substr($registerAt, 0, 10), 'desc' => '账户注册'];
        }
        $firstLogin = $this->userLoginLogRepository->findFirstLoginAt($userId);
        if ($firstLogin) {
            $stages[] = ['key' => '认知', 'date' => substr($firstLogin, 0, 10), 'desc' => '首次登录访问'];
        }

        $anchors = $this->orderOrderRepository->getUserLifecycleAnchors($userId);
        if ($anchors['first']) {
            $stages[] = ['key' => '首单', 'date' => substr($anchors['first'], 0, 10), 'desc' => '首次下单'];
        }
        if ($anchors['completed_count'] >= 2 && $anchors['last'] && $anchors['last'] !== $anchors['first']) {
            $stages[] = ['key' => '复购', 'date' => substr($anchors['last'], 0, 10), 'desc' => '累计已完成 ' . $anchors['completed_count'] . ' 单'];
        }

        $levelId = (int)($user->member_level_id ?? 0);
        if ($levelId > 0) {
            $level = $this->memberLevelRepository->find($levelId);
            if ($level) {
                $stages[] = ['key' => '等级', 'date' => substr((string)($user->updated_at ?? ''), 0, 10), 'desc' => '当前等级：' . (string)($level['name'] ?? '')];
            }
        }

        // 沉睡判断：最后下单 ≥ 90 天
        if ($anchors['last'] && (time() - strtotime($anchors['last'])) > 86400 * 90) {
            $stages[] = ['key' => '沉睡', 'date' => substr($anchors['last'], 0, 10), 'desc' => '近 90 天无下单'];
        }

        // 下一阶段预测
        $next = $this->predictNextStage($anchors);

        return [
            'stages' => $stages,
            'next'   => $next,
        ];
    }

    /**
     * 简化的下一阶段建议
     */
    private function predictNextStage(array $anchors): array
    {
        $orderCount = $anchors['completed_count'];
        $last       = $anchors['last'];

        if (!$last) {
            return ['title' => '引导首单', 'desc' => '该用户尚未下单，建议推送新人优惠'];
        }
        $daysSinceLast = (int)floor((time() - strtotime($last)) / 86400);

        if ($daysSinceLast > 90) {
            return ['title' => '召回沉睡', 'desc' => '已超 ' . $daysSinceLast . ' 天未下单，建议推送召回券'];
        }
        if ($orderCount >= 5) {
            return ['title' => '高价值复购', 'desc' => '建议推送新品 / 专属客户经理'];
        }
        return ['title' => '促进复购', 'desc' => '建议推送相关品类优惠券'];
    }

    /**
     * 单用户操作日志（分类计数 + 分页列表）
     */
    public function getOperationLogs(int $userId, ?string $category, int $page, int $limit): array
    {
        $list = $this->userOperationLogRepository->getUserLogs($userId, $category, $page, $limit);
        return [
            'categories' => $this->userOperationLogRepository->countByCategory($userId),
            'list'       => $list['list'],
            'pagination' => $list['pagination'],
        ];
    }
}
