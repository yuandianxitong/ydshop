<?php
declare(strict_types=1);

namespace app\service\marketing;

use app\repository\marketing\MarketingAdPositionRepository;
use app\repository\marketing\MarketingAdRepository;
use core\base\Service;
use core\exception\BusinessException;
use think\facade\Db;

class MarketingAdPositionService extends Service
{
    protected MarketingAdPositionRepository $adPositionRepository;
    protected MarketingAdRepository $adRepository;

    public function getList(array $params): array
    {
        [$page, $limit] = $this->extractPagination($params);
        return $this->adPositionRepository->getSearchList($params, $page, $limit);
    }

    public function getAllActive(): array
    {
        return $this->adPositionRepository->getAllActive();
    }

    public function detail(int $id): ?array
    {
        return $this->adPositionRepository->find($id);
    }

    public function create(array $data): array
    {
        $code = trim((string)($data['code'] ?? ''));
        if ($this->adPositionRepository->findByCode($code)) {
            throw new BusinessException('广告位编码已存在');
        }
        return $this->adPositionRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $existing = $this->adPositionRepository->find($id);
        if (!$existing) throw new BusinessException('广告位不存在');

        // 拒绝修改 code —— code 是 C 端契约，改 code 会让 DIY 配置失效
        if (isset($data['code']) && $data['code'] !== $existing['code']) {
            throw new BusinessException('广告位编码不可修改');
        }
        unset($data['code']); // 即使前端传入也无视

        return $this->adPositionRepository->update($id, $data);
    }

    /**
     * 删除广告位 —— 同事务级联软删该位置下所有广告
     */
    public function delete(int $id): bool
    {
        $existing = $this->adPositionRepository->find($id);
        if (!$existing) throw new BusinessException('广告位不存在');

        Db::startTrans();
        try {
            $this->adRepository->softDeleteByPositionId($id);
            $this->adPositionRepository->delete($id);
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }
}
