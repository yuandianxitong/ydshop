<?php
declare(strict_types=1);

namespace app\api\controller\v1\member;

use app\service\member\MemberFavoriteService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '商品收藏', description: '商品收藏列表 + 切换 + 状态查询')]
class FavoriteController extends Controller
{
    protected MemberFavoriteService $favoriteService;

    /**
     * 收藏列表（分页）
     */
    #[OA\Get(
        path: '/favorite',
        summary: '收藏列表',
        security: [['bearerAuth' => []]],
        tags: ['商品收藏'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function index(): Response
    {
        try {
            $userId = $this->getUserId();
            $page   = (int) $this->request->get('page', $this->request->get('page_no', 1));
            $limit  = (int) $this->request->get('limit', $this->request->get('page_size', 15));

            $result = $this->favoriteService->getList($userId, $page, $limit);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 切换收藏状态
     */
    #[OA\Post(
        path: '/favorite/toggle',
        summary: '切换收藏（add/remove）',
        security: [['bearerAuth' => []]],
        tags: ['商品收藏'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['spu_id'],
                properties: [
                    new OA\Property(property: 'spu_id', type: 'integer', description: '商品 SPU ID'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '操作成功，data.favorited=true 表示已收藏', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function toggle(): Response
    {
        try {
            $userId = $this->getUserId();
            $spuId  = (int) $this->request->post('spu_id', 0);

            $isFavorited = $this->favoriteService->toggle($userId, $spuId);
            return $this->success($isFavorited ? '收藏成功' : '已取消收藏', ['favorited' => $isFavorited]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 检查是否已收藏
     */
    #[OA\Get(
        path: '/favorite/check/{spu_id}',
        summary: '检查是否已收藏',
        security: [['bearerAuth' => []]],
        tags: ['商品收藏'],
        parameters: [
            new OA\Parameter(name: 'spu_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '返回 { favorited: boolean }', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function check(): Response
    {
        try {
            $userId = $this->getUserId();
            $spuId  = (int) $this->request->param('spu_id', 0);

            $favorited = $this->favoriteService->isFavorited($userId, $spuId);
            return $this->success('success', ['favorited' => $favorited]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
