<?php
declare(strict_types=1);

namespace app\service\user;

use app\repository\user\UserGroupRepository;
use core\base\Service;
use core\exception\BusinessException;

class UserGroupService extends Service
{
    protected UserGroupRepository $userGroupRepository;
    protected UserRuleEngine $userRuleEngine;

    // ─────────────── CRUD ───────────────

    public function getList(array $params): array
    {
        $page  = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        return $this->userGroupRepository->getSearchList($params, $page, $limit);
    }

    public function create(array $data): array
    {
        $group = $this->userGroupRepository->createGroup($data);
        return $group->toArray();
    }

    public function update(int $id, array $data): array
    {
        $group = $this->userGroupRepository->findById($id);
        if (!$group) {
            throw new BusinessException('用户分组不存在');
        }
        $group->save($data);
        return $group->toArray();
    }

    public function delete(int $id): bool
    {
        $group = $this->userGroupRepository->findById($id);
        if (!$group) {
            throw new BusinessException('用户分组不存在');
        }
        // 删除关联关系
        $this->userGroupRepository->deleteGroupRelations($id);
        return $this->userGroupRepository->deleteGroup($id);
    }

    public function detail(int $id): array
    {
        $group = $this->userGroupRepository->findById($id);
        if (!$group) {
            throw new BusinessException('用户分组不存在');
        }
        return $group->toArray();
    }

    // ─────────────── Rule Engine ───────────────

    /**
     * 刷新分组（根据规则重新匹配用户，全量替换关系）
     */
    public function refreshGroup(int $groupId): int
    {
        $group = $this->userGroupRepository->findById($groupId);
        if (!$group) {
            throw new BusinessException('用户分组不存在');
        }

        $userIds = $this->userRuleEngine->matchUserIds($group->rules);

        $this->userGroupRepository->deleteGroupRelations($groupId);
        $this->userGroupRepository->batchInsertRelations($groupId, $userIds);
        $this->userGroupRepository->updateUserCount($groupId);

        return count($userIds);
    }

    // ─────────────── User Management ───────────────

    /**
     * 手动添加用户到分组
     */
    public function addUsers(int $groupId, array $userIds): void
    {
        $group = $this->userGroupRepository->findById($groupId);
        if (!$group) {
            throw new BusinessException('用户分组不存在');
        }
        $this->userGroupRepository->addUsersToGroup($groupId, $userIds);
        $this->userGroupRepository->updateUserCount($groupId);
    }

    /**
     * 从分组中移除用户
     */
    public function removeUsers(int $groupId, array $userIds): void
    {
        $group = $this->userGroupRepository->findById($groupId);
        if (!$group) {
            throw new BusinessException('用户分组不存在');
        }
        $this->userGroupRepository->removeUsersFromGroup($groupId, $userIds);
        $this->userGroupRepository->updateUserCount($groupId);
    }

    /**
     * 分页获取分组内用户
     */
    public function getGroupUsers(int $groupId, int $page = 1, int $limit = 20): array
    {
        $group = $this->userGroupRepository->findById($groupId);
        if (!$group) {
            throw new BusinessException('用户分组不存在');
        }
        return $this->userGroupRepository->getGroupUsersPaginated($groupId, $page, $limit);
    }
}
