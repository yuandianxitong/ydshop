<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberAddressRepository;
use app\service\common\ExcelExportService;
use core\base\Service;
use core\exception\BusinessException;

class AddressBookService extends Service
{
    protected MemberAddressRepository $addressRepo;
    protected ExcelExportService $excelExportService;

    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        return $this->addressRepo->getPageList($params, $page, $limit);
    }

    public function getStats(): array
    {
        return $this->addressRepo->getStats();
    }

    public function delete(int $id): bool
    {
        $row = $this->addressRepo->find($id);
        if (!$row) {
            throw new BusinessException('地址不存在');
        }
        return $this->addressRepo->delete($id);
    }

    public function create(array $data): array
    {
        $payload = $this->normalize($data, true);
        if (!empty($payload['is_default']) && !empty($payload['user_id'])) {
            $this->addressRepo->setDefault(0, (int)$payload['user_id']); // 清掉旧默认
        }
        return $this->addressRepo->create($payload);
    }

    public function update(int $id, array $data): bool
    {
        $row = $this->addressRepo->find($id);
        if (!$row) {
            throw new BusinessException('地址不存在');
        }
        $payload = $this->normalize($data, false);
        if (!empty($payload['is_default'])) {
            $this->addressRepo->setDefault($id, (int)$row['user_id']);
            unset($payload['is_default']);
        }
        return empty($payload) ? true : $this->addressRepo->update($id, $payload);
    }

    public function setDefault(int $id): bool
    {
        $row = $this->addressRepo->find($id);
        if (!$row) {
            throw new BusinessException('地址不存在');
        }
        return $this->addressRepo->setDefault($id, (int)$row['user_id']);
    }

    private function normalize(array $data, bool $isCreate): array
    {
        $allowed = ['user_id', 'name', 'phone', 'province', 'city', 'district', 'detail', 'lng', 'lat', 'region_code', 'is_default'];
        $out = [];
        foreach ($allowed as $k) {
            if ($isCreate || array_key_exists($k, $data)) {
                $out[$k] = $data[$k] ?? null;
            }
        }
        if ($isCreate) {
            if (empty($out['user_id'])) throw new BusinessException('user_id 不能为空');
            if (empty($out['name']))    throw new BusinessException('收货人不能为空');
            if (empty($out['phone']))   throw new BusinessException('手机号不能为空');
        }
        if (isset($out['is_default'])) {
            $out['is_default'] = $out['is_default'] ? 1 : 0;
        }
        return $out;
    }

    /**
     * 导出会员地址簿 xlsx
     */
    public function exportXlsx(array $params): \think\Response
    {
        $rows = $this->addressRepo->getAllForExport($params, ExcelExportService::MAX_ROWS);
        $headers = ['ID', '会员', '会员手机', '收货人', '收货手机', '省', '市', '区', '详细地址', '是否默认', '创建时间'];
        $data = array_map(fn($r) => [
            $r['id'],
            $r['user_nickname'] ?? '-',
            $r['user_mobile'] ?? '',
            $r['name'] ?? '',
            $r['phone'] ?? '',
            $r['province'] ?? '',
            $r['city'] ?? '',
            $r['district'] ?? '',
            $r['detail'] ?? '',
            ($r['is_default'] ?? 0) ? '是' : '否',
            $r['created_at'] ?? '',
        ], $rows);
        return $this->excelExportService->streamXlsx(
            '会员地址簿_' . date('Ymd_His'),
            $headers,
            $data
        );
    }
}
