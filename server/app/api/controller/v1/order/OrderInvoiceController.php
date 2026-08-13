<?php
declare(strict_types=1);

namespace app\api\controller\v1\order;

use app\api\validate\v1\order\OrderInvoiceValidate;
use app\service\order\OrderInvoiceService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单发票', description: '用户开票申请与查询')]
class OrderInvoiceController extends Controller
{
    protected OrderInvoiceService $orderInvoiceService;

    /**
     * 用户提交开票申请
     */
    #[OA\Post(
        path: '/order/invoice',
        summary: '提交开票申请',
        security: [['bearerAuth' => []]],
        tags: ['订单发票'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'type', 'title', 'recipient_email'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer', description: '订单 ID'),
                    new OA\Property(property: 'type', type: 'string', enum: ['personal', 'company'], description: '抬头类型'),
                    new OA\Property(property: 'title', type: 'string', description: '发票抬头（最多 120 字）'),
                    new OA\Property(property: 'tax_no', type: 'string', description: '税号（type=company 时必填）'),
                    new OA\Property(property: 'recipient_email', type: 'string', format: 'email', description: '接收邮箱'),
                    new OA\Property(property: 'content', type: 'string', description: '开票内容（默认"商品明细"）'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '提交成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '订单未支付 / 该订单已申请 / 校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function submit(): Response
    {
        $userId = $this->getUserId();
        $data   = $this->request->post();
        $this->validate($data, OrderInvoiceValidate::class, [], false, 'submit');

        try {
            $result = $this->orderInvoiceService->createForUser($userId, $data);
            return $this->success('提交成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 我的发票列表
     */
    #[OA\Get(
        path: '/order/invoice',
        summary: '我的发票申请列表',
        security: [['bearerAuth' => []]],
        tags: ['订单发票'],
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
        $userId = $this->getUserId();
        $page   = (int)$this->request->param('page', 1);
        $limit  = (int)$this->request->param('limit', 15);
        try {
            $result = $this->orderInvoiceService->getUserList($userId, $page, $limit);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 单条发票详情
     */
    #[OA\Get(
        path: '/order/invoice/{id}',
        summary: '发票详情',
        security: [['bearerAuth' => []]],
        tags: ['订单发票'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '发票不存在或无权访问', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): Response
    {
        $userId = $this->getUserId();
        $id     = (int)$this->request->param('id');
        try {
            $result = $this->orderInvoiceService->getUserDetail($userId, $id);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
