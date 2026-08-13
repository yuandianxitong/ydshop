<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\delivery\DeliveryExceptionTicketRepository;
use core\base\Service;
use core\exception\BusinessException;

class DeliveryExceptionTicketService extends Service
{
    protected DeliveryExceptionTicketRepository $deliveryExceptionTicketRepository;

    public function getList(array $filters): array
    {
        $page  = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 15)));
        return $this->deliveryExceptionTicketRepository->getPageList($filters, $page, $limit);
    }

    public function getDetail(int $id): array
    {
        $ticket = $this->deliveryExceptionTicketRepository->findById($id);
        if (!$ticket) {
            throw new BusinessException('工单不存在');
        }
        return $ticket;
    }

    public function create(array $data): int
    {
        $payload = [
            'ticket_no'         => $this->deliveryExceptionTicketRepository->generateTicketNo(),
            'type'              => $data['type'],
            'status'            => 'open',
            'delivery_order_id' => !empty($data['delivery_order_id']) ? (int)$data['delivery_order_id'] : null,
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'evidence'          => $data['evidence'] ?? null,
            'reporter'          => $data['reporter'] ?? null,
            'contact'           => $data['contact'] ?? null,
        ];
        $result = $this->deliveryExceptionTicketRepository->create($payload);
        $id     = (int)($result['id'] ?? 0);
        if ($id <= 0) {
            throw new BusinessException('工单创建失败：未获取到主键');
        }
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $ticket = $this->deliveryExceptionTicketRepository->findById($id);
        if (!$ticket) {
            throw new BusinessException('工单不存在');
        }
        $patch = [];
        foreach (['title', 'description', 'type', 'evidence', 'reporter', 'contact', 'delivery_order_id'] as $f) {
            if (array_key_exists($f, $data)) {
                $patch[$f] = $data[$f];
            }
        }
        if (empty($patch)) {
            return true;
        }
        return $this->deliveryExceptionTicketRepository->updateById($id, $patch);
    }

    public function transition(int $id, string $to, ?string $resolutionNote, int $adminId): bool
    {
        $ticket = $this->deliveryExceptionTicketRepository->findById($id);
        if (!$ticket) {
            throw new BusinessException('工单不存在');
        }
        $this->validateTransition((string)$ticket['status'], $to);

        $patch = ['status' => $to];
        if ($to === 'resolved') {
            if (empty($resolutionNote) || mb_strlen($resolutionNote) < 5) {
                throw new BusinessException('解决工单需填写处理说明（至少 5 个字）');
            }
            $patch['resolution_note'] = $resolutionNote;
            $patch['handled_by']      = $adminId;
            $patch['handled_at']      = date('Y-m-d H:i:s');
        }
        return $this->deliveryExceptionTicketRepository->updateById($id, $patch);
    }

    public function delete(int $id): bool
    {
        $ticket = $this->deliveryExceptionTicketRepository->findById($id);
        if (!$ticket) {
            throw new BusinessException('工单不存在');
        }
        return $this->deliveryExceptionTicketRepository->softDelete($id);
    }

    private function validateTransition(string $from, string $to): void
    {
        $allowed = [
            'open'        => ['in_progress'],
            'in_progress' => ['resolved'],
            'resolved'    => ['closed'],
            'closed'      => [],
        ];
        if (!in_array($to, $allowed[$from] ?? [], true)) {
            throw new BusinessException("非法状态流转：{$from} → {$to}");
        }
    }
}
