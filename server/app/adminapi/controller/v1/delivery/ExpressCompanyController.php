<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\delivery;

use app\adminapi\validate\v1\delivery\ExpressCompanyValidate;
use app\service\delivery\ExpressCompanyService;
use core\base\Controller;
use core\attribute\Permission;
use core\attribute\PermissionSkip;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '物流公司', description: '物流公司管理')]
class ExpressCompanyController extends Controller
{
    protected ExpressCompanyService $expressCompanyService;

    #[Permission('delivery.express.list')]
    #[OA\Get(
        path: '/delivery/express-company',
        summary: '物流公司列表',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: '页码', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: '每页数量', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'keyword', in: 'query', description: '关键词搜索', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: '状态(0禁用 1启用)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))
        ]
    )]
    public function index(): Response
    {
        $params = $this->getRequestData();
        $result = $this->expressCompanyService->getList($params);
        return $this->paginate($result);
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/delivery/express-company/options',
        summary: '物流公司选项列表（下拉用）',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function options(): Response
    {
        $result = $this->expressCompanyService->getOptions();
        return $this->success('获取成功', $result);
    }

    #[Permission('delivery.express.list')]
    #[OA\Get(
        path: '/delivery/express-company/{id}',
        summary: '物流公司详情',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
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
        $result = $this->expressCompanyService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('delivery.express.create')]
    #[OA\Post(
        path: '/delivery/express-company',
        summary: '创建物流公司',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '公司名称'),
                    new OA\Property(property: 'code', type: 'string', description: '编码'),
                    new OA\Property(property: 'logo', type: 'string', description: 'Logo'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态:1启用,0禁用', enum: [0, 1]),
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
        $data   = $this->validate($this->request->post(), ExpressCompanyValidate::class, [], false, 'store');
        $result = $this->expressCompanyService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('delivery.express.update')]
    #[OA\Put(
        path: '/delivery/express-company/{id}',
        summary: '更新物流公司',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'name', type: 'string', description: '公司名称'),
                    new OA\Property(property: 'code', type: 'string', description: '编码'),
                    new OA\Property(property: 'logo', type: 'string', description: 'Logo'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态:1启用,0禁用', enum: [0, 1]),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), ExpressCompanyValidate::class, [], false, 'update');
        $this->expressCompanyService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('delivery.express.update')]
    #[OA\Put(
        path: '/delivery/express-company/{id}/status',
        summary: '更新物流公司状态',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'integer', description: '状态(0禁用 1启用)', enum: [0, 1]),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function status(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), ExpressCompanyValidate::class, [], false, 'status');
        $this->expressCompanyService->updateStatus($id, (int)$data['status']);
        return $this->success('状态更新成功');
    }

    #[Permission('delivery.express.delete')]
    #[OA\Delete(
        path: '/delivery/express-company/{id}',
        summary: '删除物流公司',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
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
        $this->expressCompanyService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('delivery.express.delete')]
    #[OA\Post(
        path: '/delivery/express-company/batch-delete',
        summary: '批量删除物流公司',
        security: [['bearerAuth' => []]],
        tags: ['物流公司'],
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
        $data  = $this->validate($this->request->post(), ExpressCompanyValidate::class, [], false, 'batch_delete');
        $count = $this->expressCompanyService->batchDelete((array)$data['ids']);
        return $this->success('批量删除成功，共删除' . $count . '条记录');
    }
}
