<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsCategoryRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsCategoryService extends Service
{
    protected GoodsCategoryRepository $goodsCategoryRepo;

    /**
     * 获取列表
     */
    public function getList(array $params): array
    {
        $where = [];

        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }

        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        return $this->goodsCategoryRepo->getPageList($where, $page, $limit);
    }

    /**
     * 获取详情
     */
    public function getDetail(int $id): array
    {
        $result = $this->goodsCategoryRepo->find($id);
        if (!$result) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $result;
    }

    /**
     * 获取分类树
     */
    public function getTree(): array
    {
        $list = $this->goodsCategoryRepo->getAll([['status', '=', 1]], 'sort asc, id asc');
        return $this->buildTree($list, 0);
    }

    /**
     * 递归构建树
     */
    private function buildTree(array $list, int $parentId): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ((int)$item['parent_id'] === $parentId) {
                $children = $this->buildTree($list, (int)$item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * 创建分类 — 自动计算 level 和 path
     */
    public function create(array $data): array
    {
        $parentId = (int)($data['parent_id'] ?? 0);
        if ($parentId > 0) {
            $parent = $this->goodsCategoryRepo->find($parentId);
            if (!$parent) {
                throw new \core\exception\BusinessException('父分类不存在');
            }
            $data['level'] = (int)$parent['level'] + 1;
            $data['path'] = $parent['path'] . ',' . $parentId;
        } else {
            $data['level'] = 1;
            $data['path'] = '0';
        }
        return $this->goodsCategoryRepo->create($data);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->goodsCategoryRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        $updateData = array_filter([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort' => $data['sort'] ?? null,
            'level' => $data['level'] ?? null,
            'path' => $data['path'] ?? null,
            'status' => $data['status'] ?? null,
            'is_show' => $data['is_show'] ?? null,
        ], fn($v) => $v !== null);

        return $this->goodsCategoryRepo->update($id, $updateData);
    }

    /**
     * 删除
     */
    public function delete(int $id): bool
    {
        $record = $this->goodsCategoryRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        if (!$this->goodsCategoryRepo->delete($id)) {
            throw new BusinessException(lang('messages.delete_failed'));
        }
        return true;
    }

    /**
     * 更新状态
     */
    public function updateStatus(int $id, int $status): bool
    {
        $record = $this->goodsCategoryRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->goodsCategoryRepo->update($id, ['status' => $status]);
    }

    /**
     * 选择器水合：按 IDs 取轻量字段，返回顺序与入参一致
     */
    public function getByIds(array $ids): array
    {
        $rows = $this->goodsCategoryRepo->findByIds($ids);
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int)$r['id']] = $r;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[(int)$id])) {
                $ordered[] = $byId[(int)$id];
            }
        }
        return $ordered;
    }
}
