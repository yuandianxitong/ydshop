<?php
declare(strict_types=1);

namespace app\repository\marketing;

use app\model\marketing\MarketingAd;
use app\model\marketing\MarketingAdPosition;
use core\base\Repository;
use think\Model;

class MarketingAdRepository extends Repository
{
    protected function getModel(): Model
    {
        return new MarketingAd();
    }

    /**
     * C 端核心查询：按 position.code 取当前生效广告
     */
    public function getActiveByPositionCode(string $code): array
    {
        $position = MarketingAdPosition::where('code', $code)->where('status', 1)->find();
        if (!$position) return ['position' => null, 'ads' => []];

        $now = date('Y-m-d H:i:s');
        $query = MarketingAd::where('position_id', $position->id)
            ->where('status', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->whereOr('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->whereOr('end_at', '>', $now);
            })
            ->order('sort desc, id desc');

        if ((int)$position->is_carousel === 0) {
            $query->limit(1);
        }

        return [
            'position' => $position->toArray(),
            'ads'      => $query->select()->toArray(),
        ];
    }

    public function getSearchList(array $params, int $page = 1, int $limit = 20): array
    {
        $where = [];
        if (!empty($params['position_id'])) {
            $where[] = ['position_id', '=', (int)$params['position_id']];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        if (!empty($params['keyword'])) {
            $where[] = ['title', 'like', "%{$params['keyword']}%"];
        }
        $result = $this->getList($where, $page, $limit, 'sort desc, id desc');

        // enrich each ad with position relation
        $positionIds = array_column($result['list'] ?? [], 'position_id');
        $positionIds = array_unique(array_filter($positionIds));
        $posMap = [];
        if ($positionIds) {
            $rows = MarketingAdPosition::whereIn('id', $positionIds)->select()->toArray();
            foreach ($rows as $r) $posMap[$r['id']] = $r;
        }
        foreach ($result['list'] as &$ad) {
            $ad['position'] = $posMap[$ad['position_id']] ?? null;
        }
        return $result;
    }

    /**
     * 批量更新状态
     */
    public function batchUpdateStatus(array $ids, int $status): int
    {
        if (!$ids) return 0;
        return MarketingAd::whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * 软删除某位置下所有广告（删除位置时级联用）
     */
    public function softDeleteByPositionId(int $positionId): int
    {
        return MarketingAd::where('position_id', $positionId)
            ->whereNull('deleted_at')
            ->useSoftDelete('deleted_at', date('Y-m-d H:i:s'))
            ->delete();
    }
}
