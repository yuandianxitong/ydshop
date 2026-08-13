<?php
declare(strict_types=1);

namespace app\service\store;

use app\repository\order\OrderOrderRepository;
use app\repository\StoreRepository;
use core\base\Service;
use core\exception\BusinessException;

class StoreService extends Service
{
    protected StoreRepository $storeRepository;
    protected OrderOrderRepository $orderOrderRepository;

    /**
     * 允许写入的字段白名单（避免 admin 把详情接口返回的虚拟字段
     * is_open_now / status_text / created_at 等回传上来导致 ThinkPHP 报 fields not exists）
     */
    private const FILLABLE = [
        'name', 'code',
        'address', 'province', 'city', 'district', 'detail', 'region_code',
        'lng', 'lat', 'phone',
        'business_hours', 'status', 'sort', 'remark',
    ];

    private function pickFillable(array $data): array
    {
        return array_intersect_key($data, array_flip(self::FILLABLE));
    }

    public function paginate(array $params, int $page = 1, int $size = 15): array
    {
        return $this->storeRepository->paginate($params, $page, $size);
    }

    public function detail(int $id): ?array
    {
        return $this->storeRepository->findActive($id);
    }

    public function listByDistance(?float $lng, ?float $lat, ?int $goodsId = null): array
    {
        return $this->storeRepository->listByDistance($lng, $lat, $goodsId);
    }

    public function create(array $data): int
    {
        $data = $this->pickFillable($data);
        $data['created_at'] = date('Y-m-d H:i:s');
        $row = $this->storeRepository->create($data);
        return (int)($row['id'] ?? 0);
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->pickFillable($data);
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->storeRepository->update($id, $data);
    }

    public function softDelete(int $id): bool
    {
        return $this->storeRepository->softDelete($id);
    }

    /**
     * 生成 6 位数字自提码，scope = 同门店 pending 订单中 unique
     * @throws BusinessException 3 次重试仍冲突时抛
     */
    public function generatePickupCode(int $storeId): string
    {
        for ($i = 0; $i < 3; $i++) {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (!$this->orderOrderRepository->existsPendingPickupCode($storeId, $code)) {
                return $code;
            }
        }
        throw new BusinessException('自提码生成失败，请重试');
    }
}
