<?php
declare(strict_types=1);

namespace app\api\controller\v1\marketing;

use core\base\Controller;
use app\service\marketing\MarketingAdService;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '营销-广告', description: 'C 端广告查询（公开）')]
class MarketingAdController extends Controller
{
    protected MarketingAdService $adService;

    /**
     * GET /api/ad/by-position/:code
     * 获取指定位置的广告列表（公开）
     * 永不抛错 —— 任何异常返回空数据，避免广告挂掉影响首页
     */
    #[OA\Get(
        path: '/ad/by-position/{code}',
        summary: '获取指定广告位的广告列表（公开）',
        tags: ['营销-广告'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function byPosition(): Response
    {
        $code = (string)$this->request->param('code');
        try {
            $data = $this->adService->getPublicByPositionCode($code);
        } catch (\Throwable $e) {
            $data = ['position' => null, 'ads' => []];
        }
        return $this->success('success', $data);
    }
}
