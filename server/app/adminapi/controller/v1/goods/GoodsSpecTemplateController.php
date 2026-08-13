<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\adminapi\validate\v1\goods\GoodsSpecTemplateValidate;
use app\service\goods\GoodsSpecTemplateService;
use core\attribute\Permission;
use core\base\Controller;
use OpenApi\Attributes as OA;
use think\Response;

#[OA\Tag(name: '规格模板', description: '商品规格模板')]
class GoodsSpecTemplateController extends Controller
{
    protected GoodsSpecTemplateService $goodsSpecTemplateService;

    #[Permission('goods.goods-spec-template.list')]
    #[OA\Get(
        path: '/goods/goods-spec-template',
        summary: '规格模板列表',
        security: [['bearerAuth' => []]],
        tags: ['规格模板'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->goodsSpecTemplateService->getList($this->getRequestData())); }

    #[Permission('goods.goods-spec-template.list')]
    #[OA\Get(
        path: '/goods/goods-spec-template/{id}',
        summary: '规格模板详情',
        security: [['bearerAuth' => []]],
        tags: ['规格模板'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response { return $this->success('获取成功', $this->goodsSpecTemplateService->getDetail((int)$this->request->param('id'))); }

    #[Permission('goods.goods-spec-template.create')]
    #[OA\Post(
        path: '/goods/goods-spec-template',
        summary: '创建规格模板',
        security: [['bearerAuth' => []]],
        tags: ['规格模板'],
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, GoodsSpecTemplateValidate::class, [], 'create');
        return $this->success('创建成功', $this->goodsSpecTemplateService->create($data));
    }

    #[Permission('goods.goods-spec-template.update')]
    #[OA\Put(
        path: '/goods/goods-spec-template/{id}',
        summary: '更新规格模板',
        security: [['bearerAuth' => []]],
        tags: ['规格模板'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, GoodsSpecTemplateValidate::class, [], 'update');
        $this->goodsSpecTemplateService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-spec-template.delete')]
    #[OA\Delete(
        path: '/goods/goods-spec-template/{id}',
        summary: '删除规格模板',
        security: [['bearerAuth' => []]],
        tags: ['规格模板'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->goodsSpecTemplateService->delete((int)$this->request->param('id'));
        return $this->success('删除成功');
    }

    #[Permission('goods.goods-spec-template.delete')]
    #[OA\Post(
        path: '/goods/goods-spec-template/batch-delete',
        summary: '批量删除',
        security: [['bearerAuth' => []]],
        tags: ['规格模板'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'))])
        ),
        responses: [new OA\Response(response: 200, description: '批量删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function batchDelete(): Response
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要删除的记录');
        }
        foreach ($ids as $id) {
            $this->goodsSpecTemplateService->delete((int)$id);
        }
        return $this->success('批量删除成功');
    }
}
