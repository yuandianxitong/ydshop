<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\order;

use app\adminapi\validate\v1\order\OrderValidate;
use app\service\order\OrderQueryService;
use app\service\order\OrderService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '订单管理', description: '订单查询 / 取消 / 备注 / 自提核销')]
class OrderController extends Controller
{
    protected OrderService $orderService;
    protected OrderQueryService $orderQueryService;

    #[Permission('order.list')]
    #[OA\Get(
        path: '/order/order',
        summary: '订单列表',
        security: [['bearerAuth' => []]],
        tags: ['订单管理'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'status', in: 'query', description: 'pending/paid/shipped/completed/cancelled/closed', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'keyword', in: 'query', description: '订单号/会员搜索', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'delivery_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['express', 'merchant'])),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->orderQueryService->getList($this->getRequestData())); }

    #[Permission('order.list')]
    #[OA\Get(
        path: '/order/order/{id}',
        summary: '订单详情',
        security: [['bearerAuth' => []]],
        tags: ['订单管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response
    {
        return $this->success('获取成功', $this->orderQueryService->getDetail((int)$this->request->param('id')));
    }

    #[Permission('order.cancel')]
    #[OA\Post(
        path: '/order/order/{id}/cancel',
        summary: '后台取消订单',
        security: [['bearerAuth' => []]],
        tags: ['订单管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [new OA\Property(property: 'reason', type: 'string')])
        ),
        responses: [new OA\Response(response: 200, description: '取消成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function cancel(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), OrderValidate::class, [], false, 'cancel');
        $this->orderService->adminCancel($id, (string)($data['reason'] ?? ''));
        return $this->success('取消成功');
    }

    #[Permission('order.update')]
    #[OA\Post(
        path: '/order/order/{id}/remark',
        summary: '卖家备注',
        security: [['bearerAuth' => []]],
        tags: ['订单管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'seller_remark', type: 'string', description: '卖家备注（最多 500 字）')])
        ),
        responses: [new OA\Response(response: 200, description: '备注成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function remark(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), OrderValidate::class, [], false, 'remark');
        $this->orderService->addSellerRemark($id, (string)($data['seller_remark'] ?? ''));
        return $this->success('备注成功');
    }

    #[Permission('order.update')]
    #[OA\Put(
        path: '/order/order/{id}/address',
        summary: '修改收货地址',
        security: [['bearerAuth' => []]],
        tags: ['订单管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'phone', 'province', 'city', 'district', 'detail'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'province', type: 'string'),
                    new OA\Property(property: 'city', type: 'string'),
                    new OA\Property(property: 'district', type: 'string'),
                    new OA\Property(property: 'detail', type: 'string'),
                    new OA\Property(property: 'lng', type: 'number'),
                    new OA\Property(property: 'lat', type: 'number'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function updateAddress(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->validate((array)$this->request->put(), OrderValidate::class, [], false, 'address');
        $this->orderService->adminUpdateAddress($id, $data);
        return $this->success('地址已更新');
    }

    #[Permission('order.delete')]
    #[OA\Delete(
        path: '/order/order/{id}',
        summary: '删除订单（软删除，仅已取消/已关闭）',
        security: [['bearerAuth' => []]],
        tags: ['订单管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $id = (int)$this->request->param('id');
        $this->orderService->adminDelete($id);
        return $this->success('删除成功');
    }

    #[Permission('order.pickup.verify')]
    #[OA\Put(
        path: '/order/order/{id}/pickup-verify',
        summary: '自提码核销',
        security: [['bearerAuth' => []]],
        tags: ['订单管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pickup_code'],
                properties: [new OA\Property(property: 'pickup_code', type: 'string', description: '6 位自提码')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '核销成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '自提码错误 / 订单已核销', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function pickupVerify(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate((array)$this->request->put(), OrderValidate::class, [], false, 'pickup_verify');
        $this->orderService->verifyPickup($id, (string)$data['pickup_code'], $this->getUserId());
        return $this->success('核销成功');
    }
}
