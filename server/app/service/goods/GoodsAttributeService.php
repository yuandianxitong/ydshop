<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsAttributeRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsAttributeService extends Service
{
    protected GoodsAttributeRepository $goodsAttributeRepo;

    /**
     * 获取列表
     */
    public function getList(array $params): array
    {
        $where = [];

        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }

        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        return $this->goodsAttributeRepo->getPageList($where, $page, $limit);
    }

    /**
     * 获取详情
     */
    public function getDetail(int $id): array
    {
        $result = $this->goodsAttributeRepo->find($id);
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
        return $this->goodsAttributeRepo->create([
            'group_id' => $data['group_id'] ?? 0,
            'name'     => $data['name'] ?? '',
            'type'     => $data['type'] ?? 'input',
            // Model create 会走 options => json 类型转换
            'options'  => $this->normalizeOptions($data['options'] ?? null),
            'sort'     => $data['sort'] ?? 0,
        ]);
    }

    /**
     * 更新
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->goodsAttributeRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        $updateData = array_filter([
            'group_id' => $data['group_id'] ?? null,
            'name'     => $data['name'] ?? null,
            'type'     => $data['type'] ?? null,
            'sort'     => $data['sort'] ?? null,
        ], static fn ($v) => $v !== null);

        if (array_key_exists('options', $data)) {
            $opts = $this->normalizeOptions($data['options']);
            // Query::update 跳过 Model json 转换，需显式写入合法 JSON / NULL
            $updateData['options'] = $opts === null
                ? null
                : json_encode(array_values($opts), JSON_UNESCAPED_UNICODE);
        }

        return $this->goodsAttributeRepo->update($id, $updateData);
    }

    /**
     * 删除
     */
    public function delete(int $id): bool
    {
        $record = $this->goodsAttributeRepo->find($id);
        if (!$record) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->goodsAttributeRepo->delete($id);
    }

    /**
     * 将前端 options（数组 / JSON 字符串 / 逗号分隔）规范为字符串列表
     *
     * @return list<string>|null
     */
    private function normalizeOptions(mixed $options): ?array
    {
        if ($options === null || $options === '') {
            return null;
        }

        if (is_array($options)) {
            $parts = array_values(array_filter(
                array_map(static fn ($v) => trim((string)$v), $options),
                static fn ($v) => $v !== ''
            ));
            return $parts === [] ? null : $parts;
        }

        if (is_string($options)) {
            $decoded = json_decode($options, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeOptions($decoded);
            }

            $parts = preg_split('/[,，]/u', $options) ?: [];
            $parts = array_values(array_filter(
                array_map('trim', $parts),
                static fn ($v) => $v !== ''
            ));
            return $parts === [] ? null : $parts;
        }

        return null;
    }
}
