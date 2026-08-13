<?php
declare(strict_types=1);

namespace app\service\user;

use app\repository\user\UserRepository;
use app\repository\user\UserTagRelationRepository;
use app\repository\user\UserTagRepository;
use core\base\Service;
use core\exception\BusinessException;
use think\facade\Db;
use think\facade\Log;

class UserTagService extends Service
{
    protected UserTagRepository         $userTagRepository;
    protected UserTagRelationRepository $userTagRelationRepository;
    protected UserRepository            $userRepository;
    protected UserRuleEngine            $userRuleEngine;

    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        return $this->userTagRepository->getPageList($params, $page, $limit);
    }

    /**
     * 全部启用标签（前端 4 列分组卡片网格使用）
     */
    public function getAllActive(): array
    {
        return $this->userTagRepository->getAllOrdered();
    }

    public function getDetail(int $id): array
    {
        $row = $this->userTagRepository->find($id);
        if (!$row) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $row;
    }

    public function create(array $data): array
    {
        return $this->userTagRepository->create($this->payload($data, true));
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->userTagRepository->find($id)) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->userTagRepository->update($id, $this->payload($data, false));
    }

    /**
     * 删除标签（同时删除关联关系，事务保护）
     */
    public function delete(int $id): bool
    {
        return Db::transaction(function () use ($id) {
            $this->userTagRelationRepository->deleteByTagId($id);
            return $this->userTagRepository->delete($id);
        });
    }

    public function assignTags(array $userIds, array $tagIds): void
    {
        if (empty($userIds) || empty($tagIds)) return;
        $this->userTagRelationRepository->batchInsertIgnore($userIds, $tagIds);
        // 同步更新 user_count 缓存
        foreach (array_unique(array_map('intval', $tagIds)) as $tid) {
            $this->userTagRepository->updateUserCount($tid, $this->userTagRelationRepository->countByTagId($tid));
        }
    }

    public function removeTags(array $userIds, array $tagIds): void
    {
        if (empty($userIds) || empty($tagIds)) return;
        $this->userTagRelationRepository->batchDelete($userIds, $tagIds);
        foreach (array_unique(array_map('intval', $tagIds)) as $tid) {
            $this->userTagRepository->updateUserCount($tid, $this->userTagRelationRepository->countByTagId($tid));
        }
    }

    public function getUserTags(int $userId): array
    {
        return $this->userTagRelationRepository->getTagsByUserId($userId);
    }

    public function getUsersByTag(int $tagId, int $page, int $limit): array
    {
        $result  = $this->userTagRelationRepository->getUserIdsByTagId($tagId, $page, $limit);
        $userIds = $result['user_ids'];
        $total   = $result['total'];

        if (empty($userIds)) {
            return [
                'list'       => [],
                'pagination' => ['current_page' => $page, 'per_page' => $limit, 'total' => 0, 'last_page' => 1],
            ];
        }

        $users = $this->userRepository->findByIds($userIds, 'id,nickname,avatar,mobile,status,created_at');

        return [
            'list'       => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 按规则刷新单个标签覆盖用户（全量替换 + user_count 缓存）
     * 事务包裹整个"替换关系 + 更新计数"流程，避免数据不一致
     *
     * @return int 匹配到的用户数
     */
    public function refreshTag(int $tagId): int
    {
        $tag = $this->userTagRepository->find($tagId);
        if (!$tag) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        // 规则匹配在事务外（纯读，不需要锁）
        $userIds = $this->userRuleEngine->matchUserIds($tag['rules'] ?? null);
        $count = count($userIds);

        Db::transaction(function () use ($tagId, $userIds, $count) {
            $this->userTagRelationRepository->replaceForTag($tagId, $userIds);
            $this->userTagRepository->updateUserCount($tagId, $count);
        });

        return $count;
    }

    /**
     * 批量刷新所有 auto_update=1 的标签（cron 调度）
     *
     * @return array{success: int, failed: array<int, array{id:int, name:string, reason:string}>}
     */
    public function refreshAutoTags(): array
    {
        $tags = $this->userTagRepository->getAutoUpdateTags();
        $success = 0;
        $failed  = [];

        foreach ($tags as $tag) {
            $tid = (int)$tag['id'];
            try {
                $this->refreshTag($tid);
                $success++;
            } catch (\Throwable $e) {
                Log::error('user-tag:refresh 失败', [
                    'tag_id' => $tid,
                    'name'   => $tag['name'] ?? '',
                    'msg'    => $e->getMessage(),
                ]);
                $failed[] = [
                    'id'     => $tid,
                    'name'   => (string)($tag['name'] ?? ''),
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * 整形请求体
     */
    private function payload(array $data, bool $isCreate): array
    {
        $allowed = ['name', 'description', 'color', 'group_type', 'rules', 'auto_update', 'sort', 'status'];
        $out = [];

        if ($isCreate || array_key_exists('name', $data))        $out['name']        = trim((string)($data['name'] ?? ''));
        if ($isCreate || array_key_exists('description', $data)) $out['description'] = trim((string)($data['description'] ?? ''));
        if ($isCreate || array_key_exists('color', $data))       $out['color']       = (string)($data['color'] ?? '#409eff');
        if ($isCreate || array_key_exists('group_type', $data))  $out['group_type']  = $this->normalizeGroupType($data['group_type'] ?? 'social');
        if ($isCreate || array_key_exists('rules', $data))       $out['rules']       = $this->normalizeRules($data['rules'] ?? null);
        if ($isCreate || array_key_exists('auto_update', $data)) $out['auto_update'] = !empty($data['auto_update']) ? 1 : 0;
        if ($isCreate || array_key_exists('sort', $data))        $out['sort']        = (int)($data['sort'] ?? 0);
        if ($isCreate || array_key_exists('status', $data))      $out['status']      = isset($data['status']) ? (int)(bool)$data['status'] : 1;

        // 白名单兜底
        return array_intersect_key($out, array_flip($allowed));
    }

    private function normalizeGroupType(mixed $type): string
    {
        $allowed = ['consume', 'behavior', 'lifecycle', 'social'];
        return in_array($type, $allowed, true) ? (string)$type : 'social';
    }

    private function normalizeRules(mixed $rules): ?array
    {
        if (!is_array($rules) || empty($rules['conditions'])) {
            return null;
        }
        $conditions = [];
        foreach ($rules['conditions'] as $c) {
            if (!is_array($c) || empty($c['field'])) continue;
            $conditions[] = [
                'field'   => (string)$c['field'],
                'op'      => (string)($c['op'] ?? '='),
                'value'   => $c['value'] ?? '',
                'exclude' => !empty($c['exclude']),
            ];
        }
        if (empty($conditions)) {
            return null;
        }
        return [
            'logic'      => strtoupper((string)($rules['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
            'conditions' => $conditions,
        ];
    }
}
