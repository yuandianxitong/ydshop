<?php
declare(strict_types=1);

namespace app\repository\help;

use app\model\help\HelpCategory;
use app\model\help\Help;
use core\base\Repository;
use think\Model;

class HelpCategoryRepository extends Repository
{
    protected function getModel(): Model
    {
        return new HelpCategory();
    }

    public function getSearchList(array $params, int $page = 1, int $limit = 20): array
    {
        $where = [];
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', "%{$params['keyword']}%"];
        }
        $result = $this->getList($where, $page, $limit, 'sort asc, id desc');

        // enrich: 每个分类下 status=1 的帮助数
        foreach ($result['list'] as &$row) {
            $row['help_count'] = Help::where('category_id', $row['id'])
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->count();
        }
        return $result;
    }

    public function getAllActive(): array
    {
        return HelpCategory::where('status', 1)
            ->order('sort asc, id desc')
            ->select()
            ->toArray();
    }

    public function countHelpsByCategoryId(int $categoryId): int
    {
        return Help::where('category_id', $categoryId)->whereNull('deleted_at')->count();
    }
}
