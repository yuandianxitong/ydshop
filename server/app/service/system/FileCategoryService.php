<?php
declare(strict_types=1);

namespace app\service\system;

use app\repository\system\FileCategoryRepository;
use app\repository\system\FileRepository;
use core\base\Service;
use core\exception\BusinessException;

class FileCategoryService extends Service
{
    protected FileCategoryRepository $fileCategoryRepo;
    protected FileRepository $fileRepo;

    /**
     * 获取分类树（含每个分类的文件数量）
     */
    public function getTree(): array
    {
        $list = $this->fileCategoryRepo->getAll([], 'sort asc, id asc');

        // 获取每个分类的文件数量
        $counts = $this->fileRepo->getFileCountByCategory();

        // 附加文件数量到列表
        foreach ($list as &$item) {
            $item['file_count'] = $counts[(int)$item['id']] ?? 0;
        }
        unset($item);

        return $this->buildTree($list, 0);
    }

    /**
     * 创建分类
     */
    public function create(array $data): array
    {
        return $this->fileCategoryRepo->create([
            'name'      => $data['name'],
            'parent_id' => (int)($data['parent_id'] ?? 0),
            'sort'      => (int)($data['sort'] ?? 0),
        ]);
    }

    /**
     * 更新分类
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->fileCategoryRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['parent_id'])) {
            $updateData['parent_id'] = (int)$data['parent_id'];
        }
        if (isset($data['sort'])) {
            $updateData['sort'] = (int)$data['sort'];
        }

        return $this->fileCategoryRepo->update($id, $updateData);
    }

    /**
     * 删除分类（同时将该分类下的文件归为未分类）
     */
    public function delete(int $id): bool
    {
        $record = $this->fileCategoryRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        // 将该分类下的文件设置为未分类
        $this->fileRepo->updateWhere(
            [['category_id', '=', $id]],
            ['category_id' => 0]
        );

        return $this->fileCategoryRepo->delete($id);
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
}
