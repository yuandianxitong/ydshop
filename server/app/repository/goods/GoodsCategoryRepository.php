<?php
declare(strict_types=1);

namespace app\repository\goods;

use app\model\goods\GoodsCategory;
use core\base\Repository;
use think\Model as ThinkModel;

class GoodsCategoryRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new GoodsCategory();
    }

    public function find($id): ?array
    {
        $result = $this->model
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->find();

        return $result ? $result->toArray() : null;
    }

    /**
     * 分页列表
     */
    public function getPageList(array $where = [], int $page = 1, int $limit = 15): array
    {
        $query = $this->model->where($where)->whereNull('deleted_at');

        $total = $query->count();
        $list = $query->page($page, $limit)
            ->order('id desc')
            ->select()
            ->toArray();

        return [
            'list'       => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $limit),
            ],
        ];
    }

    public function getAll(array $where = [], string $order = 'id desc'): array
    {
        return $this->model
            ->where($where)
            ->whereNull('deleted_at')
            ->order($order)
            ->select()
            ->toArray();
    }

    public function delete($id): bool
    {
        $now = date('Y-m-d H:i:s');

        return $this->model
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => $now,
                'updated_at' => $now,
            ]) > 0;
    }

    /**
     * 按 IDs 批量取轻量字段（选择器 by-ids 端点用）
     * 返回：[{ id, name, parent_id }]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->model
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->field('id, name, parent_id')
            ->select()
            ->toArray();
    }

    /**
     * 取某父分类下所有启用的子分类 id（C 端商品列表按父类筛选时聚合子类用）
     *
     * @return int[]
     */
    public function getActiveChildIds(int $parentId): array
    {
        return $this->model->where('parent_id', $parentId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->column('id');
    }

    /**
     * 取某分类下所有子孙分类 id。
     *
     * @return int[]
     */
    public function getDescendantIds(int $parentId, bool $onlyActive = false): array
    {
        $ids = [];
        $parentIds = [$parentId];

        while (!empty($parentIds)) {
            $query = $this->model->whereIn('parent_id', $parentIds)
                ->whereNull('deleted_at');
            if ($onlyActive) {
                $query->where('status', 1);
            }

            $children = array_map('intval', $query->column('id'));
            if (empty($children)) {
                break;
            }

            $ids = array_merge($ids, $children);
            $parentIds = $children;
        }

        return array_values(array_unique($ids));
    }

    /**
     * 按 ID 列表取启用分类的精简字段（首页装修分类组件用）
     */
    public function findActiveByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->model->whereIn('id', $ids)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->field('id, name, icon, sort')
            ->order('sort asc')
            ->select()
            ->toArray();
    }

    /**
     * 取一级启用分类（首页装修分类组件未指定 ID 时使用）
     */
    public function findActiveTopLevel(int $limit): array
    {
        return $this->model->where('parent_id', 0)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->field('id, name, icon, sort')
            ->order('sort asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
