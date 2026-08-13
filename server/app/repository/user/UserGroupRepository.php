<?php
declare(strict_types=1);

namespace app\repository\user;

use app\model\user\UserGroup;
use app\model\user\UserGroupRelation;
use core\base\Repository;
use think\Model;

class UserGroupRepository extends Repository
{
    protected function getModel(): Model
    {
        return new UserGroup();
    }

    /**
     * 分页列表
     */
    public function getSearchList(array $params, int $page = 1, int $limit = 20): array
    {
        $query = UserGroup::order('sort asc, id asc');

        if (!empty($params['keyword'])) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int) ceil($total / max($limit, 1)),
            ],
        ];
    }

    /**
     * 根据ID查找分组
     */
    public function findById(int $id): ?UserGroup
    {
        return UserGroup::find($id);
    }

    /**
     * 创建分组
     */
    public function createGroup(array $data): UserGroup
    {
        return UserGroup::create($data);
    }

    /**
     * 更新分组
     */
    public function updateGroup(int $id, array $data): bool
    {
        return (bool) UserGroup::where('id', $id)->update($data);
    }

    /**
     * 删除分组
     */
    public function deleteGroup(int $id): bool
    {
        $group = UserGroup::find($id);
        if (!$group) {
            return false;
        }
        return (bool) $group->delete();
    }

    /**
     * 获取分组下的用户ID列表
     */
    public function getGroupUserIds(int $groupId): array
    {
        return UserGroupRelation::where('group_id', $groupId)->column('user_id');
    }

    /**
     * 删除分组的所有关系
     */
    public function deleteGroupRelations(int $groupId): void
    {
        UserGroupRelation::where('group_id', $groupId)->delete();
    }

    /**
     * 批量插入用户分组关系
     */
    public function batchInsertRelations(int $groupId, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }
        $now  = date('Y-m-d H:i:s');
        $data = [];
        foreach ($userIds as $userId) {
            $data[] = [
                'user_id'    => (int) $userId,
                'group_id'   => $groupId,
                'created_at' => $now,
            ];
        }
        // 分批插入，避免单次过大
        foreach (array_chunk($data, 500) as $chunk) {
            UserGroupRelation::insertAll($chunk);
        }
    }

    /**
     * 删除指定用户在分组中的关系
     */
    public function removeUsersFromGroup(int $groupId, array $userIds): void
    {
        UserGroupRelation::where('group_id', $groupId)
            ->whereIn('user_id', $userIds)
            ->delete();
    }

    /**
     * 添加用户到分组（忽略已存在的）
     */
    public function addUsersToGroup(int $groupId, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }
        // 过滤已存在的
        $existing = UserGroupRelation::where('group_id', $groupId)
            ->whereIn('user_id', $userIds)
            ->column('user_id');

        $newIds = array_diff($userIds, $existing);
        $this->batchInsertRelations($groupId, array_values($newIds));
    }

    /**
     * 统计分组用户数
     */
    public function countGroupUsers(int $groupId): int
    {
        return UserGroupRelation::where('group_id', $groupId)->count();
    }

    /**
     * 更新分组用户数缓存
     */
    public function updateUserCount(int $groupId): void
    {
        $count = $this->countGroupUsers($groupId);
        UserGroup::where('id', $groupId)->update(['user_count' => $count]);
    }

    /**
     * 获取所有需要自动刷新的分组
     */
    public function getAutoUpdateGroups(): array
    {
        return UserGroup::where('auto_update', 1)
            ->where('status', 1)
            ->select()
            ->toArray();
    }

    /**
     * 分页获取分组内的用户列表
     */
    public function getGroupUsersPaginated(int $groupId, int $page, int $limit): array
    {
        $query = UserGroupRelation::where('group_id', $groupId)
            ->order('created_at', 'desc');

        $total   = $query->count();
        $records = $query->page($page, $limit)->select()->toArray();

        $userIds = array_column($records, 'user_id');

        $users = [];
        if (!empty($userIds)) {
            $users = \app\model\user\User::whereIn('id', $userIds)
                ->field('id,nickname,avatar,mobile,status,created_at')
                ->select()
                ->toArray();
        }

        return [
            'list'       => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int) ceil($total / max($limit, 1)),
            ],
        ];
    }
}
