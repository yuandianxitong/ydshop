<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsUnitGroupRepository;
use app\repository\goods\GoodsUnitRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsUnitGroupService extends Service
{
    protected GoodsUnitGroupRepository $groupRepo;
    protected GoodsUnitRepository $unitRepo;

    /**
     * 列表（带每分组单位数）
     */
    public function getList(): array
    {
        return $this->groupRepo->getListWithCount();
    }

    public function create(array $data): array
    {
        $payload = $this->extract($data);
        if (!empty($payload['code']) && $this->groupRepo->exists(['code' => $payload['code']])) {
            throw new BusinessException('分组编码已存在');
        }
        return $this->groupRepo->create($payload);
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->groupRepo->find($id)) {
            throw new BusinessException('分组不存在');
        }
        $payload = $this->extract($data);
        if (!empty($payload['code'])) {
            $existing = $this->groupRepo->findWhere(['code' => $payload['code']]);
            if ($existing && (int)$existing['id'] !== $id) {
                throw new BusinessException('分组编码已存在');
            }
        }
        return $this->groupRepo->update($id, $payload);
    }

    public function delete(int $id): bool
    {
        $linked = $this->unitRepo->count(['group_id' => $id]);
        if ($linked > 0) {
            throw new BusinessException("分组下还有 {$linked} 个单位，请先转移或删除");
        }
        return $this->groupRepo->delete($id);
    }

    private function extract(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'code', 'name', 'tone', 'sort', 'status',
        ]));
    }
}
