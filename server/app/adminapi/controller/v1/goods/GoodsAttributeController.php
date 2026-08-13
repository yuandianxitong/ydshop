<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\service\goods\GoodsAttributeService;
use app\adminapi\validate\v1\goods\GoodsAttributeValidate;
use core\base\Controller;
use core\attribute\Permission;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '属性', description: '属性管理')]
class GoodsAttributeController extends Controller
{
    protected GoodsAttributeService $goodsAttributeService;

    #[Permission('goods.goods-attribute.list')]
    #[OA\Get(
        path: '/goods/goods-attribute',
        summary: '属性列表',
        security: [['bearerAuth' => []]],
        tags: ['属性'],
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
        $result = $this->goodsAttributeService->getList($params);
        return $this->paginate($result);
    }

    #[Permission('goods.goods-attribute.list')]
    #[OA\Get(
        path: '/goods/goods-attribute/{id}',
        summary: '属性详情',
        security: [['bearerAuth' => []]],
        tags: ['属性'],
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
        $result = $this->goodsAttributeService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('goods.goods-attribute.create')]
    #[OA\Post(
        path: '/goods/goods-attribute',
        summary: '创建属性',
        security: [['bearerAuth' => []]],
        tags: ['属性'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'group_id', type: 'integer', description: '分组ID'),
                    new OA\Property(property: 'name', type: 'string', description: '属性名称'),
                    new OA\Property(property: 'type', type: 'string', description: 'input/select/multi_select'),
                    new OA\Property(property: 'options', type: 'string', description: '预设值'),
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
        $this->validate($data, GoodsAttributeValidate::class, [], 'create');
        $result = $this->goodsAttributeService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('goods.goods-attribute.update')]
    #[OA\Put(
        path: '/goods/goods-attribute/{id}',
        summary: '更新属性',
        security: [['bearerAuth' => []]],
        tags: ['属性'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'group_id', type: 'integer', description: '分组ID'),
                    new OA\Property(property: 'name', type: 'string', description: '属性名称'),
                    new OA\Property(property: 'type', type: 'string', description: 'input/select/multi_select'),
                    new OA\Property(property: 'options', type: 'string', description: '预设值'),
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
        $this->validate($data, GoodsAttributeValidate::class, [], 'update');
        $this->goodsAttributeService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-attribute.delete')]
    #[OA\Delete(
        path: '/goods/goods-attribute/{id}',
        summary: '删除属性',
        security: [['bearerAuth' => []]],
        tags: ['属性'],
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
        $this->goodsAttributeService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('goods.goods-attribute.delete')]
    #[OA\Post(
        path: '/goods/goods-attribute/batch-delete',
        summary: '批量删除属性',
        security: [['bearerAuth' => []]],
        tags: ['属性'],
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
            $this->goodsAttributeService->delete((int)$id);
        }
        return $this->success('批量删除成功');
    }
}