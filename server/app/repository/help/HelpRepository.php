<?php
declare(strict_types=1);

namespace app\repository\help;

use app\model\help\Help;
use app\model\help\HelpCategory;
use app\model\system\Admin;
use core\base\Repository;
use think\Model;

class HelpRepository extends Repository
{
    protected function getModel(): Model
    {
        return new Help();
    }

    /**
     * C 端列表：仅 status=1 + 分类启用，不返回 content
     */
    public function getPublicList(array $params, int $page = 1, int $limit = 20): array
    {
        $categoryId = (int)($params['category_id'] ?? 0);
        $keyword = trim((string)($params['keyword'] ?? ''));

        $query = Help::alias('h')
            ->join('help_categories c', 'c.id = h.category_id')
            ->where('h.status', 1)
            ->where('c.status', 1)
            ->whereNull('h.deleted_at');

        if ($categoryId > 0) {
            $query->where('h.category_id', $categoryId);
        }
        if ($keyword !== '') {
            $query->where('h.title', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list = $query
            ->field('h.id, h.category_id, h.title, h.summary, h.view_count, h.created_at, c.name AS category_name')
            ->order('h.id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->buildPagination($list, $page, $limit, $total);
    }

    /**
     * C 端详情：必须 status=1 + 分类启用
     */
    public function getPublicDetail(int $id): ?array
    {
        $row = Help::alias('h')
            ->join('help_categories c', 'c.id = h.category_id')
            ->where('h.id', $id)
            ->where('h.status', 1)
            ->where('c.status', 1)
            ->whereNull('h.deleted_at')
            ->field('h.*, c.name AS category_name')
            ->find();
        return $row ? $row->toArray() : null;
    }

    public function incViewCount(int $id): void
    {
        Help::where('id', $id)->inc('view_count')->update();
    }

    /**
     * admin 列表：含全部状态，enrich category + admin_name
     */
    public function getSearchList(array $params, int $page = 1, int $limit = 20): array
    {
        $where = [];
        if (!empty($params['category_id'])) {
            $where[] = ['category_id', '=', (int)$params['category_id']];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        if (!empty($params['keyword'])) {
            $where[] = ['title', 'like', "%{$params['keyword']}%"];
        }
        $result = $this->getList($where, $page, $limit, 'id desc');

        // enrich category + admin_name
        $catIds = array_unique(array_filter(array_column($result['list'] ?? [], 'category_id')));
        $catMap = [];
        if ($catIds) {
            foreach (HelpCategory::whereIn('id', $catIds)->select()->toArray() as $c) {
                $catMap[$c['id']] = $c;
            }
        }
        $adminIds = array_unique(array_filter(array_column($result['list'] ?? [], 'admin_id')));
        $adminMap = [];
        if ($adminIds) {
            foreach (Admin::whereIn('id', $adminIds)->field('id, nickname, username')->select()->toArray() as $a) {
                $adminMap[$a['id']] = $a;
            }
        }
        foreach ($result['list'] as &$row) {
            $row['category'] = $catMap[$row['category_id']] ?? null;
            $row['admin_name'] = $adminMap[$row['admin_id']]['nickname']
                ?? $adminMap[$row['admin_id']]['username']
                ?? '-';
        }
        return $result;
    }

    public function batchUpdateStatus(array $ids, int $status): int
    {
        if (!$ids) return 0;
        return Help::whereIn('id', $ids)->update(['status' => $status]);
    }
}
