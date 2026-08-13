<?php
declare(strict_types=1);

namespace app\service\help;

use app\repository\help\HelpRepository;
use app\repository\help\HelpCategoryRepository;
use core\base\Service;
use core\exception\BusinessException;

class HelpService extends Service
{
    protected HelpRepository $helpRepository;
    protected HelpCategoryRepository $helpCategoryRepository;

    public function getList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->helpRepository->getSearchList($params, $page, $limit);
    }

    public function detail(int $id): ?array
    {
        return $this->helpRepository->find($id);
    }

    public function create(array $data): array
    {
        $this->validateCategory((int)($data['category_id'] ?? 0));
        return $this->helpRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->helpRepository->find($id);
        if (!$existing) throw new BusinessException('帮助不存在');
        if (isset($data['category_id'])) {
            $this->validateCategory((int)$data['category_id']);
        }
        return $this->helpRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->helpRepository->delete($id);
    }

    public function batchStatus(array $ids, int $status): int
    {
        if (!in_array($status, [0, 1], true)) {
            throw new BusinessException('status 必须为 0 或 1');
        }
        return $this->helpRepository->batchUpdateStatus($ids, $status);
    }

    /**
     * C 端列表
     */
    public function getPublicList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->helpRepository->getPublicList($params, $page, $limit);
    }

    /**
     * C 端详情：累加 view_count
     */
    public function getPublicDetail(int $id): ?array
    {
        $row = $this->helpRepository->getPublicDetail($id);
        if (!$row) return null;

        // 累加阅读量（任何失败静默）
        try {
            $this->helpRepository->incViewCount($id);
            $row['view_count'] = (int)$row['view_count'] + 1;
        } catch (\Throwable $e) {
            /* silent */
        }

        // 剥除 admin_id / deleted_at 等 internal
        unset($row['admin_id'], $row['deleted_at']);
        return $row;
    }

    private function validateCategory(int $categoryId): void
    {
        if (!$categoryId) throw new BusinessException('请选择分类');
        $cat = $this->helpCategoryRepository->find($categoryId);
        if (!$cat || (int)$cat['status'] !== 1) {
            throw new BusinessException('分类不存在或已停用');
        }
    }
}
