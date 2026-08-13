<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsSpuRepository;
use app\repository\goods\GoodsUnitGroupRepository;
use app\repository\goods\GoodsUnitRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsUnitService extends Service
{
    protected GoodsUnitRepository $goodsUnitRepo;
    protected GoodsUnitGroupRepository $goodsUnitGroupRepo;
    protected GoodsSpuRepository $goodsSpuRepository;

    /**
     * 获取列表（带分组、关联商品数）
     */
    public function getList(array $params): array
    {
        $where = [];

        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }

        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        $extra = [
            'keyword'  => $params['keyword'] ?? '',
            'group_id' => $params['group_id'] ?? '',
        ];

        $result = $this->goodsUnitRepo->getPageList($where, $page, $limit, $extra);

        // 关联商品数（一次性聚合）
        $ids = array_column($result['list'], 'id');
        $linkMap = $this->goodsUnitRepo->countLinkedSpu($ids);
        foreach ($result['list'] as &$row) {
            $row['linked_spu_count'] = $linkMap[(int)$row['id']] ?? 0;
            $row['ratio_text'] = $this->buildRatioText($row);
        }
        unset($row);

        return $result;
    }

    /**
     * 概览统计（4 个 KPI）
     */
    public function getStats(): array
    {
        $unitTotal  = $this->goodsUnitRepo->count([]);
        $groupTotal = $this->goodsUnitGroupRepo->count([]);
        $convTotal  = $this->goodsUnitRepo->count([['is_base', '=', 0]]); // 非基准 = 一条换算关系

        $linked = $this->goodsSpuRepository->countWithUnitAssigned();

        return [
            'unit_total'   => $unitTotal,
            'conv_total'   => $convTotal,
            'linked_total' => $linked,
            'group_total'  => $groupTotal,
        ];
    }

    /**
     * 换算关系列表（基于每个非基准单位 → 基准单位）
     */
    public function getConversions(array $params = []): array
    {
        // 拉所有启用单位 + 分组
        $where = [['status', '=', 1]];
        if (!empty($params['group_id'])) {
            $where[] = ['group_id', '=', (int)$params['group_id']];
        }
        $rows = $this->goodsUnitRepo->getPageList($where, 1, 1000)['list'];

        // 分组 → 基准单位映射
        $baseMap = [];
        foreach ($rows as $r) {
            if ((int)$r['is_base'] === 1) {
                $baseMap[(int)$r['group_id']] = $r;
            }
        }

        $list = [];
        foreach ($rows as $r) {
            if ((int)$r['is_base'] === 1) continue;
            $base = $baseMap[(int)$r['group_id']] ?? null;
            if (!$base) continue;
            $list[] = [
                'id'          => (int)$r['id'],
                'from_id'     => (int)$r['id'],
                'from_code'   => $r['code'],
                'from_name'   => $r['name'],
                'to_id'       => (int)$base['id'],
                'to_code'     => $base['code'],
                'to_name'     => $base['name'],
                'group_id'    => (int)$r['group_id'],
                'group_name'  => $r['group']['name'] ?? '',
                'group_tone'  => $r['group']['tone'] ?? 'blue',
                'ratio'       => (float)$r['ratio'],
            ];
        }
        return $list;
    }

    public function getDetail(int $id): array
    {
        $result = $this->goodsUnitRepo->find($id);
        if (!$result) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $result;
    }

    /**
     * 创建
     */
    public function create(array $data): array
    {
        $payload = $this->extract($data);

        if (empty($payload['code'])) {
            throw new BusinessException('单位编码必填');
        }
        if ($this->goodsUnitRepo->exists(['code' => $payload['code']])) {
            throw new BusinessException('单位编码已存在');
        }

        // 基准单位互斥：每分组只能有一个基准
        if (!empty($payload['group_id']) && !empty($payload['is_base'])) {
            $this->goodsUnitRepo->clearBaseInGroup((int)$payload['group_id']);
            $payload['ratio'] = 1; // 基准强制为 1
        }

        return $this->goodsUnitRepo->create($payload);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->goodsUnitRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        $payload = $this->extract($data);

        if (isset($payload['code'])) {
            $existing = $this->goodsUnitRepo->findWhere(['code' => $payload['code']]);
            if ($existing && (int)$existing['id'] !== $id) {
                throw new BusinessException('单位编码已存在');
            }
        }

        $groupId = (int)($payload['group_id'] ?? $record['group_id']);
        if ($groupId > 0 && !empty($payload['is_base'])) {
            $this->goodsUnitRepo->clearBaseInGroup($groupId, $id);
            $payload['ratio'] = 1;
        }

        return $this->goodsUnitRepo->update($id, $payload);
    }

    public function delete(int $id): bool
    {
        $record = $this->goodsUnitRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        if ((int)($record['is_base'] ?? 0) === 1) {
            throw new BusinessException('基准单位不可删除，请先转移分组下的其他单位');
        }
        $linked = $this->goodsUnitRepo->countLinkedSpu([$id]);
        if (!empty($linked[$id])) {
            throw new BusinessException("单位已关联 {$linked[$id]} 个商品，无法删除");
        }
        return $this->goodsUnitRepo->delete($id);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $record = $this->goodsUnitRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->goodsUnitRepo->update($id, ['status' => $status]);
    }

    public function batchDelete(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->delete((int)$id);
                $count++;
            } catch (\Exception $e) {
                // skip
            }
        }
        return $count;
    }

    // ========== Private ==========

    private function extract(array $data): array
    {
        $allowed = [
            'code', 'name', 'name_en', 'group_id', 'decimal_places',
            'is_base', 'ratio', 'sort', 'status',
        ];
        return array_intersect_key($data, array_flip($allowed));
    }

    private function buildRatioText(array $row): string
    {
        if ((int)($row['is_base'] ?? 0) === 1) {
            return '基准';
        }
        $base = $this->goodsUnitRepo->findBaseInGroup((int)$row['group_id']);
        if (!$base) {
            return (string)$row['ratio'];
        }
        $ratio = rtrim(rtrim(number_format((float)$row['ratio'], 6, '.', ''), '0'), '.');
        return sprintf('1 %s = %s %s', $row['code'] ?: $row['name'], $ratio, $base['code'] ?: $base['name']);
    }
}
