<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\service\announcement;

use app\model\announcement\Announcement;
use app\repository\announcement\AnnouncementRepository;
use core\base\Service;
use core\exception\BusinessException;

class AnnouncementService extends Service
{
    protected AnnouncementRepository $announcementRepository;

    /**
     * 获取公告列表（管理端）
     */
    public function getList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->announcementRepository->getSearchList($params, $page, $limit);
    }

    /**
     * 公告详情
     */
    public function detail(int $id): ?array
    {
        return $this->announcementRepository->find($id);
    }

    /**
     * 创建公告
     */
    public function create(array $data): array
    {
        // 如果状态为已发布，设置发布时间
        if (isset($data['status']) && (int) $data['status'] === Announcement::STATUS_PUBLISHED) {
            $data['publish_at'] = date('Y-m-d H:i:s');
        }

        $announcement = $this->announcementRepository->create($data);

        $this->trigger('announcement.created', [
            'announcement_id' => $announcement['id'],
            'title'           => $data['title'],
        ]);
        if ((int) ($announcement['status'] ?? 0) === Announcement::STATUS_PUBLISHED) {
            $this->triggerPublished($announcement);
        }

        return $announcement;
    }

    /**
     * 更新公告
     */
    public function update(int $id, array $data): bool
    {
        $announcement = $this->findOrFail($this->announcementRepository, $id);

        // 如果从草稿变为已发布且没有发布时间，设置发布时间
        if (isset($data['status']) && (int) $data['status'] === Announcement::STATUS_PUBLISHED
            && (int) $announcement['status'] === Announcement::STATUS_DRAFT) {
            $data['publish_at'] = date('Y-m-d H:i:s');
        }

        $updated = $this->announcementRepository->update($id, $data);
        if ($updated
            && isset($data['status'])
            && (int) $data['status'] === Announcement::STATUS_PUBLISHED
            && (int) $announcement['status'] === Announcement::STATUS_DRAFT) {
            $this->triggerPublished(array_merge($announcement, $data, ['id' => $id]));
        }

        return $updated;
    }

    /**
     * 更新公告状态
     */
    public function updateStatus(int $id, int $status): bool
    {
        $announcement = $this->findOrFail($this->announcementRepository, $id);

        $updateData = ['status' => $status];

        // 发布时设置发布时间
        if ($status === Announcement::STATUS_PUBLISHED && (int) $announcement['status'] === Announcement::STATUS_DRAFT) {
            $updateData['publish_at'] = date('Y-m-d H:i:s');
        }

        $updated = $this->announcementRepository->update($id, $updateData);
        if ($updated
            && $status === Announcement::STATUS_PUBLISHED
            && (int) $announcement['status'] === Announcement::STATUS_DRAFT) {
            $this->triggerPublished(array_merge($announcement, $updateData, ['id' => $id]));
        }

        return $updated;
    }

    /**
     * 获取已发布的公告列表（C端）
     */
    public function getPublishedList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->announcementRepository->getPublishedList($page, $limit);
    }

    /**
     * 获取当前已发布且已到发布时间的公告详情（C端）
     */
    public function getPublishedDetail(int $id): ?array
    {
        return $this->announcementRepository->findPublished($id);
    }

    /**
     * 删除公告
     */
    public function delete(int $id): bool
    {
        return $this->announcementRepository->delete($id);
    }

    private function triggerPublished(array $announcement): void
    {
        $this->trigger('announcement.published', [
            'announcement_id' => (int) ($announcement['id'] ?? 0),
            'title'           => (string) ($announcement['title'] ?? ''),
            'content'         => (string) ($announcement['content'] ?? ''),
            'type'            => (int) ($announcement['type'] ?? 1),
            'publish_at'      => (string) ($announcement['publish_at'] ?? date('Y-m-d H:i:s')),
        ]);
    }
}
