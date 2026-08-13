<?php
declare(strict_types=1);

namespace app\repository;

use app\model\Store;
use core\base\Repository;
use think\Model as ThinkModel;

class StoreRepository extends Repository
{
    protected function getModel(): ThinkModel
    {
        return new Store();
    }

    /**
     * 管理端分页列表，支持 keyword / status 过滤
     */
    public function paginate(array $params, int $page = 1, int $size = 15): array
    {
        $query = Store::where('deleted_at', null);

        if (!empty($params['keyword'])) {
            $kw = $params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->whereLike('name', "%{$kw}%")
                  ->whereOr('code', 'like', "%{$kw}%")
                  ->whereOr('address', 'like', "%{$kw}%")
                  ->whereOr('phone', 'like', "%{$kw}%");
            });
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        $total = $query->count();
        $list  = $query->order('sort asc, id desc')->page($page, $size)->select()->toArray();

        return $this->buildPagination($list, $page, $size, $total);
    }

    /**
     * 用户端门店列表，按距离排序（Haversine 代码侧计算）
     *
     * @param float|null $lng 用户经度（GCJ-02）
     * @param float|null $lat 用户纬度（GCJ-02）
     * @param int|null   $goodsId 商品 ID（预留，当前不过滤；详情页配送行 cell 计数用）
     */
    public function listByDistance(?float $lng, ?float $lat, ?int $goodsId = null): array
    {
        unset($goodsId); // 预留参数，当前未做按商品过滤
        $rows = Store::where('status', 1)->order('sort asc, id desc')->select()->toArray();

        if ($lng !== null && $lat !== null) {
            foreach ($rows as &$row) {
                $row['distance'] = $this->haversine(
                    $lat, $lng,
                    (float) $row['lat'], (float) $row['lng']
                );
            }
            unset($row);
            usort($rows, fn($a, $b) => $a['distance'] <=> $b['distance']);
        } else {
            foreach ($rows as &$row) {
                $row['distance'] = null;
            }
            unset($row);
        }

        return $rows;
    }

    /**
     * 查找未删除的门店，返回纯数组（不让 ORM 实例泄漏到 Service 层）
     */
    public function findActive(int $id): ?array
    {
        $result = Store::where('deleted_at', null)->find($id);
        return $result ? $result->toArray() : null;
    }

    /**
     * 软删除门店（写 deleted_at，不物理删除）
     */
    public function softDelete(int $id): bool
    {
        return Store::where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /**
     * 按 code 查找门店（返回模型实例，供 Service 直接操作）
     */
    public function findByCode(string $code): ?Store
    {
        return Store::where('code', $code)->find() ?: null;
    }

    /**
     * 启用中、含有效经纬度的门店（同城配送选最近发货点用）
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveWithCoordinates(): array
    {
        return Store::where('status', 1)
            ->where('lng', '>', 0)
            ->where('lat', '>', 0)
            ->select()
            ->toArray();
    }

    /**
     * Haversine 球面距离（单位：km）
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r    = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round(2 * $r * asin(sqrt($a)), 3);
    }
}
