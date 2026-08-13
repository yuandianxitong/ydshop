<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsSpecTemplateRepository;
use core\base\Service;
use core\exception\BusinessException;

class GoodsSpecTemplateService extends Service
{
    protected GoodsSpecTemplateRepository $goodsSpecTemplateRepo;

    public function getList(array $params): array
    {
        $where = [];

        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
            $where[] = ['status', '=', (int)$params['status']];
        }

        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        return $this->goodsSpecTemplateRepo->getPageList($where, $page, $limit);
    }

    public function getDetail(int $id): array
    {
        $result = $this->goodsSpecTemplateRepo->find($id);
        if (!$result) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $result;
    }

    public function create(array $data): array
    {
        return $this->goodsSpecTemplateRepo->create([
            'name'        => trim((string)($data['name'] ?? '')),
            'description' => trim((string)($data['description'] ?? '')),
            'items'       => $this->normalizeItems($data['items'] ?? []),
            'sort'        => (int)($data['sort'] ?? 0),
            'status'      => isset($data['status']) ? (int)$data['status'] : 1,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->goodsSpecTemplateRepo->find($id)) {
            throw new BusinessException(lang('business.record_not_found'));
        }

        $update = [];
        if (array_key_exists('name', $data))        $update['name']        = trim((string)$data['name']);
        if (array_key_exists('description', $data)) $update['description'] = trim((string)$data['description']);
        if (array_key_exists('items', $data))       $update['items']       = $this->normalizeItems($data['items']);
        if (array_key_exists('sort', $data))        $update['sort']        = (int)$data['sort'];
        if (array_key_exists('status', $data))      $update['status']      = (int)$data['status'];

        return $this->goodsSpecTemplateRepo->update($id, $update);
    }

    public function delete(int $id): bool
    {
        if (!$this->goodsSpecTemplateRepo->find($id)) {
            throw new BusinessException(lang('business.record_not_found'));
        }
        return $this->goodsSpecTemplateRepo->delete($id);
    }

    /**
     * 规整 items：去空 name、去空/重复 values，丢弃名为空或 values 为空的项
     *
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    private function normalizeItems(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }
        $out = [];
        foreach ($items as $row) {
            if (!is_array($row)) continue;
            $name = trim((string)($row['name'] ?? ''));
            $values = $row['values'] ?? [];
            if (!is_array($values)) continue;
            $cleanValues = [];
            foreach ($values as $v) {
                $sv = trim((string)$v);
                if ($sv !== '' && !in_array($sv, $cleanValues, true)) {
                    $cleanValues[] = $sv;
                }
            }
            if ($name !== '' && $cleanValues) {
                $out[] = ['name' => $name, 'values' => $cleanValues];
            }
        }
        return $out;
    }
}
