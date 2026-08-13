<?php
declare(strict_types=1);

namespace app\repository\user;

use core\base\Repository;

/**
 * 账目/积分日志 Repository 抽象基类
 *
 * 封装了 BalanceLog / PointsLog 等所有"账目流水"类型表的通用查询逻辑：
 *  - 按 user_id / type / user_ids / 日期范围 过滤
 *  - eager loading user 和 operator 昵称
 *  - 分页后统一拍平 nickname 字段
 *
 * 子类只需实现 `getModel()` 指向具体的 Model 即可。
 */
abstract class AbstractLedgerLogRepository extends Repository
{
    public function existsBySource(string $source): bool
    {
        return $source !== '' && $this->model->where('source', $source)->count() > 0;
    }

    public function existsBySourceForUser(int $userId, string $source): bool
    {
        return $source !== '' && $this->model
            ->where('user_id', $userId)
            ->where('source', $source)
            ->count() > 0;
    }

    public function existsByEventKey(int $userId, string $eventKey): bool
    {
        return $eventKey !== '' && $this->model
            ->where('user_id', $userId)
            ->where('event_key', $eventKey)
            ->count() > 0;
    }

    /**
     * 读取并锁定一组历史 source，供升级补偿验证和接管旧 event_key=NULL 流水。
     * 查询不预先限制 user_id，才能发现同一业务 source 被错误写给其他用户。
     *
     * @param string[] $sources
     * @return array<int, array<string, mixed>>
     */
    public function findBySourcesForUpdate(array $sources): array
    {
        $sources = array_values(array_unique(array_filter(array_map('trim', $sources))));
        if ($sources === []) {
            return [];
        }
        return $this->model
            ->whereIn('source', $sources)
            ->order('id', 'asc')
            ->lock(true)
            ->select()
            ->toArray();
    }

    /** 原子为唯一可信历史流水补上事件键。 */
    public function claimEventKeyIfEmpty(int $id, int $userId, string $eventKey): int
    {
        return $this->model
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('event_key')
            ->update(['event_key' => $eventKey]);
    }

    /**
     * 搜索列表（管理后台用）
     */
    public function getSearchList(array $params, int $page = 1, int $limit = 20): array
    {
        $query = $this->baseQuery();

        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (!empty($params['user_ids'])) {
            $query->whereIn('user_id', $params['user_ids']);
        }

        $startTime = $params['start_time'] ?? $params['start_date'] ?? null;
        $endTime   = $params['end_time']   ?? $params['end_date']   ?? null;
        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }
        if ($endTime) {
            $query->where('created_at', '<=', $endTime . ' 23:59:59');
        }

        $total = $query->count();
        $list  = $this->flattenNicknames(
            $query->page($page, $limit)->select()->toArray()
        );

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 获取指定用户的流水（前台用）
     */
    public function getUserLogs(int $userId, int $page = 1, int $limit = 10): array
    {
        $query = $this->baseQuery()->where('user_id', $userId);

        $total = $query->count();
        $list  = $this->flattenNicknames(
            $query->page($page, $limit)->select()->toArray()
        );

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 导出所有匹配的流水（不分页，受 maxRows 限制）
     *
     * 复用 baseQuery + 同样的过滤参数（user_id / type / user_ids / 日期范围）。
     * 实际取 maxRows + 1 行，超过 maxRows 时返回 maxRows + 1 条让 Service 层抛错。
     */
    public function getAllForExport(array $params, int $maxRows): array
    {
        $query = $this->baseQuery();

        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (!empty($params['user_ids'])) {
            $query->whereIn('user_id', $params['user_ids']);
        }

        $startTime = $params['start_time'] ?? $params['start_date'] ?? null;
        $endTime   = $params['end_time']   ?? $params['end_date']   ?? null;
        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }
        if ($endTime) {
            $query->where('created_at', '<=', $endTime . ' 23:59:59');
        }

        $rows = $query->limit($maxRows + 1)->select()->toArray();
        return $this->flattenNicknames($rows);
    }

    /**
     * 构造带 user / operator eager loading 的基础查询
     */
    protected function baseQuery()
    {
        return $this->model->with([
            'user' => function ($q) {
                $q->field('id, nickname, avatar, mobile');
            },
            'operator' => function ($q) {
                $q->field('id, nickname');
            },
        ])->order('id', 'desc');
    }

    /**
     * 将 user/operator 关联对象拍平为 user_nickname / user_avatar / user_mobile / operator_name 字段
     */
    protected function flattenNicknames(array $list): array
    {
        foreach ($list as &$item) {
            $item['user_nickname'] = $item['user']['nickname'] ?? '-';
            $item['user_avatar']   = $item['user']['avatar'] ?? '';
            $item['user_mobile']   = $item['user']['mobile'] ?? '';
            $item['operator_name'] = $item['operator']['nickname'] ?? '-';
            unset($item['user'], $item['operator']);
        }
        return $list;
    }
}
