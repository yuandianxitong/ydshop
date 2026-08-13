<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\delivery\WaybillTemplateRepository;
use core\base\Service;
use core\exception\BusinessException;

class WaybillTemplateService extends Service
{
    protected WaybillTemplateRepository $waybillTemplateRepository;

    /** @return array<string, mixed> */
    public function getCatalog(): array
    {
        $path = root_path() . 'config' . DIRECTORY_SEPARATOR . 'kdniao_waybill_catalog.php';
        if (!is_file($path)) {
            return [];
        }
        $data = require $path;
        return is_array($data) ? $data : [];
    }

    public function getList(array $params): array
    {
        $where = [];
        if (!empty($params['keyword'])) {
            $keyword = trim((string)$params['keyword']);
            $where[] = ['name|express_name|express_code', 'like', '%' . $keyword . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        if (!empty($params['express_code'])) {
            $where[] = ['express_code', '=', trim((string)$params['express_code'])];
        }
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = max(1, min(100, (int)($params['limit'] ?? 15)));
        return $this->waybillTemplateRepository->getPageList($where, $page, $limit);
    }

    /** @return list<array<string, mixed>> */
    public function getOptions(): array
    {
        return $this->waybillTemplateRepository->getEnabledOptions();
    }

    public function getDetail(int $id): array
    {
        $row = $this->waybillTemplateRepository->find($id);
        if (!$row) {
            throw new BusinessException('面单模版不存在');
        }
        return $row;
    }

    public function create(array $data): array
    {
        $payload = $this->normalize($data);
        return $this->runInTransaction(function () use ($payload) {
            if ((int)($payload['is_default'] ?? 0) === 1) {
                $this->waybillTemplateRepository->clearDefault();
            }
            return $this->waybillTemplateRepository->create($payload);
        });
    }

    public function update(int $id, array $data): bool
    {
        $this->getDetail($id);
        $payload = $this->normalize($data, false);
        return $this->runInTransaction(function () use ($id, $payload) {
            if ((int)($payload['is_default'] ?? 0) === 1) {
                $this->waybillTemplateRepository->clearDefault($id);
            }
            return $this->waybillTemplateRepository->update($id, $payload);
        });
    }

    public function updateStatus(int $id, int $status): bool
    {
        $this->getDetail($id);
        return $this->waybillTemplateRepository->update($id, ['status' => $status ? 1 : 0]);
    }

    public function delete(int $id): bool
    {
        $this->getDetail($id);
        return $this->waybillTemplateRepository->delete($id);
    }

    /** @param array<string, mixed> $data */
    private function normalize(array $data, bool $creating = true): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $expressCode = strtoupper(trim((string)($data['express_code'] ?? '')));
        $expressName = trim((string)($data['express_name'] ?? ''));
        $expType = trim((string)($data['exp_type'] ?? '1'));
        if ($name === '' || $expressCode === '') {
            throw new BusinessException('模版名称和物流公司不能为空');
        }
        if ($expType === '') {
            throw new BusinessException('业务类型不能为空');
        }

        $catalog = $this->getCatalog();
        $carrier = $catalog[$expressCode] ?? null;
        if ($expressName === '' && is_array($carrier)) {
            $expressName = (string)($carrier['name'] ?? $expressCode);
        }

        $expTypeName = trim((string)($data['exp_type_name'] ?? ''));
        if ($expTypeName === '' && is_array($carrier)) {
            foreach ($carrier['exp_types'] ?? [] as $item) {
                if ((string)($item['value'] ?? '') === $expType) {
                    $expTypeName = (string)($item['label'] ?? $expType);
                    break;
                }
            }
        }
        if ($expTypeName === '') {
            $expTypeName = $expType;
        }

        $templateSize = trim((string)($data['template_size'] ?? ''));
        $templateSizeLabel = trim((string)($data['template_size_label'] ?? ''));
        if ($templateSizeLabel === '' && is_array($carrier)) {
            foreach ($carrier['template_sizes'] ?? [] as $item) {
                if ((string)($item['value'] ?? '') === $templateSize) {
                    $templateSizeLabel = (string)($item['label'] ?? ($templateSize === '' ? '默认模版' : $templateSize));
                    break;
                }
            }
        }
        if ($templateSizeLabel === '') {
            $templateSizeLabel = $templateSize === '' ? '默认模版' : $templateSize;
        }

        $payload = [
            'name' => $name,
            'express_code' => $expressCode,
            'express_name' => $expressName !== '' ? $expressName : $expressCode,
            'exp_type' => $expType,
            'exp_type_name' => $expTypeName,
            'template_size' => $templateSize,
            'template_size_label' => $templateSizeLabel,
            'pay_type' => max(1, min(3, (int)($data['pay_type'] ?? 1))),
            'need_pickup' => isset($data['need_pickup']) ? ((int)$data['need_pickup'] ? 1 : 0) : 0,
            'is_default' => isset($data['is_default']) ? ((int)$data['is_default'] ? 1 : 0) : 0,
            'status' => isset($data['status']) ? ((int)$data['status'] ? 1 : 0) : 1,
            'sort' => (int)($data['sort'] ?? 0),
        ];

        if (!$creating) {
            return array_filter($payload, static fn ($v) => $v !== null);
        }
        return $payload;
    }
}
