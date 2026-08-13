<?php
declare(strict_types=1);

namespace app\repository\diy;

use core\base\Model;
use core\base\Repository;
use app\model\diy\DiyPage;

class DiyPageRepository extends Repository
{
    protected function getModel(): Model
    {
        return new DiyPage();
    }

    public function getPageList(array $where, int $page, int $limit): array
    {
        $query = $this->model->where('deleted_at', null);

        if (!empty($where['platform'])) {
            $query->where('platform', $where['platform']);
        }
        if (!empty($where['page_type'])) {
            $query->where('page_type', $where['page_type']);
        }
        if (!empty($where['keyword'])) {
            $query->where('title', 'like', '%' . $where['keyword'] . '%');
        }

        $total = $query->count();
        $list = $query->order('sort asc, id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'list' => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $limit),
            ]
        ];
    }

    public function findByTypePlatform(string $pageType, string $platform, ?int $excludeId = null): ?array
    {
        $query = $this->model->where('deleted_at', null)
            ->where('page_type', $pageType)
            ->where('platform', $platform);

        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }

        $result = $query->find();
        return $result ? $result->toArray() : null;
    }

    public function getPublishedPage(string $pageType, string $platform): ?array
    {
        $base = $this->model->where('deleted_at', null)
            ->where('page_type', $pageType)
            ->where('platform', $platform)
            ->where('is_published', 1)
            ->where('status', 1);

        $default = (clone $base)->where('is_default', 1)->find();
        if ($default) {
            return $default->toArray();
        }
        $latest = (clone $base)->order('updated_at desc, id desc')->find();
        return $latest ? $latest->toArray() : null;
    }

    public function findDefault(string $pageType, string $platform): ?array
    {
        $result = $this->model->where('deleted_at', null)
            ->where('page_type', $pageType)
            ->where('platform', $platform)
            ->where('is_default', 1)
            ->find();
        return $result ? $result->toArray() : null;
    }

    public function clearDefault(string $pageType, string $platform): bool
    {
        return $this->model->where('deleted_at', null)
            ->where('page_type', $pageType)
            ->where('platform', $platform)
            ->where('is_default', 1)
            ->update(['is_default' => 0]) !== false;
    }

    public function countByTypePlatform(string $pageType, string $platform): int
    {
        return $this->model->where('deleted_at', null)
            ->where('page_type', $pageType)
            ->where('platform', $platform)
            ->count();
    }
}
