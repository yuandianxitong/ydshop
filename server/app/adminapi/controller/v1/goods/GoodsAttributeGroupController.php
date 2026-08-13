<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\service\goods\GoodsAttributeGroupService;
use app\adminapi\validate\v1\goods\GoodsAttributeGroupValidate;
use core\base\Controller;
use core\attribute\Permission;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '属性分组', description: '属性分组管理')]
class GoodsAttributeGroupController extends Controller
{
    protected GoodsAttributeGroupService $goodsAttributeGroupService;

    #[Permission('goods.goods-attribute-group.list')]
    #[OA\Get(
        path: '/goods/goods-attribute-group',
        summary: '属性分组列表',
        security: [['bearerAuth' => []]],
        tags: ['属性分组'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: '页码', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', description: '每页数量', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'keyword', in: 'query', description: '关键词搜索', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))
        ]
    )]
    public function index(): Response
    {
        $params = $this->getRequestData();
        $result = $this->goodsAttributeGroupService->getList($params);
        return $this->paginate($result);
    }

    #[Permission('goods.goods-attribute-group.list')]
    #[OA\Get(
        path: '/goods/goods-attribute-group/with-attrs',
        summary: '获取分组及属性（按分类）',
        security: [['bearerAuth' => []]],
        tags: ['属性分组'],
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', description: '分类ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function withAttrs(): Response
    {
        $categoryId = (int) $this->request->get('category_id', 0);
        $result = $this->goodsAttributeGroupService->getGroupsWithAttributes($categoryId);
        return $this->success('获取成功', $result);
    }

    #[Permission('goods.goods-attribute-group.list')]
    #[OA\Get(
        path: '/goods/goods-attribute-group/{id}',
        summary: '属性分组详情',
        security: [['bearerAuth' => []]],
        tags: ['属性分组'],
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
        $result = $this->goodsAttributeGroupService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('goods.goods-attribute-group.create')]
    #[OA\Post(
        path: '/goods/goods-attribute-group',
        summary: '创建属性分组',
        security: [['bearerAuth' => []]],
        tags: ['属性分组'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '分组名称'),
                    new OA\Property(property: 'category_id', type: 'integer', description: '分类ID'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
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
        $this->validate($data, GoodsAttributeGroupValidate::class, [], 'create');
        $result = $this->goodsAttributeGroupService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('goods.goods-attribute-group.update')]
    #[OA\Put(
        path: '/goods/goods-attribute-group/{id}',
        summary: '更新属性分组',
        security: [['bearerAuth' => []]],
        tags: ['属性分组'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'name', type: 'string', description: '分组名称'),
                    new OA\Property(property: 'category_id', type: 'integer', description: '分类ID'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
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
        $this->validate($data, GoodsAttributeGroupValidate::class, [], 'update');
        $this->goodsAttributeGroupService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-attribute-group.delete')]
    #[OA\Delete(
        path: '/goods/goods-attribute-group/{id}',
        summary: '删除属性分组',
        security: [['bearerAuth' => []]],
        tags: ['属性分组'],
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
        $this->goodsAttributeGroupService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('goods.goods-attribute-group.delete')]
    #[OA\Post(
        path: '/goods/goods-attribute-group/batch-delete',
        summary: '批量删除属性分组',
        security: [['bearerAuth' => []]],
        tags: ['属性分组'],
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
            $this->goodsAttributeGroupService->delete((int)$id);
        }
        return $this->success('批量删除成功');
    }
}