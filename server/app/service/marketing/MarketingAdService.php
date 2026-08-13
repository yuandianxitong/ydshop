<?php
declare(strict_types=1);

namespace app\service\marketing;

use app\repository\marketing\MarketingAdPositionRepository;
use app\repository\marketing\MarketingAdRepository;
use core\base\Service;
use core\exception\BusinessException;

class MarketingAdService extends Service
{
    protected MarketingAdRepository $adRepository;
    protected MarketingAdPositionRepository $adPositionRepository;

    public function getList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->adRepository->getSearchList($params, $page, $limit);
    }

    public function detail(int $id): ?array
    {
        return $this->adRepository->find($id);
    }

    public function create(array $data): array
    {
        $this->validateData($data);
        return $this->adRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->adRepository->find($id);
        if (!$existing) throw new BusinessException('广告不存在');
        $this->validateData($data, $id);
        return $this->adRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->adRepository->delete($id);
    }

    public function batchStatus(array $ids, int $status): int
    {
        if (!in_array($status, [0, 1], true)) {
            throw new BusinessException('status 必须为 0 或 1');
        }
        return $this->adRepository->batchUpdateStatus($ids, $status);
    }

    /**
     * C 端公开接口入口
     * 永不抛错 —— 异常返回空数据，由控制器层兜底
     */
    public function getPublicByPositionCode(string $code): array
    {
        $result = $this->adRepository->getActiveByPositionCode($code);
        $position = $result['position'];
        $ads = $result['ads'];

        // 剥除 internal 字段
        if ($position) {
            $position = [
                'code'               => $position['code'],
                'name'               => $position['name'],
                'is_carousel'        => (int)$position['is_carousel'],
                'recommended_width'  => (int)$position['recommended_width'],
                'recommended_height' => (int)$position['recommended_height'],
            ];
        }
        $ads = array_map(fn($a) => [
            'id'    => (int)$a['id'],
            'image' => $a['image'],
            'link'  => $a['link'] ?? '',
        ], $ads);

        return ['position' => $position, 'ads' => $ads];
    }

    private function validateData(array $data, ?int $editingId = null): void
    {
        $positionId = (int)($data['position_id'] ?? 0);
        if (!$positionId) throw new BusinessException('请选择广告位');

        $position = $this->adPositionRepository->find($positionId);
        if (!$position || (int)$position['status'] !== 1) throw new BusinessException('广告位不存在或已停用');

        $start = $data['start_at'] ?? null;
        $end   = $data['end_at'] ?? null;
        if ($start && $end && strtotime($start) >= strtotime($end)) {
            throw new BusinessException('上架时间不能晚于下架时间');
        }
    }
}
