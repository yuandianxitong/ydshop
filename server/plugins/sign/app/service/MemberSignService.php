<?php
declare(strict_types=1);

namespace plugins\sign\service;

use app\model\user\BalanceLog;
use app\model\user\PointsLog;
use plugins\sign\repository\MemberSignLogRepository;
use app\repository\system\SystemConfigRepository;
use app\service\user\UserManageService;
use app\repository\user\UserRepository;
use core\base\Service;

class MemberSignService extends Service
{
    protected UserManageService $userManageService;
    protected MemberSignLogRepository $signLogRepository;
    protected SystemConfigRepository $systemConfigRepository;
    protected UserRepository $userRepository;

    /**
     * 用户签到
     */
    public function checkin(int $userId, string $source = 'unknown'): array
    {
        $today = date('Y-m-d');
        $base        = (int) $this->systemConfigRepository->getConfigValue('sign.points_base', 1);
        $increment   = (int) $this->systemConfigRepository->getConfigValue('sign.points_increment', 1);
        $max         = (int) $this->systemConfigRepository->getConfigValue('sign.points_max', 7);
        $bonusDays   = (int) $this->systemConfigRepository->getConfigValue('sign.continuous_bonus_days', 7);
        $bonusPoints = (int) $this->systemConfigRepository->getConfigValue('sign.continuous_bonus_points', 10);

        return $this->runInTransaction(function () use (
            $userId,
            $source,
            $today,
            $base,
            $increment,
            $max,
            $bonusDays,
            $bonusPoints
        ): array {
            // 先锁用户行，序列化同一用户的普通签到、补签和积分变更；随后
            // 签到唯一记录与积分流水处于同一事务，任一步失败都会一起回滚。
            if (!$this->userRepository->findForUpdate($userId)) {
                $this->throwBusinessException('用户不存在');
            }
            if ($this->signLogRepository->findByDateArray($userId, $today) !== null) {
                $this->throwBusinessException('今日已签到');
            }

            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $lastLog = $this->signLogRepository->findByDateArray($userId, $yesterday);
            $continuousDays = $lastLog ? (int)$lastLog['continuous_days'] + 1 : 1;
            $points = $this->calcPoints(
                $continuousDays,
                $base,
                $increment,
                $max,
                $bonusDays,
                $bonusPoints
            );

            $this->signLogRepository->create([
                'user_id'         => $userId,
                'sign_date'       => $today,
                'continuous_days' => $continuousDays,
                'points_awarded'  => $points,
                'is_makeup'       => 0,
                'source'          => $source,
            ]);

            if ($points > 0) {
                $eventKey = sprintf('sign:%d:%s', $userId, $today);
                $this->userManageService->adjustPoints(
                    $userId,
                    $points,
                    "签到奖励（{$today}）",
                    PointsLog::TYPE_SIGN_IN,
                    $eventKey,
                    null,
                    $eventKey
                );
            }

            return [
                'sign_date'       => $today,
                'continuous_days' => $continuousDays,
                'points_awarded'  => $points,
            ];
        });
    }

    /**
     * 获取签到日历
     */
    public function getCalendar(int $userId, string $month): array
    {
        $logs        = $this->signLogRepository->findByMonth($userId, $month);
        $signedDates = array_column($logs, 'sign_date');

        $today       = date('Y-m-d');
        $todaySigned = in_array($today, $signedDates, true);

        // 获取当前连续天数（取最新一条签到记录）
        $latestLog = $this->signLogRepository->findLatestByUser($userId);

        // 如果最新签到不是今天或昨天，则连续天数重置为 0
        $continuousDays = 0;
        if ($latestLog) {
            $latestDate = (string)$latestLog['sign_date'];
            if ($latestDate === $today || $latestDate === date('Y-m-d', strtotime('-1 day'))) {
                $continuousDays = (int)$latestLog['continuous_days'];
            }
        }

        // 预览今日可获积分（未签到时计算下一次签到的积分）
        $base        = (int) $this->systemConfigRepository->getConfigValue('sign.points_base', 1);
        $increment   = (int) $this->systemConfigRepository->getConfigValue('sign.points_increment', 1);
        $max         = (int) $this->systemConfigRepository->getConfigValue('sign.points_max', 7);
        $bonusDays   = (int) $this->systemConfigRepository->getConfigValue('sign.continuous_bonus_days', 7);
        $bonusPoints = (int) $this->systemConfigRepository->getConfigValue('sign.continuous_bonus_points', 10);

        if ($todaySigned) {
            $todayLog = null;
            foreach ($logs as $log) {
                if (($log['sign_date'] ?? '') === $today) {
                    $todayLog = $log;
                    break;
                }
            }
            $todayPoints = $todayLog ? (int)$todayLog['points_awarded'] : 0;
        } else {
            $nextContinuousDays = $continuousDays + 1;
            $todayPoints = $this->calcPoints($nextContinuousDays, $base, $increment, $max, $bonusDays, $bonusPoints);
        }

        return [
            'signed_dates'    => $signedDates,
            'continuous_days' => $continuousDays,
            'today_signed'    => $todaySigned,
            'today_points'    => $todayPoints,
        ];
    }

    /**
     * 获取签到配置
     */
    public function getConfig(): array
    {
        return [
            'sign.points_base'             => (int)$this->systemConfigRepository->getConfigValue('sign.points_base', 1),
            'sign.points_increment'        => (int)$this->systemConfigRepository->getConfigValue('sign.points_increment', 1),
            'sign.points_max'              => (int)$this->systemConfigRepository->getConfigValue('sign.points_max', 7),
            'sign.continuous_bonus_days'   => (int)$this->systemConfigRepository->getConfigValue('sign.continuous_bonus_days', 7),
            'sign.continuous_bonus_points' => (int)$this->systemConfigRepository->getConfigValue('sign.continuous_bonus_points', 10),
        ];
    }

    /**
     * 根据连续签到天数计算积分
     */
    private function calcPoints(
        int $continuousDays,
        int $base,
        int $increment,
        int $max,
        int $bonusDays,
        int $bonusPoints
    ): int {
        // 在一个周期（bonusDays）内的第几天，1-indexed
        $dayInCycle = (($continuousDays - 1) % $bonusDays) + 1;
        $points = min($base + ($dayInCycle - 1) * $increment, $max);

        // 恰好完成整个周期时额外奖励
        if ($continuousDays > 0 && $continuousDays % $bonusDays === 0) {
            $points += $bonusPoints;
        }

        return $points;
    }

    /**
     * 后台 KPI 统计
     */
    public function getStats(): array
    {
        return $this->signLogRepository->getStats();
    }

    /**
     * 后台分页列表
     */
    public function getLogs(array $filters, int $page, int $limit): array
    {
        return $this->signLogRepository->getPageList($filters, $page, $limit);
    }

    /**
     * 用户补签
     */
    public function makeup(int $userId, string $date, string $source = 'unknown'): array
    {
        // 1. 校验补签开关
        $enabled = $this->systemConfigRepository->getConfigValue('sign.makeup_enabled', '0');
        if (!$enabled || $enabled === '0') {
            $this->throwBusinessException('补签功能未开放');
        }

        // 2. 校验日期范围
        $today = date('Y-m-d');
        if ($date > $today) {
            $this->throwBusinessException('不可补签未来日期');
        }
        if ($date === $today) {
            $this->throwBusinessException('今日请直接签到');
        }

        $limit    = (int)$this->systemConfigRepository->getConfigValue('sign.makeup_days_limit', 7);
        $earliest = date('Y-m-d', strtotime("-{$limit} day"));
        if ($date < $earliest) {
            $this->throwBusinessException("仅可补 {$limit} 天内的签到");
        }

        // 3. 当日未签
        if ($this->signLogRepository->findByDateArray($userId, $date) !== null) {
            $this->throwBusinessException('该日已签到');
        }

        // 4. 计算补签日的 continuous_days：查前一天是否已签
        $prevDate = date('Y-m-d', strtotime("{$date} -1 day"));
        $prevLog  = $this->signLogRepository->findByDateArray($userId, $prevDate);
        $continuousDays = $prevLog ? (int)$prevLog['continuous_days'] + 1 : 1;

        // 5. 读取消耗配置
        $currency = (string)$this->systemConfigRepository->getConfigValue('sign.makeup_currency', 'points');
        $price    = (int)$this->systemConfigRepository->getConfigValue('sign.makeup_price', 5);

        // 6. 事务：扣费 + 写记录
        $this->runInTransaction(function () use ($userId, $date, $source, $currency, $price, $continuousDays) {
            $sourceTag = 'sign:makeup:' . $date;
            if ($currency === 'balance') {
                $this->userManageService->adjustBalance(
                    $userId,
                    -$price,
                    "补签 {$date}",
                    BalanceLog::TYPE_ADMIN_ADJUST,
                    $sourceTag
                );
            } else {
                $this->userManageService->adjustPoints(
                    $userId,
                    -$price,
                    "补签 {$date}",
                    PointsLog::TYPE_ADMIN_ADJUST,
                    $sourceTag
                );
            }

            $this->signLogRepository->create([
                'user_id'         => $userId,
                'sign_date'       => $date,
                'continuous_days' => $continuousDays,
                'points_awarded'  => 0,
                'is_makeup'       => 1,
                'source'          => $source,
            ]);
        });

        return [
            'sign_date'       => $date,
            'continuous_days' => $continuousDays,
            'cost'            => $price,
            'currency'        => $currency,
        ];
    }
}
