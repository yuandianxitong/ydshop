<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\service\feedback;

use app\model\feedback\Feedback;
use app\repository\feedback\FeedbackRepository;
use core\base\Service;
use core\exception\BusinessException;

class FeedbackService extends Service
{
    protected FeedbackRepository $feedbackRepository;

    /**
     * 提交反馈（C端用户）
     */
    public function submit(int $userId, array $data): array
    {
        $type = (string) ($data['type'] ?? Feedback::TYPE_SUGGESTION);
        if (!in_array($type, Feedback::TYPES, true)) {
            throw new BusinessException('反馈类型不正确');
        }

        $content = trim((string) ($data['content'] ?? ''));
        if ($content === '') {
            throw new BusinessException('反馈内容不能为空');
        }
        if (mb_strlen($content) > 1000) {
            throw new BusinessException('反馈内容不能超过 1000 字');
        }

        $images = $this->normalizeImages($data['images'] ?? []);
        $contact = trim((string) ($data['contact'] ?? ''));
        if (mb_strlen($contact) > 100) {
            throw new BusinessException('联系方式不能超过 100 字');
        }

        $feedback = $this->feedbackRepository->create([
            'user_id' => $userId,
            'type'    => $type,
            'content' => $content,
            'images'  => $images,
            'contact' => $contact,
            'status'  => Feedback::STATUS_PENDING,
        ]);

        $this->trigger('feedback.created', [
            'feedback_id' => $feedback['id'],
            'user_id'     => $userId,
            'type'        => $type,
        ]);

        return $feedback;
    }

    /**
     * 获取用户的反馈列表（C端）
     */
    public function getUserList(int $userId, array $params): array
    {
        [$page, $limit] = $this->extractPagination($params, 10);
        return $this->feedbackRepository->getUserFeedbacks($userId, $page, $limit);
    }

    /**
     * 搜索反馈列表（管理端）
     */
    public function getList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->feedbackRepository->getSearchList($params, $page, $limit);
    }

    /**
     * 反馈详情
     */
    public function detail(int $id): ?array
    {
        return $this->feedbackRepository->find($id);
    }

    /**
     * 获取当前用户的反馈详情（C 端）。
     *
     * 不存在与不属于当前用户统一返回“记录不存在”，防止通过递增 ID
     * 探测其他用户的反馈记录。
     */
    public function getUserDetail(int $userId, int $id): array
    {
        $feedback = $this->feedbackRepository->findByIdAndUser($id, $userId);
        if (!$feedback) {
            throw new BusinessException('记录不存在');
        }
        return $feedback;
    }

    /**
     * 回复反馈（管理端）
     */
    public function reply(int $id, int $adminId, string $replyContent): bool
    {
        $feedback = $this->feedbackRepository->find($id);
        if (!$feedback) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        if ((int) $feedback['status'] === Feedback::STATUS_CLOSED) {
            throw new BusinessException(lang('business.feedback_closed'));
        }

        $replyContent = trim($replyContent);
        if ($replyContent === '' || mb_strlen($replyContent) > 2000) {
            throw new BusinessException('回复内容不能为空且不能超过 2000 字');
        }

        $repliedAt = date('Y-m-d H:i:s');
        $updated = $this->feedbackRepository->update($id, [
            'reply'      => $replyContent,
            'replied_at' => $repliedAt,
            'replied_by' => $adminId,
            'status'     => Feedback::STATUS_REPLIED,
        ]);

        if ($updated) {
            $this->trigger('feedback.replied', [
                'feedback_id' => $id,
                'user_id'     => (int) $feedback['user_id'],
                'reply'       => $replyContent,
                'replied_at'  => $repliedAt,
            ]);
        }

        return $updated;
    }

    /**
     * 关闭反馈
     */
    public function close(int $id): bool
    {
        $feedback = $this->feedbackRepository->find($id);
        if (!$feedback) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        if ((int) $feedback['status'] === Feedback::STATUS_CLOSED) {
            return true;
        }

        $updated = $this->feedbackRepository->update($id, [
            'status' => Feedback::STATUS_CLOSED,
        ]);
        if ($updated) {
            $this->trigger('feedback.closed', [
                'feedback_id' => $id,
                'user_id'     => (int) $feedback['user_id'],
            ]);
        }

        return $updated;
    }

    /**
     * 删除反馈
     */
    public function delete(int $id): bool
    {
        return $this->feedbackRepository->delete($id);
    }

    /**
     * @return string[]
     */
    private function normalizeImages(mixed $images): array
    {
        if (!is_array($images)) {
            throw new BusinessException('反馈图片格式不正确');
        }
        if (count($images) > 3) {
            throw new BusinessException('反馈图片最多上传 3 张');
        }

        $normalized = [];
        foreach ($images as $image) {
            if (!is_string($image)) {
                throw new BusinessException('反馈图片格式不正确');
            }

            $image = trim($image);
            if ($image === '') {
                continue;
            }
            if (mb_strlen($image) > 500) {
                throw new BusinessException('反馈图片地址过长');
            }
            $normalized[] = $image;
        }

        return array_values(array_unique($normalized));
    }
}
