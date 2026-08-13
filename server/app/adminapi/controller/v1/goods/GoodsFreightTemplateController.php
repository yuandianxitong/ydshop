<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\service\goods\GoodsFreightTemplateService;
use app\adminapi\validate\v1\goods\GoodsFreightTemplateValidate;
use core\base\Controller;
use core\attribute\Permission;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '运费模板', description: '运费模板管理')]
class GoodsFreightTemplateController extends Controller
{
    protected GoodsFreightTemplateService $goodsFreightTemplateService;

    #[Permission('goods.goods-freight-template.list')]
    #[OA\Get(
        path: '/goods/goods-freight-template',
        summary: '运费模板列表',
        security: [['bearerAuth' => []]],
        tags: ['运费模板'],
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
        $result = $this->goodsFreightTemplateService->getList($params);
        return $this->paginate($result);
    }

    #[Permission('goods.goods-freight-template.list')]
    #[OA\Get(
        path: '/goods/goods-freight-template/{id}',
        summary: '运费模板详情',
        security: [['bearerAuth' => []]],
        tags: ['运费模板'],
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
        $result = $this->goodsFreightTemplateService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    #[Permission('goods.goods-freight-template.create')]
    #[OA\Post(
        path: '/goods/goods-freight-template',
        summary: '创建运费模板',
        security: [['bearerAuth' => []]],
        tags: ['运费模板'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '模板名称'),
                    new OA\Property(property: 'charge_type', type: 'string', description: '计费方式'),
                    new OA\Property(property: 'is_free', type: 'integer', description: '是否免运费'),
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
        $this->validate($data, GoodsFreightTemplateValidate::class, [], 'create');
        $result = $this->goodsFreightTemplateService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('goods.goods-freight-template.update')]
    #[OA\Put(
        path: '/goods/goods-freight-template/{id}',
        summary: '更新运费模板',
        security: [['bearerAuth' => []]],
        tags: ['运费模板'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'name', type: 'string', description: '模板名称'),
                    new OA\Property(property: 'charge_type', type: 'string', description: '计费方式'),
                    new OA\Property(property: 'is_free', type: 'integer', description: '是否免运费'),
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
        $this->validate($data, GoodsFreightTemplateValidate::class, [], 'update');
        $this->goodsFreightTemplateService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('goods.goods-freight-template.delete')]
    #[OA\Delete(
        path: '/goods/goods-freight-template/{id}',
        summary: '删除运费模板',
        security: [['bearerAuth' => []]],
        tags: ['运费模板'],
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
        $this->goodsFreightTemplateService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('goods.goods-freight-template.delete')]
    #[OA\Post(
        path: '/goods/goods-freight-template/batch-delete',
        summary: '批量删除运费模板',
        security: [['bearerAuth' => []]],
        tags: ['运费模板'],
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
            $this->goodsFreightTemplateService->delete((int)$id);
        }
        return $this->success('批量删除成功');
    }
}