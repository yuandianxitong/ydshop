<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\delivery;

use app\service\delivery\MapDataService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '配送地图', description: '配送实时地图 — 高德 Key + 订单坐标')]
class DeliveryMapController extends Controller
{
    protected MapDataService $mapDataService;

    /**
     * 前端地图初始化用：返回 JS API Key + 安全码 + 默认城市
     */
    #[Permission('delivery.order.list')]
    #[OA\Get(
        path: '/delivery/map/config',
        summary: '地图初始化配置',
        security: [['bearerAuth' => []]],
        tags: ['配送地图'],
        description: '返回 amap_js_key / amap_security_code / default_city 等',
        responses: [new OA\Response(response: 200, description: 'success', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function config(): Response { return $this->success('success', $this->mapDataService->getMapConfig()); }

    /**
     * 地图订单数据（含 lazy geocode）
     */
    #[Permission('delivery.order.list')]
    #[OA\Get(
        path: '/delivery/map/orders',
        summary: '地图订单坐标',
        security: [['bearerAuth' => []]],
        tags: ['配送地图'],
        parameters: [
            new OA\Parameter(name: 'statuses', in: 'query', description: '逗号分隔状态过滤', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'since', in: 'query', description: '时间起点（默认今天 0 点）', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'success', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function orders(): Response
    {
        $statusesParam = $this->request->get('statuses');
        $statuses      = is_string($statusesParam) && $statusesParam !== ''
            ? array_values(array_filter(explode(',', $statusesParam)))
            : [];

        $since = (string)$this->request->get('since', '');
        if ($since === '') {
            $since = date('Y-m-d 00:00:00');
        }

        return $this->success('success', $this->mapDataService->getOrdersForMap([
            'statuses' => $statuses,
            'since'    => $since,
        ]));
    }
}
