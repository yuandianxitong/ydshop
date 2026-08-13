<?php
declare(strict_types=1);

namespace app\repository\user;

use app\model\user\UserTag;
use core\base\Repository;
use think\Model;

class UserTagRepository extends Repository
{
    protected function getModel(): Model
    {
        return new UserTag();
    }

    /**
     * 获取所有启用标签，按 sort 升序（前端 4 列分组卡片使用，需要全量）
     */
    public function getAllOrdered(): array
    {
        return UserTag::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 分页列表（含 keyword / group_type / status / auto_update 过滤）
     */
    public function getPageList(array $params, int $page = 1, int $limit = 20): array
    {
        $query = UserTag::order('sort', 'asc')->order('id', 'desc');

        if (!empty($params['keyword'])) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['group_type'])) {
            $query->where('group_type', (string)$params['group_type']);
        }
        if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
            $query->where('status', (int)$params['status']);
        }
        if (isset($params['auto_update']) && $params['auto_update'] !== '' && $params['auto_update'] !== null) {
            $query->where('auto_update', (int)$params['auto_update']);
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
     * 启用 + auto_update=1 的标签（cron 用）
     */
    public function getAutoUpdateTags(): array
    {
        return UserTag::where('auto_update', 1)
            ->where('status', 1)
            ->select()
            ->toArray();
    }

    /**
     * 更新标签的覆盖人数缓存
     */
    public function updateUserCount(int $tagId, int $count): void
    {
        UserTag::where('id', $tagId)->update(['user_count' => $count]);
    }

    /**
     * 加行锁查询单条（cron 内并发防护）
     */
    public function findForUpdate(int $id): ?array
    {
        $row = UserTag::where('id', $id)->lock(true)->find();
        return $row ? $row->toArray() : null;
    }
}
