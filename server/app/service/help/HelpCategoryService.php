<?php
declare(strict_types=1);

namespace app\service\help;

use app\repository\help\HelpCategoryRepository;
use core\base\Service;
use core\exception\BusinessException;

class HelpCategoryService extends Service
{
    protected HelpCategoryRepository $helpCategoryRepository;

    public function getList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->helpCategoryRepository->getSearchList($params, $page, $limit);
    }

    public function getAllActive(): array
    {
        return $this->helpCategoryRepository->getAllActive();
    }

    public function detail(int $id): ?array
    {
        return $this->helpCategoryRepository->find($id);
    }

    public function create(array $data): array
    {
        return $this->helpCategoryRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->helpCategoryRepository->find($id);
        if (!$existing) throw new BusinessException('分类不存在');
        return $this->helpCategoryRepository->update($id, $data);
    }

    /**
     * 删除分类前检查：下有帮助则拒绝
     */
    public function delete(int $id): bool
    {
        $existing = $this->helpCategoryRepository->find($id);
        if (!$existing) throw new BusinessException('分类不存在');

        $count = $this->helpCategoryRepository->countHelpsByCategoryId($id);
        if ($count > 0) {
            throw new BusinessException("该分类下仍有 {$count} 篇帮助，请先迁移或删除");
        }

        return $this->helpCategoryRepository->delete($id);
    }
}
