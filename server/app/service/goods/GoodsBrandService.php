<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsBrandRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsBrandService extends Service
{
    protected GoodsBrandRepository $goodsBrandRepo;

    /**
     * 获取列表
     */
    public function getList(array $params): array
    {
        $where = [];

        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }

        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        return $this->goodsBrandRepo->getPageList($where, $page, $limit);
    }

    /**
     * 获取详情
     */
    public function getDetail(int $id): array
    {
        $result = $this->goodsBrandRepo->find($id);
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
        return $this->goodsBrandRepo->create([
            'name' => $data['name'] ?? '',
            'logo' => $data['logo'] ?? '',
            'description' => $data['description'] ?? '',
            'sort' => $data['sort'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->goodsBrandRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'logo' => $data['logo'] ?? null,
            'description' => $data['description'] ?? null,
            'sort' => $data['sort'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($v) => $v !== null);

        return $this->goodsBrandRepo->update($id, $updateData);
    }

    /**
     * 删除
     */
    public function delete(int $id): bool
    {
        $record = $this->goodsBrandRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->goodsBrandRepo->delete($id);
    }

    /**
     * 更新状态
     */
    public function updateStatus(int $id, int $status): bool
    {
        $record = $this->goodsBrandRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->goodsBrandRepo->update($id, ['status' => $status]);
    }
}