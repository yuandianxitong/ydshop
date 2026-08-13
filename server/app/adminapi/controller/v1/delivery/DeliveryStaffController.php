<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\delivery;

use app\adminapi\validate\v1\delivery\DeliveryStaffValidate;
use app\service\delivery\DeliveryStaffService;
use core\base\Controller;
use core\attribute\Permission;
use core\attribute\PermissionSkip;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '配送员', description: '配送员管理')]
class DeliveryStaffController extends Controller
{
    protected DeliveryStaffService $deliveryStaffService;

    #[Permission('delivery.staff.list')]
    #[OA\Get(
        path: '/delivery/staff',
        summary: '配送员列表',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: '页码', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: '每页数量', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'keyword', in: 'query', description: '关键词搜索', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: '状态(0休息 1在岗)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))
        ]
    )]
    public function index(): Response
    {
        $params = $this->getRequestData();
        $result = $this->deliveryStaffService->getList($params);
        return $this->paginate($result);
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/delivery/staff/options',
        summary: '配送员选项列表（下拉用）',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function options(): Response
    {
        $result = $this->deliveryStaffService->getOptions();
        return $this->success('获取成功', $result);
    }

    #[Permission('delivery.staff.list')]
    #[OA\Get(
        path: '/delivery/staff/{id}',
        summary: '配送员详情',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function show(): Response
    {
        $id = (int) $this->request->param('id');
        $result = $this->deliveryStaffService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('delivery.staff.create')]
    #[OA\Post(
        path: '/delivery/staff',
        summary: '创建配送员',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '姓名'),
                    new OA\Property(property: 'phone', type: 'string', description: '手机号'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态:1在岗,0休息', enum: [0, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '验证失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function store(): Response
    {
        $data   = $this->validate($this->request->post(), DeliveryStaffValidate::class, [], false, 'store');
        $result = $this->deliveryStaffService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('delivery.staff.update')]
    #[OA\Put(
        path: '/delivery/staff/{id}',
        summary: '更新配送员',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'name', type: 'string', description: '姓名'),
                    new OA\Property(property: 'phone', type: 'string', description: '手机号'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态:1在岗,0休息', enum: [0, 1]),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), DeliveryStaffValidate::class, [], false, 'update');
        $this->deliveryStaffService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('delivery.staff.update')]
    #[OA\Put(
        path: '/delivery/staff/{id}/status',
        summary: '更新配送员状态',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', description: '状态(0休息 1在岗)', enum: [0, 1]),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function status(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), DeliveryStaffValidate::class, [], false, 'status');
        $this->deliveryStaffService->updateStatus($id, (int)$data['status']);
        return $this->success('状态更新成功');
    }

    #[Permission('delivery.staff.delete')]
    #[OA\Delete(
        path: '/delivery/staff/{id}',
        summary: '删除配送员',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function delete(): Response
    {
        $id = (int) $this->request->param('id');
        $this->deliveryStaffService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('delivery.staff.delete')]
    #[OA\Post(
        path: '/delivery/staff/batch-delete',
        summary: '批量删除配送员',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'ID列表'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '批量删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function batchDelete(): Response
    {
        $data  = $this->validate($this->request->post(), DeliveryStaffValidate::class, [], false, 'batch_delete');
        $count = $this->deliveryStaffService->batchDelete((array)$data['ids']);
        return $this->success('批量删除成功，共删除' . $count . '条记录');
    }

    #[Permission('delivery.staff.export')]
    #[OA\Get(
        path: '/delivery/staff/export',
        summary: '导出配送员 xlsx',
        security: [['bearerAuth' => []]],
        tags: ['配送员'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function export(): Response
    {
        $params = $this->getRequestData();
        return $this->deliveryStaffService->exportXlsx($params);
    }
}
