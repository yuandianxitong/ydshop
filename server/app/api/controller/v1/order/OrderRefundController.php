<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\api\controller\v1\order;

use app\api\validate\v1\order\OrderRefundValidate;
use app\service\order\OrderRefundService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单退款', description: '用户申请退款及退货物流提交')]
class OrderRefundController extends Controller
{
    protected OrderRefundService $orderRefundService;

    /**
     * 我的售后列表
     */
    #[OA\Get(
        path: '/order-refund',
        summary: '我的售后列表',
        security: [['bearerAuth' => []]],
        tags: ['订单退款'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: '退款状态（pending/approved/returning/received/refunding/refunded/rejected 等）', schema: new OA\Schema(type: 'string')),
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
            $params = $this->request->only(['status', 'page', 'limit']);
            $result = $this->orderRefundService->getMyList($this->getUserId(), $params);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 我的售后详情
     */
    #[OA\Get(
        path: '/order-refund/{id}',
        summary: '我的售后详情',
        security: [['bearerAuth' => []]],
        tags: ['订单退款'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '退款单 ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '退款记录不存在', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): Response
    {
        try {
            $id     = (int) $this->request->param('id');
            $detail = $this->orderRefundService->getMyDetail($this->getUserId(), $id);
            return $this->success('success', $detail);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 申请退款
     */
    #[OA\Post(
        path: '/order-refund/apply',
        summary: '申请退款',
        security: [['bearerAuth' => []]],
        tags: ['订单退款'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_item_id'],
                properties: [
                    new OA\Property(property: 'order_item_id', type: 'integer', description: '订单商品行 ID'),
                    new OA\Property(property: 'refund_amount', type: 'number', format: 'float', description: '整行退款的净实付金额（可不传，传入时必须与后端分摊金额一致）'),
                    new OA\Property(property: 'type', type: 'string', enum: ['refund_only', 'return_refund', 'refund_with_goods'], description: '退款类型：仅退款 / 退货退款（refund_with_goods 为兼容别名）'),
                    new OA\Property(property: 'reason', type: 'string', description: '退款原因（最多 120 字）'),
                    new OA\Property(property: 'description', type: 'string', description: '问题描述（最多 500 字）'),
                    new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string'), description: '凭证图片 URL 数组'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '退款申请已提交', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '状态不允许退款 / 校验失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function apply(): Response
    {
        try {
            $data   = $this->validate($this->request->post(), OrderRefundValidate::class, [], false, 'apply');
            $refund = $this->orderRefundService->apply($this->getUserId(), $data);
            return $this->success('退款申请已提交', $refund);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 提交退货物流信息
     */
    #[OA\Post(
        path: '/order-refund/{id}/logistics',
        summary: '提交退货物流',
        security: [['bearerAuth' => []]],
        tags: ['订单退款'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '退款单 ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company', 'express_no'],
                properties: [
                    new OA\Property(property: 'company', type: 'string', description: '快递公司'),
                    new OA\Property(property: 'express_no', type: 'string', description: '快递单号'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '物流信息已提交', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '当前退款状态不允许填写退货物流', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function submitLogistics(): Response
    {
        try {
            $data = $this->validate($this->request->post(), OrderRefundValidate::class, [], false, 'submit_logistics');
            $id   = (int) $this->request->param('id');
            $this->orderRefundService->submitReturnLogistics($id, $this->getUserId(), (string)$data['company'], (string)$data['express_no']);
            return $this->success('物流信息已提交');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
