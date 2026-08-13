<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\service\goods\GoodsBrandService;
use app\adminapi\validate\v1\goods\GoodsBrandValidate;
use core\base\Controller;
use core\attribute\Permission;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '品牌', description: '品牌管理')]
class GoodsBrandController extends Controller
{
    protected GoodsBrandService $goodsBrandService;

    #[Permission('goods.goods-brand.list')]
    #[OA\Get(
        path: '/goods/goods-brand',
        summary: '品牌列表',
        security: [['bearerAuth' => []]],
        tags: ['品牌'],
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
        $result = $this->goodsBrandService->getList($params);
        return $this->paginate($result);
    }

    #[Permission('goods.goods-brand.list')]
    #[OA\Get(
        path: '/goods/goods-brand/{id}',
        summary: '品牌详情',
        security: [['bearerAuth' => []]],
        tags: ['品牌'],
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
        $result = $this->goodsBrandService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('goods.goods-brand.create')]
    #[OA\Post(
        path: '/goods/goods-brand',
        summary: '创建品牌',
        security: [['bearerAuth' => []]],
        tags: ['品牌'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '品牌名称'),
                    new OA\Property(property: 'logo', type: 'string', description: 'Logo'),
                    new OA\Property(property: 'description', type: 'string', description: '描述'),
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
        $data = $this->request->post();
        $this->validate($data, GoodsBrandValidate::class, [], 'create');
        $result = $this->goodsBrandService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('goods.goods-brand.update')]
    #[OA\Put(
        path: '/goods/goods-brand/{id}',
        summary: '更新品牌',
        security: [['bearerAuth' => []]],
        tags: ['品牌'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'name', type: 'string', description: '品牌名称'),
                    new OA\Property(property: 'logo', type: 'string', description: 'Logo'),
                    new OA\Property(property: 'description', type: 'string', description: '描述'),
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
        $id = (int) $this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, GoodsBrandValidate::class, [], 'update');
        $this->goodsBrandService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-brand.delete')]
    #[OA\Delete(
        path: '/goods/goods-brand/{id}',
        summary: '删除品牌',
        security: [['bearerAuth' => []]],
        tags: ['品牌'],
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
        $this->goodsBrandService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('goods.goods-brand.delete')]
    #[OA\Post(
        path: '/goods/goods-brand/batch-delete',
        summary: '批量删除品牌',
        security: [['bearerAuth' => []]],
        tags: ['品牌'],
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
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要删除的记录');
        }
        foreach ($ids as $id) {
            $this->goodsBrandService->delete((int)$id);
        }
        return $this->success('批量删除成功');
    }

    #[Permission('goods.goods-brand.update')]
    #[OA\Put(
        path: '/goods/goods-brand/{id}/status',
        summary: '更新品牌状态',
        security: [['bearerAuth' => []]],
        tags: ['品牌'],
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
        $id = (int) $this->request->param('id');
        $status = (int) $this->request->post('status', 0);
        $this->goodsBrandService->updateStatus($id, $status);
        return $this->success('状态更新成功');
    }
}