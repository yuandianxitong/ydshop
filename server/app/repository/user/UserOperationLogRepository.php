<?php
declare(strict_types=1);

namespace app\repository\user;

use app\model\user\UserOperationLog;
use core\base\Repository;
use think\Model as ThinkModel;

class UserOperationLogRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new UserOperationLog();
    }

    /**
     * 写一条操作日志（容错：写失败仅记日志，不抛异常）
     */
    public function record(int $userId, string $category, array $data): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $eventKey = trim((string)($data['event_key'] ?? ''));
        try {
            $this->insertLog([
                'user_id'     => $userId,
                'category'    => in_array($category, UserOperationLog::CATEGORIES, true) ? $category : 'other',
                'event_key'   => $eventKey !== '' ? $eventKey : null,
                'event_code'  => (string)($data['event_code'] ?? ''),
                'title'       => (string)($data['title'] ?? ''),
                'description' => (string)($data['description'] ?? ''),
                'icon'        => (string)($data['icon'] ?? ''),
                'tone'        => (string)($data['tone'] ?? ''),
                'ref_type'    => (string)($data['ref_type'] ?? ''),
                'ref_id'      => isset($data['ref_id']) ? (int)$data['ref_id'] : null,
                'meta'        => $data['meta'] ?? null,
                'created_at'  => $data['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Throwable $e) {
            // 先 INSERT、由唯一键裁决并发；命中同一 event_key 即为合法重放。
            if ($eventKey !== '' && $this->eventKeyExists($eventKey)) {
                return false;
            }
            \think\facade\Log::warning('UserOperationLog record failed', [
                'user_id'  => $userId,
                'category' => $category,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    /** 测试可替换边界；生产环境由数据库唯一键裁决并发。 */
    protected function insertLog(array $data): void
    {
        $this->model->create($data);
    }

    protected function eventKeyExists(string $eventKey): bool
    {
        return $this->model->where('event_key', $eventKey)->find() !== null;
    }

    /**
     * 单用户日志列表（按分类过滤、分页）
     */
    public function getUserLogs(int $userId, ?string $category, int $page, int $limit): array
    {
        $query = UserOperationLog::where('user_id', $userId)->order('id', 'desc');
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }
        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();
        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 单用户分类计数（左侧侧栏 ALL/login/asset/...）
     *
     * @return array<string, int>  含 all 键
     */
    public function countByCategory(int $userId): array
    {
        $rows = UserOperationLog::where('user_id', $userId)
            ->field(['category', 'COUNT(*) AS cnt'])
            ->group('category')
            ->select()
            ->toArray();

        $out = ['all' => 0];
        foreach (UserOperationLog::CATEGORIES as $c) {
            $out[$c] = 0;
        }
        foreach ($rows as $r) {
            $cat = (string)$r['category'];
            $cnt = (int)$r['cnt'];
            $out[$cat] = $cnt;
            $out['all'] += $cnt;
        }
        return $out;
    }
}
