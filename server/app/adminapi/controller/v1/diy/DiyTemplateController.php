<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\diy;

use core\base\Controller;
use app\service\diy\DiyTemplateService;
use app\adminapi\validate\v1\diy\DiyTemplateValidate;
use core\attribute\Permission;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'DIY 模板', description: 'DIY 页面模板（用于创建新页面的初始内容）')]
class DiyTemplateController extends Controller
{
    protected DiyTemplateService $service;

    #[Permission('diy.template.list')]
    #[OA\Get(
        path: '/diy/template',
        summary: '模板列表',
        security: [['bearerAuth' => []]],
        tags: ['DIY 模板'],
        parameters: [
            new OA\Parameter(name: 'platform', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page_type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 50)),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index()
    {
        $params = $this->getRequestData(['platform' => '', 'page_type' => '', 'page' => 1, 'limit' => 50]);
        return $this->paginate($this->service->getList($params));
    }

    #[Permission('diy.template.list')]
    #[OA\Get(
        path: '/diy/template/{id}',
        summary: '模板详情',
        security: [['bearerAuth' => []]],
        tags: ['DIY 模板'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show()
    {
        $result = $this->service->getDetail((int) $this->request->param('id'));
        if (!$result) return $this->error('模板不存在');
        return $this->success('获取成功', $result);
    }

    #[Permission('diy.template.create')]
    #[OA\Post(
        path: '/diy/template',
        summary: '创建模板',
        security: [['bearerAuth' => []]],
        tags: ['DIY 模板'],
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store()
    {
        $data = $this->request->post();
        $this->validate($data, DiyTemplateValidate::class, [], 'create');
        return $this->success('创建成功', $this->service->create($data));
    }

    #[Permission('diy.template.update')]
    #[OA\Put(
        path: '/diy/template/{id}',
        summary: '更新模板',
        security: [['bearerAuth' => []]],
        tags: ['DIY 模板'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update()
    {
        $id = (int) $this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, DiyTemplateValidate::class, [], 'update');
        $this->service->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('diy.template.delete')]
    #[OA\Delete(
        path: '/diy/template/{id}',
        summary: '删除模板',
        security: [['bearerAuth' => []]],
        tags: ['DIY 模板'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete()
    {
        $this->service->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }
}
