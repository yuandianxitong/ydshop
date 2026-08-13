<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberAddressRepository;
use core\base\Service;
use core\exception\BusinessException;

class MemberAddressService extends Service
{
    protected MemberAddressRepository $memberAddressRepository;

    /**
     * 获取用户所有收货地址（不分页，用户地址通常 <20 条）
     */
    public function getList(int $userId): array
    {
        return $this->memberAddressRepository->findAllByUser($userId);
    }

    /**
     * 新增收货地址
     * 若 is_default=1，先将该用户其他地址的 is_default 置为 0
     */
    public function create(int $userId, array $data): array
    {
        $data['user_id'] = $userId;

        return $this->runInTransaction(function () use ($userId, $data) {
            if (!empty($data['is_default'])) {
                $this->memberAddressRepository->clearDefaults($userId);
            }
            return $this->memberAddressRepository->create($data);
        });
    }

    /**
     * 更新收货地址（验证归属）
     * 若 is_default=1，先将该用户其他地址的 is_default 置为 0
     */
    public function update(int $addressId, int $userId, array $data): array
    {
        $existing = $this->getOwned($addressId, $userId);

        return $this->runInTransaction(function () use ($addressId, $userId, $data, $existing) {
            if (!empty($data['is_default'])) {
                $this->memberAddressRepository->clearDefaults($userId, $addressId);
            }
            $this->memberAddressRepository->update($addressId, $data);
            return $this->memberAddressRepository->find($addressId) ?? $existing;
        });
    }

    /**
     * 软删除收货地址（验证归属）
     */
    public function delete(int $addressId, int $userId): bool
    {
        $this->getOwned($addressId, $userId);
        return $this->memberAddressRepository->delete($addressId);
    }

    /**
     * 获取默认地址；若无默认则返回第一条地址
     */
    public function getDefault(int $userId): ?array
    {
        return $this->memberAddressRepository->findDefaultOrFirst($userId);
    }

    // ========== Private Helpers ==========

    private function getOwned(int $addressId, int $userId): array
    {
        $address = $this->memberAddressRepository->findByIdAndUser($addressId, $userId);
        if (!$address) {
            throw new BusinessException('地址不存在或无权操作');
        }
        return $address;
    }
}
