<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\service\goods\GoodsUnitService;
use app\adminapi\validate\v1\goods\GoodsUnitValidate;
use core\base\Controller;
use core\attribute\Permission;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '计量单位', description: '计量单位管理')]
class GoodsUnitController extends Controller
{
    protected GoodsUnitService $goodsUnitService;

    #[Permission('goods.goods-unit.list')]
    #[OA\Get(
        path: '/goods/goods-unit',
        summary: '计量单位列表',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
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
        $result = $this->goodsUnitService->getList($params);
        return $this->paginate($result);
    }

    #[Permission('goods.goods-unit.list')]
    #[OA\Get(
        path: '/goods/goods-unit/{id}',
        summary: '计量单位详情',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
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
        $result = $this->goodsUnitService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('goods.goods-unit.create')]
    #[OA\Post(
        path: '/goods/goods-unit',
        summary: '创建计量单位',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '单位名称'),
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
        $this->validate($data, GoodsUnitValidate::class, [], 'create');
        $result = $this->goodsUnitService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('goods.goods-unit.update')]
    #[OA\Put(
        path: '/goods/goods-unit/{id}',
        summary: '更新计量单位',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'name', type: 'string', description: '单位名称'),
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
        $this->validate($data, GoodsUnitValidate::class, [], 'update');
        $this->goodsUnitService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-unit.delete')]
    #[OA\Delete(
        path: '/goods/goods-unit/{id}',
        summary: '删除计量单位',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
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
        $this->goodsUnitService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('goods.goods-unit.delete')]
    #[OA\Post(
        path: '/goods/goods-unit/batch-delete',
        summary: '批量删除计量单位',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
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
            $this->goodsUnitService->delete((int)$id);
        }
        return $this->success('批量删除成功');
    }

    #[Permission('goods.goods-unit.update')]
    #[OA\Put(
        path: '/goods/goods-unit/{id}/status',
        summary: '更新计量单位状态',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
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
        $this->goodsUnitService->updateStatus($id, $status);
        return $this->success('状态更新成功');
    }

    /**
     * 概览统计
     */
    #[Permission('goods.goods-unit.list')]
    #[OA\Get(
        path: '/goods/goods-unit/stats',
        summary: '计量单位概览统计',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function stats(): Response { return $this->success('获取成功', $this->goodsUnitService->getStats()); }

    /**
     * 换算关系列表
     */
    #[Permission('goods.goods-unit.list')]
    #[OA\Get(
        path: '/goods/goods-unit/conversions',
        summary: '换算关系列表',
        security: [['bearerAuth' => []]],
        tags: ['计量单位'],
        parameters: [new OA\Parameter(name: 'group_id', in: 'query', schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function conversions(): Response
    {
        $params = $this->getRequestData();
        $result = $this->goodsUnitService->getConversions($params);
        return $this->success('获取成功', $result);
    }
}