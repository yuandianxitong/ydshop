<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\adminapi\validate\v1\goods\GoodsUnitGroupValidate;
use app\service\goods\GoodsUnitGroupService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '计量单位分组', description: '计量单位分组管理')]
class GoodsUnitGroupController extends Controller
{
    protected GoodsUnitGroupService $goodsUnitGroupService;

    #[Permission('goods.goods-unit.list')]
    #[OA\Get(
        path: '/goods/goods-unit-group',
        summary: '计量单位分组列表',
        security: [['bearerAuth' => []]],
        tags: ['计量单位分组'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function index(): Response { return $this->success('获取成功', $this->goodsUnitGroupService->getList()); }

    #[Permission('goods.goods-unit.create')]
    #[OA\Post(
        path: '/goods/goods-unit-group',
        summary: '创建分组',
        security: [['bearerAuth' => []]],
        tags: ['计量单位分组'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'tone', type: 'string'),
                    new OA\Property(property: 'sort', type: 'integer'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->validate($this->request->post(), GoodsUnitGroupValidate::class, [], false, 'store');
        return $this->success('创建成功', $this->goodsUnitGroupService->create($data));
    }

    #[Permission('goods.goods-unit.update')]
    #[OA\Put(
        path: '/goods/goods-unit-group/{id}',
        summary: '更新分组',
        security: [['bearerAuth' => []]],
        tags: ['计量单位分组'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), GoodsUnitGroupValidate::class, [], false, 'update');
        $this->goodsUnitGroupService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-unit.delete')]
    #[OA\Delete(
        path: '/goods/goods-unit-group/{id}',
        summary: '删除分组',
        security: [['bearerAuth' => []]],
        tags: ['计量单位分组'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->goodsUnitGroupService->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }
}
