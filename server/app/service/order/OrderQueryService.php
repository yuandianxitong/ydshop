<?php
declare(strict_types=1);

namespace app\service\order;

use app\model\order\OrderOrder;
use app\repository\order\OrderAdjustLogRepository;
use app\repository\order\OrderOrderRepository;
use app\repository\order\OrderRefundRepository;
use core\base\Service;
use core\exception\BusinessException;

/**
 * 订单查询 Service —— 只读路径
 *
 * 从原 OrderService 中拆出，与下单/取消/核销等写操作隔离，
 * 后续如需上读写分离或读缓存可以独立演进。
 */
class OrderQueryService extends Service
{
    protected OrderOrderRepository $orderOrderRepo;
    protected OrderRefundRepository $orderRefundRepository;
    protected OrderAdjustLogRepository $orderAdjustLogRepository;

    /**
     * 获取订单列表
     */
    public function getList(array $params = []): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->orderOrderRepo->getPageList($params, $page, $limit);
    }

    /**
     * 获取订单详情
     */
    public function getDetail(int $id): array
    {
        $detail = $this->orderOrderRepo->getDetail($id);
        if (!$detail) {
            throw new BusinessException('订单不存在');
        }
        return $this->appendLineage($detail);
    }

    /**
     * 订单调整日志（拆分/合并/改价），按时间正序。
     *
     * 现有日志表无 join 管理员姓名先例（admin_operation_logs 直接冗余
     * username），这里按契约直接返回 admin_id。
     */
    public function getAdjustLogs(int $orderId): array
    {
        return $this->orderAdjustLogRepository->getByOrderId($orderId);
    }

    /**
     * 附加拆合血缘字段（管理端详情用）。
     *
     * - split_from_order_id/no：本单为拆分子单时的父单
     * - merged_into_order_id/no：本单被合并时的存活单
     * - split_child_orders：从本单拆出的子单列表
     * - merged_from_orders：合并进本单的来源单列表
     *
     * method_exists 防御：血缘 Repository 方法与新列由并行改造提供，
     * 集成前不阻塞既有详情接口。
     *
     * @param array<string, mixed> $detail
     */
    private function appendLineage(array $detail): array
    {
        $detail['split_from_order_id'] = null;
        $detail['split_from_order_no'] = null;
        $detail['merged_into_order_id'] = null;
        $detail['merged_into_order_no'] = null;
        $detail['split_child_orders'] = [];
        $detail['merged_from_orders'] = [];

        $relationType = (string)($detail['relation_type'] ?? '');
        $parentOrderId = (int)($detail['parent_order_id'] ?? 0);
        if ($parentOrderId > 0 && in_array($relationType, ['split_child', 'merge_absorbed'], true)) {
            $parent = $this->orderOrderRepo->find($parentOrderId);
            if ($relationType === 'split_child') {
                $detail['split_from_order_id'] = $parentOrderId;
                $detail['split_from_order_no'] = (string)($parent['order_no'] ?? '');
            } else {
                $detail['merged_into_order_id'] = $parentOrderId;
                $detail['merged_into_order_no'] = (string)($parent['order_no'] ?? '');
            }
        }

        if (method_exists($this->orderOrderRepo, 'findChildrenByParentId')) {
            foreach ($this->orderOrderRepo->findChildrenByParentId((int)$detail['id']) as $child) {
                $row = [
                    'id'       => (int)$child['id'],
                    'order_no' => (string)($child['order_no'] ?? ''),
                ];
                if (($child['relation_type'] ?? '') === 'split_child') {
                    $detail['split_child_orders'][] = $row;
                } elseif (($child['relation_type'] ?? '') === 'merge_absorbed') {
                    $detail['merged_from_orders'][] = $row;
                }
            }
        }

        return $detail;
    }

    /**
     * 按 id 或 order_no 取当前用户的订单详情。
     *
     * C 端详情查询必须在 Repository 层同时限定 user_id，避免先查出
     * 订单再在 Service 层判断所有者导致越权数据暴露。不存在与不属于当前
     * 用户统一返回“订单不存在”，不泄露订单是否存在。
     *
     * order_no 形态：长度 >= 14（yyyymmddHHmmss + 随机后缀）
     */
    public function getUserDetailByIdOrNo(int $userId, string $idOrNo): array
    {
        $detail = strlen($idOrNo) >= 14
            ? $this->orderOrderRepo->getDetailByOrderNoAndUser($idOrNo, $userId)
            : $this->orderOrderRepo->getDetailByIdAndUser((int)$idOrNo, $userId);

        if (!$detail) {
            throw new BusinessException('订单不存在');
        }
        return $detail;
    }

    /**
     * 断言订单属于当前用户（轻量归属校验，物流轨迹等只读接口用）。
     *
     * 与详情查询同策略：不存在与不属于当前用户统一抛「订单不存在」。
     */
    public function assertOwnedByUser(int $userId, int $orderId): void
    {
        if (!$this->orderOrderRepo->findByIdAndUser($orderId, $userId)) {
            throw new BusinessException('订单不存在');
        }
    }

    /**
     * 当前用户各状态订单数（我的页 quick entry 上标用）
     *
     * 返回 { pending, paid, shipped, refunding } —— "已完成" 不打标
     */
    public function getCountsForUser(int $userId): array
    {
        $counts = $this->orderOrderRepo->countByUserAndStatuses($userId, [
            OrderOrder::STATUS_PENDING,
            OrderOrder::STATUS_PAID,
            OrderOrder::STATUS_SHIPPED,
        ]);
        return [
            'pending'   => $counts[OrderOrder::STATUS_PENDING] ?? 0,
            'paid'      => $counts[OrderOrder::STATUS_PAID] ?? 0,
            'shipped'   => $counts[OrderOrder::STATUS_SHIPPED] ?? 0,
            // 退款 / 售后：active states（申请中 / 审核通过待退款 / 退货中 / 退款中）
            'refunding' => $this->orderRefundRepository->countActiveByUserId($userId),
        ];
    }
}
