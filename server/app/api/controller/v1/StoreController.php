<?php
declare(strict_types=1);

namespace app\api\controller\v1;

use app\service\store\StoreService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '门店', description: '自提门店列表与详情（公开）')]
class StoreController extends Controller
{
    protected StoreService $storeService;

    #[OA\Get(
        path: '/store',
        summary: '门店列表（按距离排序）',
        tags: ['门店'],
        parameters: [
            new OA\Parameter(name: 'lng', in: 'query', description: '当前位置经度', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'lat', in: 'query', description: '当前位置纬度', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'goods_id', in: 'query', description: '商品 ID（筛选支持自提的门店）', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function list(): Response
    {
        $params  = $this->request->get();
        $lng     = isset($params['lng']) ? (float)$params['lng'] : null;
        $lat     = isset($params['lat']) ? (float)$params['lat'] : null;
        $goodsId = isset($params['goods_id']) ? (int)$params['goods_id'] : null;
        $list    = $this->storeService->listByDistance($lng, $lat, $goodsId);
        return $this->success('success', ['list' => $list]);
    }

    #[OA\Get(
        path: '/store/{id}',
        summary: '门店详情',
        tags: ['门店'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '门店不存在', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): Response
    {
        $id     = (int)$this->request->param('id');
        $detail = $this->storeService->detail($id);
        if (!$detail) {
            return $this->error('门店不存在');
        }
        return $this->success('success', $detail);
    }
}
