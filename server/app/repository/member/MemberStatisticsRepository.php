<?php
declare(strict_types=1);

namespace app\repository\member;

use app\model\member\MemberLevel;
use app\model\user\User;
use app\model\user\UserLoginLog;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberStatisticsRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new User();
    }

    // ─── overview ───────────────────────────────────────────
    public function countTotal(): int
    {
        return User::count();
    }

    public function countRegistersByDay(string $date): int
    {
        return User::whereDay('created_at', $date)->count();
    }

    public function countRegistersByMonth(string $yearMonth): int
    {
        return User::whereMonth('created_at', $yearMonth)->count();
    }

    // ─── 活跃 ───────────────────────────────────────────────
    public function countActiveSince(string $sinceTimestamp): int
    {
        return User::where('last_login_time', '>=', $sinceTimestamp)->count();
    }

    // ─── 等级分布 ───────────────────────────────────────────
    public function countNoLevelUsers(): int
    {
        return User::where(function ($q) {
            $q->where('member_level_id', 0)->whereOr('member_level_id', null);
        })->count();
    }

    public function listLevels(): array
    {
        return MemberLevel::field('id, name')->select()->toArray();
    }

    public function countByLevel(int $levelId): int
    {
        return User::where('member_level_id', $levelId)->count();
    }

    // ─── 消费分布 ───────────────────────────────────────────
    public function countConsumeRange(?float $min, ?float $max): int
    {
        $query = User::newQuery();
        if ($min === null) {
            $query->where('total_consume', '<=', 0);
        } elseif ($max === null) {
            $query->where('total_consume', '>', $min);
        } else {
            $query->where('total_consume', '>=', $min)
                ->where('total_consume', '<', $max);
        }
        return $query->count();
    }

    /**
     * 注册来源分布（按 users 身份字段推断；user_auths 当前无写入路径）
     *
     * 优先级：小程序 > 公众号 > 开放平台/扫码 > 手机号
     *
     * @return list<array{platform: string, count: int}>
     */
    public function countByAuthPlatform(): array
    {
        $rows = User::fieldRaw(
            "CASE
                WHEN mini_openid IS NOT NULL AND mini_openid <> '' THEN 'wechat_mp'
                WHEN oa_openid IS NOT NULL AND oa_openid <> '' THEN 'wechat_oa'
                WHEN openid IS NOT NULL AND openid <> '' THEN 'wechat_web'
                WHEN mobile IS NOT NULL AND mobile <> '' THEN 'phone'
                ELSE 'other'
            END AS platform, COUNT(*) AS count"
        )
            ->group('platform')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $platform = (string)($row['platform'] ?? '');
            if ($platform === '' || $platform === 'other') {
                continue;
            }
            $result[] = [
                'platform' => $platform,
                'count'    => (int)($row['count'] ?? 0),
            ];
        }

        usort($result, static fn ($a, $b) => $b['count'] <=> $a['count']);

        return $result;
    }

    // ─── 留存矩阵原始数据 ───────────────────────────────────
    public function countCohortRegistrations(string $cohortStart, string $cohortEnd): int
    {
        return User::where('created_at', '>=', $cohortStart)
            ->where('created_at', '<=', $cohortEnd)
            ->count();
    }

    public function fetchUserIdsByCohort(string $cohortStart, string $cohortEnd): array
    {
        return User::where('created_at', '>=', $cohortStart)
            ->where('created_at', '<=', $cohortEnd)
            ->column('id');
    }

    public function countDistinctLoginsInWindow(array $userIds, string $startAt, string $endAt): int
    {
        if (empty($userIds)) {
            return 0;
        }
        return UserLoginLog::whereIn('user_id', $userIds)
            ->where('login_at', '>=', $startAt)
            ->where('login_at', '<', $endAt)
            ->distinct(true)
            ->count('user_id');
    }
}
