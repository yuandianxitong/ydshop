<?php
declare(strict_types=1);

namespace plugins\new_user_gift\repository;

use plugins\new_user_gift\model\NewUserGift;
use core\base\Repository;
use think\Model as ThinkModel;

class NewUserGiftRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new NewUserGift();
    }

    /**
     * 分页 + 关键词 + 状态筛选
     */
    public function getPageList(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $query = $this->model
            ->alias('g')
            ->leftJoin(
                '(SELECT gift_id, COUNT(*) AS claimed_count FROM new_user_gift_logs GROUP BY gift_id) s',
                's.gift_id = g.id'
            )
            ->field('g.*, COALESCE(s.claimed_count, 0) AS claimed_count')
            ->order('g.sort_order', 'asc')
            ->order('g.id', 'desc');

        if (!empty($filters['keyword'])) {
            $query->where('g.name', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('g.status', (int)$filters['status']);
        }

        $total = $query->count();
        $list  = $query->page($page, $limit)->select()->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * 当前可发放的礼包：status=1 且在期内，按 sort_order, id 排序
     */
    public function findActive(): array
    {
        $now = date('Y-m-d H:i:s');
        return $this->model
            ->where('status', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_start')->whereOr('valid_start', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_end')->whereOr('valid_end', '>=', $now);
            })
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    public function findById(int $id): ?NewUserGift
    {
        return $this->model->where('id', $id)->find();
    }

    public function updateById(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) !== false;
    }

    public function deleteById(int $id): bool
    {
        $gift = $this->model->where('id', $id)->find();
        return $gift ? (bool)$gift->delete() : false;
    }

    /**
     * 校验名称唯一（排除自身 id）
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = $this->model->where('name', $name);
        if ($excludeId !== null) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }
}
