<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\order\OrderRefundReason;
use core\base\Repository;
use think\Model as ThinkModel;

class OrderRefundReasonRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new OrderRefundReason();
    }

    /**
     * Get all reasons ordered by sort.
     */
    public function getAll(array $where = [], string $order = 'sort asc'): array
    {
        return OrderRefundReason::order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * Get active (status=1) reasons ordered by sort.
     */
    public function getActiveList(): array
    {
        return OrderRefundReason::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * Create a new reason.
     */
    public function create(array $data): array
    {
        return OrderRefundReason::create($data)->toArray();
    }

    /**
     * Update a reason by ID.
     */
    public function update($id, array $data): bool
    {
        /** @var OrderRefundReason|null $reason */
        $reason = OrderRefundReason::find($id);
        if (!$reason) {
            return false;
        }
        return $reason->save($data);
    }

    /**
     * Delete a reason by ID.
     */
    public function delete($id): bool
    {
        /** @var OrderRefundReason|null $reason */
        $reason = OrderRefundReason::find($id);
        if (!$reason) {
            return false;
        }
        return (bool) $reason->delete();
    }
}
