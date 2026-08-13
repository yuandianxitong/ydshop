<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsAttributeGroupRepository;
use app\repository\goods\GoodsAttributeRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsAttributeGroupService extends Service
{
    protected GoodsAttributeGroupRepository $goodsAttributeGroupRepo;
    protected GoodsAttributeRepository $goodsAttributeRepository;

    /**
     * 获取列表
     */
    public function getList(array $params): array
    {
        $where = [];

        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['category_id']) && $params['category_id'] !== '') {
            $where[] = ['category_id', '=', (int)$params['category_id']];
        }

        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        return $this->goodsAttributeGroupRepo->getPageList($where, $page, $limit);
    }

    /**
     * 获取详情
     */
    public function getDetail(int $id): array
    {
        $result = $this->goodsAttributeGroupRepo->find($id);
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
        return $this->goodsAttributeGroupRepo->create([
            'name' => $data['name'] ?? '',
            'category_id' => $data['category_id'] ?? '0',
            'sort' => $data['sort'] ?? 0,
        ]);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->goodsAttributeGroupRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'sort' => $data['sort'] ?? null,
        ], fn($v) => $v !== null);

        return $this->goodsAttributeGroupRepo->update($id, $updateData);
    }

    /**
     * 删除
     */
    public function delete(int $id): bool
    {
        $record = $this->goodsAttributeGroupRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->goodsAttributeGroupRepo->delete($id);
    }

    /**
     * 获取分组及其属性（按分类筛选）
     */
    public function getGroupsWithAttributes(int $categoryId = 0): array
    {
        $where = [];
        if ($categoryId > 0) {
            $where[] = ['category_id', '=', $categoryId];
        }
        $groups = $this->goodsAttributeGroupRepo->getAll($where, 'sort asc');
        foreach ($groups as &$group) {
            $group['attributes'] = $this->goodsAttributeRepository->findByGroupId((int)$group['id']);
        }
        return $groups;
    }
}