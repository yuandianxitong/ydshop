<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\delivery\DeliveryShiftRepository;
use app\repository\delivery\DeliveryStaffRepository;
use core\base\Service;
use core\exception\BusinessException;

class DeliveryShiftService extends Service
{
    protected DeliveryShiftRepository $deliveryShiftRepository;
    protected DeliveryStaffRepository $deliveryStaffRepository;

    public function getList(array $filters): array
    {
        $page  = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 15)));
        return $this->deliveryShiftRepository->getPageList($filters, $page, $limit);
    }

    public function getDetail(int $id): array
    {
        $shift = $this->deliveryShiftRepository->findById($id);
        if (!$shift) {
            throw new BusinessException('班次不存在');
        }
        return $shift;
    }

    public function create(array $data): int
    {
        $this->ensureStaffExists((int)$data['staff_id']);
        $this->validateTime((string)$data['start_time'], (string)$data['end_time']);

        $payload = [
            'staff_id'   => (int)$data['staff_id'],
            'weekday'    => (int)$data['weekday'],
            'start_time' => (string)$data['start_time'],
            'end_time'   => (string)$data['end_time'],
            'remark'     => (string)($data['remark'] ?? ''),
        ];
        $result = $this->deliveryShiftRepository->create($payload);
        $id     = (int)($result['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('班次创建失败');
        }
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $shift = $this->deliveryShiftRepository->findById($id);
        if (!$shift) {
            throw new BusinessException('班次不存在');
        }
        if (isset($data['staff_id'])) {
            $this->ensureStaffExists((int)$data['staff_id']);
        }
        $start = $data['start_time'] ?? $shift['start_time'];
        $end   = $data['end_time']   ?? $shift['end_time'];
        $this->validateTime((string)$start, (string)$end);

        $patch = [];
        foreach (['staff_id', 'weekday', 'start_time', 'end_time', 'remark'] as $f) {
            if (array_key_exists($f, $data)) {
                $patch[$f] = $data[$f];
            }
        }
        return $this->deliveryShiftRepository->updateById($id, $patch);
    }

    public function delete(int $id): bool
    {
        $shift = $this->deliveryShiftRepository->findById($id);
        if (!$shift) {
            throw new BusinessException('班次不存在');
        }
        return $this->deliveryShiftRepository->softDelete($id);
    }

    private function validateTime(string $start, string $end): void
    {
        if (strcmp($start, $end) >= 0) {
            throw new BusinessException('上班时间必须早于下班时间（不支持跨午夜）');
        }
    }

    private function ensureStaffExists(int $staffId): void
    {
        if ($staffId <= 0 || !$this->deliveryStaffRepository->find($staffId)) {
            throw new BusinessException('配送员不存在');
        }
    }
}
