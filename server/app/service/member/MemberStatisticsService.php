<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberStatisticsRepository;
use core\base\Service;

class MemberStatisticsService extends Service
{
    protected MemberStatisticsRepository $memberStatisticsRepository;

    public function overview(): array
    {
        return [
            'total'     => $this->memberStatisticsRepository->countTotal(),
            'today'     => $this->memberStatisticsRepository->countRegistersByDay(date('Y-m-d')),
            'yesterday' => $this->memberStatisticsRepository->countRegistersByDay(date('Y-m-d', strtotime('-1 day'))),
            'month'     => $this->memberStatisticsRepository->countRegistersByMonth(date('Y-m')),
        ];
    }

    public function registerTrend(int $days): array
    {
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = [
                'date'  => $date,
                'count' => $this->memberStatisticsRepository->countRegistersByDay($date),
            ];
        }
        return $result;
    }

    public function activeAnalysis(): array
    {
        $thresholds = [
            'day'   => date('Y-m-d 00:00:00'),
            'week'  => date('Y-m-d 00:00:00', strtotime('-6 days')),
            'month' => date('Y-m-d 00:00:00', strtotime('-29 days')),
        ];
        $labels = [
            'day'   => '今日活跃',
            'week'  => '近7天活跃',
            'month' => '近30天活跃',
        ];
        $data = [];
        foreach ($thresholds as $key => $since) {
            $data[] = [
                'type'  => $key,
                'label' => $labels[$key],
                'count' => $this->memberStatisticsRepository->countActiveSince($since),
            ];
        }
        return $data;
    }

    public function levelDistribution(): array
    {
        $data = [];
        $noLevel = $this->memberStatisticsRepository->countNoLevelUsers();
        if ($noLevel > 0) {
            $data[] = ['level_name' => '普通用户', 'count' => $noLevel];
        }
        foreach ($this->memberStatisticsRepository->listLevels() as $level) {
            $data[] = [
                'level_name' => $level['name'],
                'count'      => $this->memberStatisticsRepository->countByLevel((int)$level['id']),
            ];
        }
        return $data;
    }

    public function consumeDistribution(): array
    {
        $ranges = [
            ['label' => '未消费',     'min' => null, 'max' => 0],
            ['label' => '1-100元',    'min' => 1,    'max' => 100],
            ['label' => '100-500元',  'min' => 100,  'max' => 500],
            ['label' => '500-1000元', 'min' => 500,  'max' => 1000],
            ['label' => '1000元以上', 'min' => 1000, 'max' => null],
        ];
        $data = [];
        foreach ($ranges as $range) {
            $data[] = [
                'range' => $range['label'],
                'count' => $this->memberStatisticsRepository->countConsumeRange(
                    $range['min'] === null ? null : (float)$range['min'],
                    $range['max'] === null ? null : (float)$range['max']
                ),
            ];
        }
        return $data;
    }

    public function sourceDistribution(): array
    {
        $rows = $this->memberStatisticsRepository->countByAuthPlatform();
        $labels = [
            'wechat_mp'  => '微信小程序',
            'wechat_oa'  => '微信公众号',
            'wechat_web' => '微信扫码',
            'phone'      => '手机注册',
            'alipay'     => '支付宝',
            'douyin'     => '抖音',
        ];
        $total = array_sum(array_map(fn($r) => (int)$r['count'], $rows));
        return array_map(fn($r) => [
            'platform' => $r['platform'],
            'label'    => $labels[$r['platform']] ?? $r['platform'],
            'count'    => (int)$r['count'],
            'pct'      => $total > 0 ? round((int)$r['count'] / $total * 1000) / 10 : 0,
        ], $rows);
    }

    public function retentionMatrix(int $cohorts = 8): array
    {
        $now           = strtotime(date('Y-m-d'));
        $weekday       = (int)date('w', $now); // 0=Sun..6=Sat
        $daysSinceMon  = $weekday === 0 ? 6 : $weekday - 1;
        $thisMondayTs  = strtotime("-{$daysSinceMon} days", $now);

        $rows = [];
        for ($i = $cohorts - 1; $i >= 0; $i--) {
            $cohortStartTs = strtotime("-{$i} weeks", $thisMondayTs);
            $cohortStart   = date('Y-m-d 00:00:00', $cohortStartTs);
            $cohortEnd     = date('Y-m-d 23:59:59', strtotime('+6 days', $cohortStartTs));

            $news    = $this->memberStatisticsRepository->countCohortRegistrations($cohortStart, $cohortEnd);
            $userIds = $news > 0
                ? $this->memberStatisticsRepository->fetchUserIdsByCohort($cohortStart, $cohortEnd)
                : [];

            $cells = [];
            foreach ([1, 3, 7, 14, 30] as $dn) {
                $windowStartTs = $cohortStartTs + $dn * 86400;
                $windowEndTs   = $windowStartTs + 86400;
                if (time() < $windowEndTs) {
                    $cells["d{$dn}"] = null;
                    continue;
                }
                if (empty($userIds)) {
                    $cells["d{$dn}"] = 0;
                    continue;
                }
                $retained = $this->memberStatisticsRepository->countDistinctLoginsInWindow(
                    $userIds,
                    date('Y-m-d H:i:s', $windowStartTs),
                    date('Y-m-d H:i:s', $windowEndTs)
                );
                $cells["d{$dn}"] = $news > 0 ? (int)round($retained / $news * 100) : 0;
            }

            $rows[] = [
                'week'  => date('m-d', $cohortStartTs),
                'news'  => $news,
                'cells' => $cells,
            ];
        }
        return $rows;
    }
}
