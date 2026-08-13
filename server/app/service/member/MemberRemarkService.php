<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberRemarkRepository;
use app\repository\system\AdminRepository;
use core\base\Service;
use core\exception\BusinessException;

class MemberRemarkService extends Service
{
    protected MemberRemarkRepository $remarkRepository;
    protected AdminRepository $adminRepository;

    public function listByUser(int $userId): array
    {
        return $this->remarkRepository->listByUserId($userId);
    }

    public function add(int $userId, string $content, ?int $operatorId): array
    {
        $content = trim($content);
        if ($content === '') {
            throw new BusinessException('备注内容不能为空');
        }
        $operatorName = '';
        if ($operatorId) {
            $admin = $this->adminRepository->find($operatorId);
            $operatorName = (string)($admin['nickname'] ?? $admin['username'] ?? '');
        }
        return $this->remarkRepository->create([
            'user_id'       => $userId,
            'content'       => mb_substr($content, 0, 500),
            'operator_id'   => $operatorId,
            'operator_name' => $operatorName,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $userId, int $id): bool
    {
        $row = $this->remarkRepository->find($id);
        if (!$row || (int)$row['user_id'] !== $userId) {
            throw new BusinessException('备注不存在');
        }
        return $this->remarkRepository->delete($id);
    }
}
