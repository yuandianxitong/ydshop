<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\api\controller\v1\order;

use app\api\validate\v1\order\OrderReviewValidate;
use app\service\order\OrderReviewService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单评价', description: '订单评价提交与查询')]
class OrderReviewController extends Controller
{
    protected OrderReviewService $orderReviewService;

    /**
     * 提交评价（需登录）
     */
    #[OA\Post(
        path: '/order-review',
        summary: '提交评价',
        security: [['bearerAuth' => []]],
        tags: ['订单评价'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_item_id', 'rating'],
                properties: [
                    new OA\Property(property: 'order_item_id', type: 'integer', description: '订单商品行 ID'),
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5, description: '评分 1~5'),
                    new OA\Property(property: 'content', type: 'string', description: '评价内容（最多 500 字）'),
                    new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string'), description: '图片 URL 数组'),
                    new OA\Property(property: 'is_anonymous', type: 'integer', enum: [0, 1], description: '是否匿名'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '评价成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '订单未完成或已评价', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function create(): Response
    {
        try {
            $data   = $this->validate($this->request->post(), OrderReviewValidate::class, [], false, 'create');
            $review = $this->orderReviewService->create($this->getUserId(), $data);
            return $this->success('评价成功', $review);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 按 SPU 获取评价列表（无需登录）
     */
    #[OA\Get(
        path: '/order-review/spu/{spu_id}',
        summary: '商品评价列表',
        tags: ['订单评价'],
        parameters: [
            new OA\Parameter(name: 'spu_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function listBySpu(): Response
    {
        try {
            $spuId = (int) $this->request->param('spu_id');
            $page  = (int) $this->request->get('page', $this->request->get('page_no', 1));
            $limit = (int) $this->request->get('limit', $this->request->get('page_size', 15));

            $result = $this->orderReviewService->getListBySpu($spuId, $page, $limit);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
