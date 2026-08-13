<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\adminapi\validate\v1\order\OrderRefundValidate;
use app\service\order\OrderRefundService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单退款管理', description: '退款申请审批 + 收货确认')]
class OrderRefundController extends Controller
{
    protected OrderRefundService $orderRefundService;

    #[Permission('order.refund.list')]
    #[OA\Get(
        path: '/order/refund',
        summary: '退款申请列表',
        security: [['bearerAuth' => []]],
        tags: ['订单退款管理'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->orderRefundService->getList($this->getRequestData())); }

    #[Permission('order.refund.list')]
    #[OA\Get(
        path: '/order/refund/{id}',
        summary: '退款详情',
        security: [['bearerAuth' => []]],
        tags: ['订单退款管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response { return $this->success('获取成功', $this->orderRefundService->getDetail((int)$this->request->param('id'))); }

    #[Permission('order.refund.approve')]
    #[OA\Post(
        path: '/order/refund/{id}/approve',
        summary: '同意退款',
        security: [['bearerAuth' => []]],
        tags: ['订单退款管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [new OA\Property(property: 'admin_remark', type: 'string')])
        ),
        responses: [new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function approve(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), OrderRefundValidate::class, [], false, 'approve');
        $this->orderRefundService->approve($id, (string)($data['admin_remark'] ?? ''));
        return $this->success('操作成功');
    }

    #[Permission('order.refund.reject')]
    #[OA\Post(
        path: '/order/refund/{id}/reject',
        summary: '驳回退款',
        security: [['bearerAuth' => []]],
        tags: ['订单退款管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refuse_reason'],
                properties: [new OA\Property(property: 'refuse_reason', type: 'string', description: '驳回原因')]
            )
        ),
        responses: [new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function reject(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), OrderRefundValidate::class, [], false, 'reject');
        $this->orderRefundService->reject($id, (string)$data['refuse_reason']);
        return $this->success('操作成功');
    }

    #[Permission('order.refund.approve')]
    #[OA\Post(
        path: '/order/refund/{id}/confirm-received',
        summary: '确认收到退货',
        security: [['bearerAuth' => []]],
        tags: ['订单退款管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function confirmReceived(): Response
    {
        $this->orderRefundService->confirmReceived((int)$this->request->param('id'));
        return $this->success('操作成功');
    }

    #[Permission('order.refund.approve')]
    #[OA\Post(
        path: '/order/refund/{id}/retry',
        summary: '重试/同步处理中的退款',
        security: [['bearerAuth' => []]],
        tags: ['订单退款管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function retry(): Response
    {
        $this->orderRefundService->retryRefund((int)$this->request->param('id'));
        return $this->success('退款结果已同步');
    }
}
