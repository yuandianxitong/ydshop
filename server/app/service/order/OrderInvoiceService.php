<?php
declare(strict_types=1);

namespace app\service\order;

use app\repository\order\OrderInvoiceRepository;
use app\repository\order\OrderOrderRepository;
use core\base\Service;
use core\exception\BusinessException;

class OrderInvoiceService extends Service
{
    protected OrderInvoiceRepository $invoiceRepo;
    protected OrderOrderRepository $orderOrderRepository;

    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->invoiceRepo->getPageList($params, $page, $limit);
    }

    public function getStats(): array
    {
        $byStatus = $this->invoiceRepo->countByStatus();
        return [
            'pending'    => $byStatus['pending']    ?? 0,
            'processing' => $byStatus['processing'] ?? 0,
            'issued'     => $byStatus['issued']     ?? 0,
            'cancelled'  => $byStatus['cancelled']  ?? 0,
            'total'      => array_sum($byStatus),
        ];
    }

    public function getDetail(int $id): array
    {
        $row = $this->invoiceRepo->find($id);
        if (!$row) {
            throw new BusinessException('发票申请不存在');
        }
        return $row;
    }

    /**
     * 后台代用户开发票（通常发票由用户在前台申请，后台主要做处理动作）
     */
    public function create(array $data): array
    {
        return $this->invoiceRepo->create($this->extract($data, true));
    }

    /**
     * 进入开票中
     */
    public function process(int $id, string $remark = ''): bool
    {
        $row = $this->getDetail($id);
        $this->assertCanTransition($row['status'], ['pending']);
        return $this->invoiceRepo->update($id, [
            'status'       => 'processing',
            'admin_remark' => $remark,
        ]);
    }

    /**
     * 完成开票
     */
    public function issue(int $id, string $fileUrl, string $remark = ''): bool
    {
        $row = $this->getDetail($id);
        $this->assertCanTransition($row['status'], ['pending', 'processing']);
        if ($fileUrl === '') {
            throw new BusinessException('请上传开票文件');
        }
        return $this->invoiceRepo->update($id, [
            'status'       => 'issued',
            'file_url'     => $fileUrl,
            'admin_remark' => $remark,
            'issued_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 作废
     */
    public function cancel(int $id, string $remark = ''): bool
    {
        $row = $this->getDetail($id);
        $this->assertCanTransition($row['status'], ['pending', 'processing', 'issued']);
        return $this->invoiceRepo->update($id, [
            'status'       => 'cancelled',
            'admin_remark' => $remark,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $this->getDetail($id);
        return $this->invoiceRepo->update($id, $this->extract($data, false));
    }

    public function delete(int $id): bool
    {
        $row = $this->getDetail($id);
        if (in_array($row['status'], ['processing', 'issued'], true)) {
            throw new BusinessException('开票中或已开票的记录不允许删除，请先作废');
        }
        return $this->invoiceRepo->delete($id);
    }

    // ========== User API ==========

    /**
     * 用户提交发票申请（要求订单存在 + 属于该用户 + 该订单尚无发票申请）
     */
    public function createForUser(int $userId, array $data): array
    {
        // 校验订单归属
        $orderId = (int)$data['order_id'];
        $order = $this->orderOrderRepository->find($orderId);
        if (!$order || (int)($order['user_id'] ?? 0) !== $userId) {
            throw new BusinessException('订单不存在或不属于你');
        }
        // 校验订单已付款
        $payAmount = (float)($order['pay_amount'] ?? 0);
        if ($payAmount <= 0) {
            throw new BusinessException('订单未支付，无法申请开票');
        }
        // 一单一票
        $existing = $this->invoiceRepo->findByOrderId($orderId);
        if ($existing) {
            throw new BusinessException('该订单已有开票申请');
        }

        $payload = [
            'order_id'        => $orderId,
            'order_no'        => (string)($order['order_no'] ?? ''),
            'user_id'         => $userId,
            'type'            => (string)$data['type'],
            'invoice_type'    => 'electronic',
            'title'           => trim((string)$data['title']),
            'tax_no'          => trim((string)($data['tax_no'] ?? '')),
            'recipient_email' => trim((string)$data['recipient_email']),
            'content'         => trim((string)($data['content'] ?? '商品明细')),
            'amount'          => (string)$payAmount,
            'status'          => 'pending',
        ];
        try {
            return $this->invoiceRepo->create($payload);
        } catch (\Throwable $e) {
            // duplicate key (uk_order_id) 兜底
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'uk_order_id')) {
                throw new BusinessException('该订单已有开票申请');
            }
            throw $e;
        }
    }

    /**
     * 用户的发票列表（分页）
     */
    public function getUserList(int $userId, int $page, int $limit): array
    {
        return $this->invoiceRepo->paginateByUser($userId, $page, $limit);
    }

    /**
     * 单条发票详情（校验用户归属）
     */
    public function getUserDetail(int $userId, int $id): array
    {
        $row = $this->invoiceRepo->find($id);
        if (!$row || (int)$row['user_id'] !== $userId) {
            throw new BusinessException('发票不存在');
        }
        return $row;
    }

    // ========== Private ==========

    private function extract(array $data, bool $allowKey): array
    {
        $allowed = [
            'order_id', 'order_no', 'user_id',
            'type', 'invoice_type',
            'title', 'tax_no', 'bank_name', 'bank_account',
            'company_address', 'company_phone',
            'recipient_name', 'recipient_phone', 'recipient_email',
            'amount', 'content', 'admin_remark',
        ];
        if ($allowKey) {
            $allowed[] = 'status';
        }
        return array_intersect_key($data, array_flip($allowed));
    }

    private function assertCanTransition(string $current, array $allowed): void
    {
        if (!in_array($current, $allowed, true)) {
            throw new BusinessException("当前状态 [{$current}] 不允许此操作");
        }
    }
}
