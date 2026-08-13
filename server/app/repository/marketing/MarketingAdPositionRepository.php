<?php
declare(strict_types=1);

namespace app\repository\marketing;

use app\model\marketing\MarketingAdPosition;
use core\base\Repository;
use think\Model;

class MarketingAdPositionRepository extends Repository
{
    protected function getModel(): Model
    {
        return new MarketingAdPosition();
    }

    public function findByCode(string $code): ?array
    {
        $row = MarketingAdPosition::where('code', $code)->find();
        return $row ? $row->toArray() : null;
    }

    public function getSearchList(array $params, int $page = 1, int $limit = 20): array
    {
        $where = [];
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        if (!empty($params['keyword'])) {
            $kw = $params['keyword'];
            $where[] = ['name|code', 'like', "%{$kw}%"];
        }
        return $this->getList($where, $page, $limit, 'sort asc, id desc');
    }

    public function getAllActive(): array
    {
        return MarketingAdPosition::where('status', 1)
            ->order('sort asc, id desc')
            ->select()
            ->toArray();
    }
}
