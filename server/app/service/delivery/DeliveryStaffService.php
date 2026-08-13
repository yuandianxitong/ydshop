<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\delivery\DeliveryStaffRepository;
use app\service\common\ExcelExportService;
use core\base\Service;
use core\exception\BusinessException;
use think\facade\Log;

class DeliveryStaffService extends Service
{
    protected DeliveryStaffRepository $deliveryStaffRepo;
    protected ExcelExportService $excelExportService;

    public function getList(array $params): array
    {
        $where = [];
        if (!empty($params['keyword'])) {
            $where[] = ['name|phone', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->deliveryStaffRepo->getList($where, $page, $limit, 'id desc');
    }

    public function getOptions(): array
    {
        return $this->deliveryStaffRepo->getAll([['status', '=', 1]], 'id asc');
    }

    public function getDetail(int $id): array
    {
        $result = $this->deliveryStaffRepo->find($id);
        if (!$result) {
            throw new BusinessException('配送员不存在');
        }
        return $result;
    }

    public function create(array $data): array
    {
        return $this->deliveryStaffRepo->create([
            'name'   => $data['name'] ?? '',
            'phone'  => $data['phone'] ?? '',
            'status' => $data['status'] ?? 1,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->deliveryStaffRepo->find($id);
        if (!$record) {
            throw new BusinessException('配送员不存在');
        }
        $updateData = array_filter([
            'name'   => $data['name'] ?? null,
            'phone'  => $data['phone'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($v) => $v !== null);
        return $this->deliveryStaffRepo->update($id, $updateData);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $record = $this->deliveryStaffRepo->find($id);
        if (!$record) {
            throw new BusinessException('配送员不存在');
        }
        return $this->deliveryStaffRepo->update($id, ['status' => $status]);
    }

    public function delete(int $id): bool
    {
        $record = $this->deliveryStaffRepo->find($id);
        if (!$record) {
            throw new BusinessException('配送员不存在');
        }
        return $this->deliveryStaffRepo->delete($id);
    }

    public function batchDelete(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->delete((int)$id);
                $count++;
            } catch (\Exception $e) {
                Log::warning('批量删除配送员失败', ['id' => $id, 'message' => $e->getMessage()]);
            }
        }
        return $count;
    }

    /**
     * 导出配送员花名册 xlsx
     */
    public function exportXlsx(array $params): \think\Response
    {
        $where = [];
        if (!empty($params['keyword'])) {
            $where[] = ['name|phone', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }

        $rows = $this->deliveryStaffRepo->getAllForExport($where, ExcelExportService::MAX_ROWS);

        $headers = ['ID', '姓名', '手机', '状态', '创建时间', '更新时间'];
        $data = array_map(fn($r) => [
            $r['id'],
            $r['name'] ?? '',
            $r['phone'] ?? '',
            ((int)($r['status'] ?? 0)) === 1 ? '启用' : '禁用',
            $r['created_at'] ?? '',
            $r['updated_at'] ?? '',
        ], $rows);

        return $this->excelExportService->streamXlsx(
            '配送员花名册_' . date('Ymd_His'),
            $headers,
            $data
        );
    }
}
