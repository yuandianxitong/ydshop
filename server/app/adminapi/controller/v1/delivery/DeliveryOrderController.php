<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\delivery;

use app\adminapi\validate\v1\delivery\DeliveryOrderValidate;
use app\service\delivery\DeliveryOrderService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '同城配送订单', description: '同城配送订单分配 / 状态推进 / 自动派单 / 导出')]
class DeliveryOrderController extends Controller
{
    protected DeliveryOrderService $deliveryOrderService;

    #[Permission('delivery.order.list')]
    #[OA\Get(
        path: '/delivery/order',
        summary: '配送订单列表',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->deliveryOrderService->getList($this->getRequestData())); }

    #[Permission('delivery.order.update')]
    #[OA\Post(
        path: '/delivery/order/{id}/assign',
        summary: '分配配送员',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'staff_id', type: 'integer')])
        ),
        responses: [new OA\Response(response: 200, description: '分配成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function assign(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), DeliveryOrderValidate::class, [], false, 'assign');
        $this->deliveryOrderService->assign($id, (int)$data['staff_id']);
        return $this->success('分配成功');
    }

    #[Permission('delivery.order.update')]
    #[OA\Post(
        path: '/delivery/order/{id}/status',
        summary: '更新配送状态',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', enum: ['picking', 'delivering', 'completed', 'cancelled'])])
        ),
        responses: [new OA\Response(response: 200, description: '状态更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function updateStatus(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), DeliveryOrderValidate::class, [], false, 'update_status');
        $this->deliveryOrderService->updateStatus($id, (string)$data['status']);
        return $this->success('状态更新成功');
    }

    #[Permission('delivery.order.export')]
    #[OA\Get(
        path: '/delivery/order/export',
        summary: '导出配送订单 xlsx',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function export(): Response { return $this->deliveryOrderService->exportXlsx($this->getRequestData()); }

    #[Permission('delivery.order.update')]
    #[OA\Post(
        path: '/delivery/order/auto-dispatch',
        summary: '自动派单（按当前在班配送员工作量最少分配）',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'order_ids', type: 'array', items: new OA\Items(type: 'integer'))])
        ),
        responses: [new OA\Response(response: 200, description: '派单完成，返回 assigned/skipped 数组', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function autoDispatch(): Response
    {
        $data   = $this->validate($this->request->post(), DeliveryOrderValidate::class, [], false, 'auto_dispatch');
        $result = $this->deliveryOrderService->autoDispatch((array)$data['order_ids']);
        return $this->success('派单完成', $result);
    }

    #[Permission('delivery.order.update')]
    #[OA\Get(
        path: '/delivery/order/platform-options',
        summary: '已启用的三方配送平台选项',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        responses: [new OA\Response(response: 200, description: '获取成功，data 为 [{code,label}]', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function platformOptions(): Response
    {
        return $this->success('获取成功', $this->deliveryOrderService->getEnabledPlatformOptions());
    }

    #[Permission('delivery.order.update')]
    #[OA\Get(
        path: '/delivery/order/{id}',
        summary: '配送订单详情（含平台字段与关联订单/配送员）',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', $this->deliveryOrderService->getDetail($id));
    }

    #[Permission('delivery.order.update')]
    #[OA\Get(
        path: '/delivery/order/{id}/tracks',
        summary: '骑手轨迹列表',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function tracks(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', $this->deliveryOrderService->getTracks($id));
    }

    #[Permission('delivery.order.update')]
    #[OA\Post(
        path: '/delivery/order/{id}/dispatch',
        summary: '向三方同城配送平台发单',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'platform', type: 'string', enum: ['dada', 'fengniao', 'uupt', 'shansong', 'sfsc'])])
        ),
        responses: [new OA\Response(response: 200, description: '发单成功，data 为最新配送单', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function dispatch(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->validate($this->request->post(), DeliveryOrderValidate::class, [], false, 'dispatch');
        $record = $this->deliveryOrderService->dispatch($id, (string)$data['platform']);
        return $this->success('发单成功', $record);
    }

    #[Permission('delivery.order.update')]
    #[OA\Post(
        path: '/delivery/order/{id}/sync',
        summary: '主动同步三方配送单状态',
        security: [['bearerAuth' => []]],
        tags: ['同城配送订单'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '同步成功，data 为最新配送单', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function sync(): Response
    {
        $id = (int)$this->request->param('id');
        $record = $this->deliveryOrderService->syncStatus($id);
        return $this->success('同步成功', $record);
    }
}
