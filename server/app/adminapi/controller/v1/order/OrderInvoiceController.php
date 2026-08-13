<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\adminapi\validate\v1\order\OrderInvoiceValidate;
use app\service\order\OrderInvoiceService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单发票管理', description: '发票申请审批与开具')]
class OrderInvoiceController extends Controller
{
    protected OrderInvoiceService $orderInvoiceService;

    #[Permission('order.invoice.list')]
    #[OA\Get(
        path: '/order/invoice',
        summary: '发票申请列表',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', description: 'pending/processing/issued/cancelled', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->orderInvoiceService->getList($this->getRequestData())); }

    #[Permission('order.invoice.list')]
    #[OA\Get(
        path: '/order/invoice/stats',
        summary: '发票状态计数',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        responses: [new OA\Response(response: 200, description: '获取成功，含 pending/processing/issued/cancelled/total', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function stats(): Response { return $this->success('获取成功', $this->orderInvoiceService->getStats()); }

    #[Permission('order.invoice.list')]
    #[OA\Get(
        path: '/order/invoice/{id}',
        summary: '发票详情',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response { return $this->success('获取成功', $this->orderInvoiceService->getDetail((int)$this->request->param('id'))); }

    #[Permission('order.invoice.update')]
    #[OA\Post(
        path: '/order/invoice/{id}/process',
        summary: '进入开票中',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [new OA\Property(property: 'admin_remark', type: 'string')])
        ),
        responses: [new OA\Response(response: 200, description: '已进入开票中', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function process(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), OrderInvoiceValidate::class, [], false, 'process');
        $this->orderInvoiceService->process($id, (string)($data['admin_remark'] ?? ''));
        return $this->success('已进入开票中');
    }

    #[Permission('order.invoice.update')]
    #[OA\Post(
        path: '/order/invoice/{id}/issue',
        summary: '开具发票',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['file_url'],
                properties: [
                    new OA\Property(property: 'file_url', type: 'string', format: 'url', description: '发票文件 URL'),
                    new OA\Property(property: 'admin_remark', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '开票完成', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function issue(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), OrderInvoiceValidate::class, [], false, 'issue');
        $this->orderInvoiceService->issue($id, (string)$data['file_url'], (string)($data['admin_remark'] ?? ''));
        return $this->success('开票完成');
    }

    #[Permission('order.invoice.update')]
    #[OA\Post(
        path: '/order/invoice/{id}/cancel',
        summary: '作废发票申请',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [new OA\Property(property: 'admin_remark', type: 'string')])
        ),
        responses: [new OA\Response(response: 200, description: '已作废', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function cancel(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), OrderInvoiceValidate::class, [], false, 'cancel');
        $this->orderInvoiceService->cancel($id, (string)($data['admin_remark'] ?? ''));
        return $this->success('已作废');
    }

    #[Permission('order.invoice.update')]
    #[OA\Put(
        path: '/order/invoice/{id}',
        summary: '修改发票申请',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), OrderInvoiceValidate::class, [], false, 'update');
        $this->orderInvoiceService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('order.invoice.delete')]
    #[OA\Delete(
        path: '/order/invoice/{id}',
        summary: '删除发票申请',
        security: [['bearerAuth' => []]],
        tags: ['订单发票管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->orderInvoiceService->delete((int)$this->request->param('id'));
        return $this->success('删除成功');
    }
}
