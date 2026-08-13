<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1;

use app\adminapi\validate\v1\StoreValidate;
use app\service\store\StoreService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '门店管理', description: '门店 CRUD（含经纬度 + 营业时间）')]
class StoreController extends Controller
{
    protected StoreService $storeService;

    #[Permission('store.list')]
    #[OA\Get(
        path: '/store',
        summary: '门店列表',
        security: [['bearerAuth' => []]],
        tags: ['门店管理'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response
    {
        $params = $this->getRequestData();
        $page   = (int)($params['page'] ?? 1);
        $size   = (int)($params['limit'] ?? 15);
        return $this->paginate($this->storeService->paginate($params, $page, $size));
    }

    #[Permission('store.list')]
    #[OA\Get(
        path: '/store/{id}',
        summary: '门店详情',
        security: [['bearerAuth' => []]],
        tags: ['门店管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'success', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response
    {
        $detail = $this->storeService->detail((int)$this->request->param('id'));
        if (!$detail) return $this->error('门店不存在');
        return $this->success('success', $detail);
    }

    #[Permission('store.create')]
    #[OA\Post(
        path: '/store',
        summary: '创建门店',
        security: [['bearerAuth' => []]],
        tags: ['门店管理'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'code', type: 'string'),
                new OA\Property(property: 'address', type: 'string'),
                new OA\Property(property: 'lng', type: 'number', format: 'float'),
                new OA\Property(property: 'lat', type: 'number', format: 'float'),
                new OA\Property(property: 'phone', type: 'string'),
                new OA\Property(property: 'business_hours', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, StoreValidate::class, [], false, 'create');
        return $this->success('创建成功', ['id' => $this->storeService->create($data)]);
    }

    #[Permission('store.edit')]
    #[OA\Put(
        path: '/store/{id}',
        summary: '更新门店',
        security: [['bearerAuth' => []]],
        tags: ['门店管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->request->put();
        $this->validate($data, StoreValidate::class, [], false, 'update');
        $this->storeService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('store.delete')]
    #[OA\Delete(
        path: '/store/{id}',
        summary: '删除门店（软删除）',
        security: [['bearerAuth' => []]],
        tags: ['门店管理'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function destroy(): Response
    {
        $this->storeService->softDelete((int)$this->request->param('id'));
        return $this->success('删除成功');
    }
}
