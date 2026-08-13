<?php
declare(strict_types=1);

namespace app\repository\notification;

use app\model\notification\UserNotification;
use core\base\Repository;
use core\exception\BusinessException;
use think\facade\Db;
use think\Model;

class UserNotificationRepository extends Repository
{
    protected function getModel(): Model
    {
        return new UserNotification();
    }

    /**
     * 按用户 + 领域事件键幂等创建通知。
     *
     * 唯一索引负责并发裁决；并发请求落败后回读赢家，避免事件重放制造重复消息。
     */
    public function createOnce(array $data): array
    {
        $eventKey = trim((string) ($data['event_key'] ?? ''));
        if ($eventKey === '') {
            return $this->create($data);
        }

        $where = [
            ['user_id', '=', (int) ($data['user_id'] ?? 0)],
            ['event_key', '=', $eventKey],
        ];
        $existing = $this->findWhere($where);
        if ($existing) {
            return $existing;
        }

        try {
            return $this->create($data);
        } catch (BusinessException $exception) {
            $existing = $this->findWhere($where);
            if ($existing) {
                return $existing;
            }
            throw $exception;
        }
    }

    /**
     * 获取用户消息列表（user_id = 指定用户 OR user_id = 0 广播）
     */
    public function getUserMessages(int $userId, int $page, int $limit): array
    {
        $query = $this->accessibleQuery($userId);
        $total = (clone $query)->count();
        $list = $query->order('created_at desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 只查询当前页的已读记录，避免消息量增长后每页加载用户全部已读 ID。
        $pageIds = array_map('intval', array_column($list, 'id'));
        $readIds = empty($pageIds) ? [] : Db::name('user_notification_reads')
            ->where('user_id', $userId)
            ->whereIn('notification_id', $pageIds)
            ->whereNotNull('read_at')
            ->column('notification_id');
        $readMap = array_fill_keys(array_map('intval', $readIds), true);

        // 标记已读状态
        foreach ($list as &$item) {
            $item['is_read'] = isset($readMap[(int) $item['id']]);
        }
        unset($item);

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 获取当前用户可见的单条通知，并附带已读状态。
     */
    public function findAccessible(int $notificationId, int $userId): ?array
    {
        $notification = $this->accessibleQuery($userId)
            ->where('id', $notificationId)
            ->find();
        if (!$notification) {
            return null;
        }

        $result = $notification->toArray();
        $result['is_read'] = Db::name('user_notification_reads')
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->count() > 0;

        return $result;
    }

    /**
     * 获取未读消息数量
     */
    public function getUnreadCount(int $userId): int
    {
        $readIds = Db::name('user_notification_reads')
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->column('notification_id');

        $query = $this->accessibleQuery($userId);

        if (!empty($readIds)) {
            $query->whereNotIn('id', $readIds);
        }

        return $query->count();
    }

    /**
     * 标记指定通知为已读（批量操作，避免 N+1）
     */
    public function markAsRead(int $userId, array $notificationIds): void
    {
        if (empty($notificationIds)) {
            return;
        }

        $notificationIds = array_values(array_unique(array_map('intval', $notificationIds)));

        // 只允许写入当前用户可见的定向通知或全员广播，防止构造 ID
        // 给其他用户的通知制造已读痕迹。
        $notificationIds = $this->accessibleQuery($userId)
            ->whereIn('id', $notificationIds)
            ->column('id');
        $notificationIds = array_map('intval', $notificationIds);
        if (empty($notificationIds)) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        // 一次查询获取已有记录
        $existingRows = Db::name('user_notification_reads')
            ->where('user_id', $userId)
            ->whereIn('notification_id', $notificationIds)
            ->column('notification_id,read_at', 'notification_id');

        // 批量更新已有但未读的记录
        $needUpdateIds = [];
        foreach ($existingRows as $nid => $row) {
            if (empty($row['read_at'])) {
                $needUpdateIds[] = $nid;
            }
        }
        if (!empty($needUpdateIds)) {
            Db::name('user_notification_reads')
                ->where('user_id', $userId)
                ->whereIn('notification_id', $needUpdateIds)
                ->update(['read_at' => $now]);
        }

        // 批量插入不存在的记录
        $existingNids = array_keys($existingRows);
        $newNids = array_diff($notificationIds, $existingNids);
        if (!empty($newNids)) {
            $insertData = [];
            foreach ($newNids as $nid) {
                $insertData[] = [
                    'notification_id' => $nid,
                    'user_id'         => $userId,
                    'read_at'         => $now,
                    'created_at'      => $now,
                ];
            }
            // 唯一索引兜底并发请求；另一请求先插入时本次安全忽略。
            /** @var \think\db\Query $insertQuery */
            $insertQuery = Db::name('user_notification_reads');
            $insertQuery->extra('IGNORE')->insertAll($insertData);
        }
    }

    /**
     * 全部标记已读（批量操作，避免 N+1）
     */
    public function markAllAsRead(int $userId): void
    {
        // 获取所有已读的通知ID
        $readNotificationIds = Db::name('user_notification_reads')
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->column('notification_id');

        // 查询所有该用户未读的通知
        $unreadQuery = $this->accessibleQuery($userId);

        if (!empty($readNotificationIds)) {
            $unreadQuery->whereNotIn('id', $readNotificationIds);
        }

        $unreadIds = $unreadQuery->column('id');

        if (empty($unreadIds)) {
            return;
        }

        // 利用 markAsRead 的批量逻辑
        $this->markAsRead($userId, $unreadIds);
    }

    /**
     * 当前用户可见的通知查询：本人定向通知 + 全员广播。
     */
    private function accessibleQuery(int $userId)
    {
        return $this->model->where('deleted_at', null)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->whereOr('user_id', 0);
            });
    }
}
