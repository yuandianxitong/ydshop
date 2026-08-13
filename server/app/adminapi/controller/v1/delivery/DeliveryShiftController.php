<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\delivery;

use app\adminapi\validate\v1\delivery\DeliveryShiftValidate;
use app\service\delivery\DeliveryShiftService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '配送员排班', description: '配送员排班 CRUD（按 weekday + time 段定义）')]
class DeliveryShiftController extends Controller
{
    protected DeliveryShiftService $deliveryShiftService;

    #[Permission('delivery.shift.list')]
    #[OA\Get(
        path: '/delivery/shift',
        summary: '排班列表',
        security: [['bearerAuth' => []]],
        tags: ['配送员排班'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'staff_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->deliveryShiftService->getList($this->getRequestData())); }

    #[Permission('delivery.shift.list')]
    #[OA\Get(
        path: '/delivery/shift/{id}',
        summary: '排班详情',
        security: [['bearerAuth' => []]],
        tags: ['配送员排班'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'success', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response { return $this->success('success', $this->deliveryShiftService->getDetail((int)$this->request->param('id'))); }

    #[Permission('delivery.shift.manage')]
    #[OA\Post(
        path: '/delivery/shift',
        summary: '创建排班',
        security: [['bearerAuth' => []]],
        tags: ['配送员排班'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'staff_id', type: 'integer'),
                new OA\Property(property: 'weekday', type: 'integer', minimum: 1, maximum: 7),
                new OA\Property(property: 'start_time', type: 'string', description: 'HH:MM:SS'),
                new OA\Property(property: 'end_time', type: 'string', description: 'HH:MM:SS'),
                new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, DeliveryShiftValidate::class, [], false, 'create');
        $id = $this->deliveryShiftService->create($data);
        return $this->success('创建成功', ['id' => $id]);
    }

    #[Permission('delivery.shift.manage')]
    #[OA\Put(
        path: '/delivery/shift/{id}',
        summary: '更新排班',
        security: [['bearerAuth' => []]],
        tags: ['配送员排班'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->request->put();
        $this->validate($data, DeliveryShiftValidate::class, [], false, 'update');
        $this->deliveryShiftService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('delivery.shift.manage')]
    #[OA\Delete(
        path: '/delivery/shift/{id}',
        summary: '删除排班',
        security: [['bearerAuth' => []]],
        tags: ['配送员排班'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function destroy(): Response
    {
        $this->deliveryShiftService->delete((int)$this->request->param('id'));
        return $this->success('删除成功');
    }
}
