<?php
declare(strict_types=1);

namespace app\service\notification;

use app\model\notification\UserNotification;
use core\base\Service;
use core\exception\BusinessException;
use app\repository\notification\UserNotificationRepository;

class UserNotificationService extends Service
{
    protected UserNotificationRepository $repo;

    /**
     * 获取用户消息列表
     */
    public function getUserMessages(int $userId, array $params): array
    {
        [$page, $limit] = $this->extractPagination($params, 10);
        return $this->repo->getUserMessages($userId, $page, $limit);
    }

    /**
     * 获取当前用户可见的消息详情。
     */
    public function getUserMessage(int $userId, int $notificationId): array
    {
        $notification = $this->repo->findAccessible($notificationId, $userId);
        if (!$notification) {
            throw new BusinessException('消息不存在');
        }
        return $notification;
    }

    /**
     * 获取未读消息数量
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->repo->getUnreadCount($userId);
    }

    /**
     * 标记指定通知为已读
     */
    public function markAsRead(int $userId, array $notificationIds): void
    {
        $normalizedIds = [];
        foreach ($notificationIds as $notificationId) {
            if ((!is_int($notificationId) && !(is_string($notificationId) && ctype_digit($notificationId)))
                || (int) $notificationId <= 0) {
                throw new BusinessException('消息 ID 列表格式不正确');
            }
            $normalizedIds[] = (int) $notificationId;
        }

        $normalizedIds = array_values(array_unique($normalizedIds));
        if (count($normalizedIds) > 100) {
            throw new BusinessException('单次最多标记 100 条消息');
        }

        $this->repo->markAsRead($userId, $normalizedIds);
    }

    /**
     * 全部标记已读
     */
    public function markAllAsRead(int $userId): void
    {
        $this->repo->markAllAsRead($userId);
    }

    /**
     * 创建用户通知
     */
    public function createNotification(array $data): array
    {
        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId < 0) {
            throw new BusinessException('通知用户 ID 不正确');
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200) {
            throw new BusinessException('通知标题不能为空且不能超过 200 字');
        }

        $type = (string) ($data['type'] ?? UserNotification::TYPE_SYSTEM);
        if (!in_array($type, UserNotification::TYPES, true)) {
            throw new BusinessException('通知类型不正确');
        }

        $eventKey = trim((string) ($data['event_key'] ?? ''));
        if (mb_strlen($eventKey) > 191) {
            throw new BusinessException('通知事件键不能超过 191 字');
        }

        $data['user_id'] = $userId;
        $data['title'] = $title;
        $data['content'] = trim((string) ($data['content'] ?? ''));
        $data['type'] = $type;
        $data['event_key'] = $eventKey !== '' ? $eventKey : null;
        $data['biz_id'] = isset($data['biz_id']) && (int) $data['biz_id'] > 0
            ? (int) $data['biz_id']
            : null;
        $data['extra'] = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        $notification = $this->repo->createOnce($data);

        $this->trigger('user.notification.created', [
            'notification_id' => $notification['id'],
            'user_id'         => $userId,
            'type'            => $type,
        ]);

        return $notification;
    }
}
