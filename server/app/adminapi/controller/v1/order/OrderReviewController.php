<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\service\order\OrderReviewService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单评价管理', description: '评价列表 + 商家回复')]
class OrderReviewController extends Controller
{
    protected OrderReviewService $orderReviewService;

    #[Permission('order.review.list')]
    #[OA\Get(
        path: '/order/review',
        summary: '评价列表',
        security: [['bearerAuth' => []]],
        tags: ['订单评价管理'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'rating', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 5)),
            new OA\Parameter(name: 'has_reply', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->orderReviewService->getAdminList($this->getRequestData())); }

    #[Permission('order.review.reply')]
    #[OA\Post(
        path: '/order/review/{id}/reply',
        summary: '商家回复评价',
        security: [['bearerAuth' => []]],
        tags: ['订单评价管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'reply_content', type: 'string')])
        ),
        responses: [new OA\Response(response: 200, description: '回复成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function reply(): Response
    {
        $id      = (int) $this->request->param('id');
        $content = (string) $this->request->post('reply_content', '');
        $this->orderReviewService->reply($id, $content);
        return $this->success('回复成功');
    }
}
