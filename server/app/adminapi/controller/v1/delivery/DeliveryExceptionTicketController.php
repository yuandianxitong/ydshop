<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\delivery;

use app\adminapi\validate\v1\delivery\DeliveryExceptionTicketValidate;
use app\service\delivery\DeliveryExceptionTicketService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '配送异常工单', description: '配送异常工单管理与状态流转')]
class DeliveryExceptionTicketController extends Controller
{
    protected DeliveryExceptionTicketService $deliveryExceptionTicketService;

    #[Permission('delivery.exception.list')]
    #[OA\Get(
        path: '/delivery/exception',
        summary: '异常工单列表',
        security: [['bearerAuth' => []]],
        tags: ['配送异常工单'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->deliveryExceptionTicketService->getList($this->getRequestData())); }

    #[Permission('delivery.exception.list')]
    #[OA\Get(
        path: '/delivery/exception/{id}',
        summary: '异常工单详情',
        security: [['bearerAuth' => []]],
        tags: ['配送异常工单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'success', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response { return $this->success('success', $this->deliveryExceptionTicketService->getDetail((int)$this->request->param('id'))); }

    #[Permission('delivery.exception.create')]
    #[OA\Post(
        path: '/delivery/exception',
        summary: '创建异常工单',
        security: [['bearerAuth' => []]],
        tags: ['配送异常工单'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'order_id', type: 'integer'),
                new OA\Property(property: 'type', type: 'string', description: '异常类型'),
                new OA\Property(property: 'description', type: 'string'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, DeliveryExceptionTicketValidate::class, [], false, 'create');
        $id = $this->deliveryExceptionTicketService->create($data);
        return $this->success('创建成功', ['id' => $id]);
    }

    #[Permission('delivery.exception.update')]
    #[OA\Put(
        path: '/delivery/exception/{id}',
        summary: '更新异常工单',
        security: [['bearerAuth' => []]],
        tags: ['配送异常工单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->request->put();
        $this->validate($data, DeliveryExceptionTicketValidate::class, [], false, 'update');
        $this->deliveryExceptionTicketService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('delivery.exception.update')]
    #[OA\Put(
        path: '/delivery/exception/{id}/transition',
        summary: '工单状态流转',
        security: [['bearerAuth' => []]],
        tags: ['配送异常工单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['to'],
                properties: [
                    new OA\Property(property: 'to', type: 'string', description: '目标状态'),
                    new OA\Property(property: 'resolution_note', type: 'string', description: '处理备注'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '状态更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function transition(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->request->put();
        $this->validate($data, DeliveryExceptionTicketValidate::class, [], false, 'transition');

        $this->deliveryExceptionTicketService->transition(
            $id,
            (string)$data['to'],
            $data['resolution_note'] ?? null,
            (int)$this->getUserId()
        );
        return $this->success('状态更新成功');
    }

    #[Permission('delivery.exception.delete')]
    #[OA\Delete(
        path: '/delivery/exception/{id}',
        summary: '删除异常工单',
        security: [['bearerAuth' => []]],
        tags: ['配送异常工单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function destroy(): Response
    {
        $this->deliveryExceptionTicketService->delete((int)$this->request->param('id'));
        return $this->success('删除成功');
    }
}
