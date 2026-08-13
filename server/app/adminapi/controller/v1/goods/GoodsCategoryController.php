<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\service\goods\GoodsCategoryService;
use app\adminapi\validate\v1\goods\GoodsCategoryValidate;
use core\base\Controller;
use core\attribute\Permission;
use core\exception\BusinessException;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '商品分类', description: '商品分类管理')]
class GoodsCategoryController extends Controller
{
    protected GoodsCategoryService $goodsCategoryService;

    #[Permission('goods.goods-category.list')]
    #[OA\Get(
        path: '/goods/goods-category',
        summary: '商品分类列表',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
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
        $result = $this->goodsCategoryService->getList($params);
        return $this->paginate($result);
    }

    #[Permission('goods.goods-category.list')]
    #[OA\Get(
        path: '/goods/goods-category/{id}',
        summary: '商品分类详情',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
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
        $result = $this->goodsCategoryService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('goods.goods-category.create')]
    #[OA\Post(
        path: '/goods/goods-category',
        summary: '创建商品分类',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', description: '父分类ID'),
                    new OA\Property(property: 'name', type: 'string', description: '分类名称'),
                    new OA\Property(property: 'icon', type: 'string', description: '图标'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
                    new OA\Property(property: 'level', type: 'integer', description: '层级'),
                    new OA\Property(property: 'path', type: 'string', description: '祖先链'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态:1启用,0禁用', enum: [0, 1]),
                    new OA\Property(property: 'is_show', type: 'integer', description: '是否前端展示'),
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
        $this->validate($data, GoodsCategoryValidate::class, [], 'create');
        $result = $this->goodsCategoryService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('goods.goods-category.update')]
    #[OA\Put(
        path: '/goods/goods-category/{id}',
        summary: '更新商品分类',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', description: '父分类ID'),
                    new OA\Property(property: 'name', type: 'string', description: '分类名称'),
                    new OA\Property(property: 'icon', type: 'string', description: '图标'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
                    new OA\Property(property: 'level', type: 'integer', description: '层级'),
                    new OA\Property(property: 'path', type: 'string', description: '祖先链'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态:1启用,0禁用', enum: [0, 1]),
                    new OA\Property(property: 'is_show', type: 'integer', description: '是否前端展示'),
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
        $this->validate($data, GoodsCategoryValidate::class, [], 'update');
        $this->goodsCategoryService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-category.delete')]
    #[OA\Delete(
        path: '/goods/goods-category/{id}',
        summary: '删除商品分类',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
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
        $this->goodsCategoryService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('goods.goods-category.list')]
    #[OA\Get(
        path: '/goods/goods-category/tree',
        summary: '商品分类树',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function tree(): \think\Response
    {
        $tree = $this->goodsCategoryService->getTree();
        return $this->success('获取成功', $tree);
    }

    #[Permission('goods.goods-category.delete')]
    #[OA\Post(
        path: '/goods/goods-category/batch-delete',
        summary: '批量删除商品分类',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
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
            $this->goodsCategoryService->delete((int)$id);
        }
        return $this->success('批量删除成功');
    }

    #[Permission('goods.goods-category.update')]
    #[OA\Put(
        path: '/goods/goods-category/{id}/status',
        summary: '更新商品分类状态',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
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
        $this->goodsCategoryService->updateStatus($id, $status);
        return $this->success('状态更新成功');
    }

    #[Permission('goods.goods-category.list')]
    #[OA\Get(
        path: '/goods/goods-category/by-ids',
        summary: '分类选择器水合：按 IDs 取轻量字段',
        security: [['bearerAuth' => []]],
        tags: ['商品分类'],
        parameters: [
            new OA\Parameter(name: 'ids', in: 'query', required: true, description: '逗号分隔的 ID 列表', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功')
        ]
    )]
    public function byIds(): Response
    {
        $idsRaw = (string) $this->request->get('ids', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw)), fn($n) => $n > 0));
        if (count($ids) > 100) {
            throw new BusinessException('ids 数量不可超过 100');
        }
        $data = $this->goodsCategoryService->getByIds($ids);
        return $this->success('获取成功', $data);
    }
}