<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\model\system\SystemConfig;
use app\repository\delivery\DeliveryOrderRepository;
use core\base\Service;

class MapDataService extends Service
{
    protected DeliveryOrderRepository $deliveryOrderRepository;
    protected AmapService $amapService;

    public function getMapConfig(): array
    {
        return [
            'js_api_key'       => (string)SystemConfig::getConfigValue('amap.js_api_key', ''),
            'js_security_code' => (string)SystemConfig::getConfigValue('amap.js_security_code', ''),
            'default_city'     => (string)SystemConfig::getConfigValue('amap.default_city', '北京'),
        ];
    }

    /**
     * 实时地图订单数据：lazy geocode + 缓存写回
     *
     * @param array $filters statuses[], since
     * @return array 仅含有效坐标的订单（geocode 失败的过滤掉）
     */
    public function getOrdersForMap(array $filters): array
    {
        $rows = $this->deliveryOrderRepository->getForMap($filters);

        // 找出缺坐标但有 address 的，batch geocode
        $missingIdx  = [];
        $missingAddr = [];
        foreach ($rows as $i => $r) {
            if (($r['dest_lat'] === null || $r['dest_lng'] === null) && $r['address'] !== '') {
                $missingIdx[]  = $i;
                $missingAddr[] = $r['address'];
            }
        }

        if (!empty($missingAddr)) {
            $coords = $this->amapService->batchGeocode($missingAddr);
            foreach ($missingIdx as $k => $i) {
                $coord = $coords[$k] ?? null;
                if ($coord) {
                    $this->deliveryOrderRepository->updateGeoCoords(
                        $rows[$i]['id'],
                        $coord['lat'],
                        $coord['lng']
                    );
                    $rows[$i]['dest_lat'] = $coord['lat'];
                    $rows[$i]['dest_lng'] = $coord['lng'];
                }
            }
        }

        // 仅返回有坐标的（geocode 失败的舍弃）
        return array_values(array_filter(
            $rows,
            fn($r) => $r['dest_lat'] !== null && $r['dest_lng'] !== null
        ));
    }
}
