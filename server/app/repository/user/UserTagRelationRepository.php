<?php
declare(strict_types=1);

namespace app\repository\user;

use app\model\user\UserTag;
use app\model\user\UserTagRelation;
use core\base\Repository;
use think\facade\Db;
use think\Model;

class UserTagRelationRepository extends Repository
{
    protected function getModel(): Model
    {
        return new UserTagRelation();
    }

    /**
     * 批量插入关系，忽略重复（使用 INSERT IGNORE）
     */
    public function batchInsertIgnore(array $userIds, array $tagIds): void
    {
        if (empty($userIds) || empty($tagIds)) {
            return;
        }
        $now    = date('Y-m-d H:i:s');
        $values = [];
        $params = [];
        foreach ($userIds as $userId) {
            foreach ($tagIds as $tagId) {
                $values[] = '(?, ?, ?)';
                $params[] = (int) $userId;
                $params[] = (int) $tagId;
                $params[] = $now;
            }
        }
        // 分批执行，每批 500 行
        $chunks      = array_chunk($values, 500);
        $paramChunks = array_chunk($params, 500 * 3);
        foreach ($chunks as $i => $chunk) {
            $sql = 'INSERT IGNORE INTO `user_tag_relations` (`user_id`, `tag_id`, `created_at`) VALUES ' . implode(',', $chunk);
            Db::execute($sql, $paramChunks[$i]);
        }
    }

    /**
     * 批量删除关系
     */
    public function batchDelete(array $userIds, array $tagIds): void
    {
        if (empty($userIds) || empty($tagIds)) {
            return;
        }
        UserTagRelation::whereIn('user_id', $userIds)
            ->whereIn('tag_id', $tagIds)
            ->delete();
    }

    /**
     * 获取某用户的所有标签
     */
    public function getTagsByUserId(int $userId): array
    {
        $tagIds = UserTagRelation::where('user_id', $userId)->column('tag_id');
        if (empty($tagIds)) {
            return [];
        }
        return UserTag::whereIn('id', $tagIds)->order('sort', 'asc')->select()->toArray();
    }

    /**
     * 获取拥有某标签的用户ID列表（分页）
     */
    public function getUserIdsByTagId(int $tagId, int $page, int $limit): array
    {
        $query = UserTagRelation::where('tag_id', $tagId);
        $total = $query->count();
        $ids   = $query->page($page, $limit)->column('user_id');
        return ['user_ids' => $ids, 'total' => $total];
    }

    /**
     * 删除某个标签的所有关联关系
     */
    public function deleteByTagId(int $tagId): void
    {
        UserTagRelation::where('tag_id', $tagId)->delete();
    }

    /**
     * 全量替换某个标签的关联用户：先清空旧关系再批量写入新关系
     * 事务管理由 Service 层负责（避免与 user_count 更新割裂）
     */
    public function replaceForTag(int $tagId, array $userIds): void
    {
        UserTagRelation::where('tag_id', $tagId)->delete();

        if (empty($userIds)) {
            return;
        }
        $now    = date('Y-m-d H:i:s');
        $values = [];
        $params = [];
        foreach (array_unique(array_map('intval', $userIds)) as $uid) {
            $values[] = '(?, ?, ?)';
            $params[] = (int)$uid;
            $params[] = $tagId;
            $params[] = $now;
        }
        $rowsPerChunk = 500;
        $valueChunks  = array_chunk($values, $rowsPerChunk);
        $paramChunks  = array_chunk($params, $rowsPerChunk * 3);
        foreach ($valueChunks as $i => $chunk) {
            $sql = 'INSERT IGNORE INTO `user_tag_relations` (`user_id`, `tag_id`, `created_at`) VALUES ' . implode(',', $chunk);
            Db::execute($sql, $paramChunks[$i]);
        }
    }

    /**
     * 统计标签覆盖用户数
     */
    public function countByTagId(int $tagId): int
    {
        return UserTagRelation::where('tag_id', $tagId)->count();
    }

    /**
     * 批量获取多个用户的标签（key=user_id）
     */
    public function getTagsGroupedByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $relations = UserTagRelation::whereIn('user_id', $userIds)->select()->toArray();
        $tagIds    = array_unique(array_column($relations, 'tag_id'));
        if (empty($tagIds)) {
            return [];
        }
        $tagMap = UserTag::whereIn('id', $tagIds)->column(null, 'id');

        $result = [];
        foreach ($relations as $rel) {
            $uid = $rel['user_id'];
            $tid = $rel['tag_id'];
            if (isset($tagMap[$tid])) {
                $result[$uid][] = $tagMap[$tid];
            }
        }
        return $result;
    }
}
