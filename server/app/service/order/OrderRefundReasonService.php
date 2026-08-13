<?php
declare(strict_types=1);

namespace app\service\order;

use app\repository\order\OrderRefundReasonRepository;
use core\base\Service;
use core\exception\BusinessException;

class OrderRefundReasonService extends Service
{
    protected OrderRefundReasonRepository $orderRefundReasonRepository;

    /**
     * Get all reasons (admin).
     */
    public function getList(): array
    {
        return $this->orderRefundReasonRepository->getAll();
    }

    /**
     * Get active reasons (C端).
     */
    public function getActiveList(): array
    {
        return $this->orderRefundReasonRepository->getActiveList();
    }

    /**
     * Create a refund reason.
     */
    public function create(array $data): array
    {
        $reason = $this->orderRefundReasonRepository->create([
            'name'   => $data['name'] ?? '',
            'sort'   => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ]);
        return $reason;
    }

    /**
     * Update a refund reason.
     */
    public function update(int $id, array $data): void
    {
        $result = $this->orderRefundReasonRepository->update($id, [
            'name'   => $data['name'] ?? '',
            'sort'   => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ]);
        if (!$result) {
            throw new BusinessException('退款原因不存在');
        }
    }

    /**
     * Delete a refund reason.
     */
    public function delete(int $id): void
    {
        $result = $this->orderRefundReasonRepository->delete($id);
        if (!$result) {
            throw new BusinessException('退款原因不存在');
        }
    }
}
