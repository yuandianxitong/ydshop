<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\member\RechargePackageRepository;
use core\base\Service;

class RechargePackageService extends Service
{
    protected RechargePackageRepository $rechargePackageRepository;

    /**
     * 分页列表
     */
    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 20);
        return $this->rechargePackageRepository->getAdminPageList($params, $page, $limit);
    }

    /**
     * 创建套餐
     */
    public function create(array $data): array
    {
        if (empty($data['amount']) || (float)$data['amount'] <= 0) {
            $this->throwBusinessException('充值金额必须大于0');
        }

        return $this->rechargePackageRepository->create([
            'amount'      => (float)($data['amount'] ?? 0),
            'gift_amount' => (float)($data['gift_amount'] ?? 0),
            'gift_points' => (int)($data['gift_points'] ?? 0),
            'sort'        => (int)($data['sort'] ?? 0),
            'status'      => isset($data['status']) ? (int)$data['status'] : 1,
        ]);
    }

    /**
     * 更新套餐
     */
    public function update(int $id, array $data): array
    {
        $package = $this->rechargePackageRepository->findModel($id);
        if (!$package) {
            $this->throwBusinessException('套餐不存在');
        }

        if (isset($data['amount']) && (float)$data['amount'] <= 0) {
            $this->throwBusinessException('充值金额必须大于0');
        }

        $package->save([
            'amount'      => isset($data['amount'])      ? (float)$data['amount']      : $package->amount,
            'gift_amount' => isset($data['gift_amount'])  ? (float)$data['gift_amount']  : $package->gift_amount,
            'gift_points' => isset($data['gift_points'])  ? (int)$data['gift_points']    : $package->gift_points,
            'sort'        => isset($data['sort'])         ? (int)$data['sort']            : $package->sort,
            'status'      => isset($data['status'])       ? (int)$data['status']          : $package->status,
        ]);

        return $package->toArray();
    }

    /**
     * 删除套餐（软删除）
     */
    public function delete(int $id): void
    {
        if (!$this->rechargePackageRepository->find($id)) {
            $this->throwBusinessException('套餐不存在');
        }
        $this->rechargePackageRepository->delete($id);
    }

    /**
     * 获取启用中的套餐列表（C端用）
     */
    public function getActiveList(): array
    {
        return $this->rechargePackageRepository->getActiveList();
    }
}
