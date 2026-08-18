<?php
declare(strict_types=1);

namespace plugins\sign\repository;

use plugins\sign\model\MemberSignLog;
use core\base\Repository;
use think\Model as ThinkModel;

class MemberSignLogRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new MemberSignLog();
    }

    /**
     * 分页查询签到日志，关联 user 显示昵称/头像
     */
    public function getPageList(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = $this->model
            ->with([
                'user' => function ($q) {
                    $q->field('id, nickname, avatar, mobile');
                },
            ])
            ->order('sign_date', 'desc')
            ->order('id', 'desc');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['sign_date_start'])) {
            $query->where('sign_date', '>=', $filters['sign_date_start']);
        }
        if (!empty($filters['sign_date_end'])) {
            $query->where('sign_date', '<=', $filters['sign_date_end']);
        }
        if (isset($filters['is_makeup']) && $filters['is_makeup'] !== '') {
            $query->where('is_makeup', (int)$filters['is_makeup']);
        }
        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        foreach ($list as &$row) {
            $row['user_nickname'] = $row['user']['nickname'] ?? '-';
            $row['user_avatar']   = $row['user']['avatar'] ?? '';
            $row['user_mobile']   = $row['user']['mobile'] ?? '';
            unset($row['user']);
        }
        unset($row);

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 查指定用户某天的签到记录
     */
    public function findByDate(int $userId, string $date): ?MemberSignLog
    {
        return $this->model->where('user_id', $userId)
            ->where('sign_date', $date)
            ->find();
    }

    /**
     * 是否在指定日期已签到（含补签）
     */
    public function existsAt(int $userId, string $date): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('sign_date', $date)
            ->count() > 0;
    }

    /**
     * 按日期查找当日签到记录（返数组）
     */
    public function findByDateArray(int $userId, string $date): ?array
    {
        $row = $this->model->where('user_id', $userId)
            ->where('sign_date', $date)
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * 用户某月（YYYY-MM 前缀）的全部签到日志
     */
    public function findByMonth(int $userId, string $monthPrefix): array
    {
        return $this->model->where('user_id', $userId)
            ->where('sign_date', 'like', $monthPrefix . '%')
            ->order('sign_date', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 用户最近一条签到日志（按 sign_date 倒序）
     */
    public function findLatestByUser(int $userId): ?array
    {
        $row = $this->model->where('user_id', $userId)
            ->order('sign_date', 'desc')
            ->find();
        return $row ? $row->toArray() : null;
    }

    /**
     * KPI 统计
     */
    public function getStats(): array
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $todayCount = $this->model->where('sign_date', $today)->count();

        $monthCount = $this->model
            ->where('sign_date', '>=', $monthStart)
            ->where('sign_date', '<=', $monthEnd)
            ->count();

        $monthPoints = (int) $this->model
            ->where('sign_date', '>=', $monthStart)
            ->where('sign_date', '<=', $monthEnd)
            ->sum('points_awarded');

        // 连续 7 天用户：取每个 user 的最新一条 sign 记录，continuous_days >= 7
        // 且最新签到日 ∈ {今天, 昨天}（否则 streak 已断）
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $subQuery = $this->model
            ->field('user_id, MAX(sign_date) AS last_date')
            ->group('user_id')
            ->buildSql();

        $continuous7 = (int) \think\facade\Db::table($subQuery . ' t')
            ->join('member_sign_logs l', 'l.user_id = t.user_id AND l.sign_date = t.last_date')
            ->whereIn('t.last_date', [$today, $yesterday])
            ->where('l.continuous_days', '>=', 7)
            ->count();

        return [
            'today_count'         => $todayCount,
            'continuous_7_users'  => $continuous7,
            'month_count'         => $monthCount,
            'month_points'        => $monthPoints,
        ];
    }
}
