<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\adminapi\validate\v1\order\OrderRefundReasonValidate;
use app\service\order\OrderRefundReasonService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单退款原因', description: '退款原因字典 CRUD')]
class OrderRefundReasonController extends Controller
{
    protected OrderRefundReasonService $orderRefundReasonService;

    #[Permission('order.refund-reason.list')]
    #[OA\Get(
        path: '/order/refund-reason',
        summary: '退款原因列表',
        security: [['bearerAuth' => []]],
        tags: ['订单退款原因'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function index(): Response { return $this->success('获取成功', $this->orderRefundReasonService->getList()); }

    #[Permission('order.refund-reason.create')]
    #[OA\Post(
        path: '/order/refund-reason',
        summary: '创建退款原因',
        security: [['bearerAuth' => []]],
        tags: ['订单退款原因'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '原因名称'),
                    new OA\Property(property: 'sort', type: 'integer'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data   = $this->validate($this->request->post(), OrderRefundReasonValidate::class, [], false, 'store');
        return $this->success('创建成功', $this->orderRefundReasonService->create($data));
    }

    #[Permission('order.refund-reason.update')]
    #[OA\Put(
        path: '/order/refund-reason/{id}',
        summary: '更新退款原因',
        security: [['bearerAuth' => []]],
        tags: ['订单退款原因'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate((array) $this->request->put(), OrderRefundReasonValidate::class, [], false, 'update');
        $this->orderRefundReasonService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('order.refund-reason.delete')]
    #[OA\Delete(
        path: '/order/refund-reason/{id}',
        summary: '删除退款原因',
        security: [['bearerAuth' => []]],
        tags: ['订单退款原因'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->orderRefundReasonService->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }
}
