<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\diy;

use core\base\Controller;
use app\service\diy\DiyPageService;
use app\adminapi\validate\v1\diy\DiyPageValidate;
use core\attribute\Permission;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'DIY 装修页面', description: 'DIY 页面 CRUD + 发布 + 版本 + 预览')]
class DiyPageController extends Controller
{
    protected DiyPageService $service;
    protected \app\service\diy\DiyPageVersionService $versionService;

    #[Permission('diy.page.list')]
    #[OA\Get(
        path: '/diy/page',
        summary: 'DIY 页面列表',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [
            new OA\Parameter(name: 'platform', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page_type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index()
    {
        $params = $this->getRequestData(['platform' => '', 'page_type' => '', 'keyword' => '', 'page' => 1, 'limit' => 20]);
        return $this->paginate($this->service->getPageList($params));
    }

    #[Permission('diy.page.list')]
    #[OA\Get(
        path: '/diy/page/{id}',
        summary: 'DIY 页面详情',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show()
    {
        $result = $this->service->getPageDetail((int) $this->request->param('id'));
        if (!$result) return $this->error('页面不存在');
        return $this->success('获取成功', $result);
    }

    #[Permission('diy.page.create')]
    #[OA\Post(
        path: '/diy/page',
        summary: '创建 DIY 页面',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'platform', type: 'string', enum: ['uniapp', 'pc']),
                new OA\Property(property: 'page_type', type: 'string'),
                new OA\Property(property: 'components', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'template_id', type: 'integer', description: '基于模板创建（可选）'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store()
    {
        $data = $this->request->post();
        $this->validate($data, DiyPageValidate::class, [], 'create');
        $templateId = (int) $this->request->post('template_id', 0);
        return $this->success('创建成功', $this->service->createPage($data, $templateId ?: null));
    }

    #[Permission('diy.page.update')]
    #[OA\Put(
        path: '/diy/page/{id}',
        summary: '保存 DIY 页面',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '保存成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update()
    {
        $id = (int) $this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, DiyPageValidate::class, [], 'update');
        $this->service->updatePage($id, $data);
        return $this->success('保存成功');
    }

    #[Permission('diy.page.publish')]
    #[OA\Post(
        path: '/diy/page/{id}/publish',
        summary: '发布 / 取消发布',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'is_published', type: 'boolean', description: 'true=发布 false=取消发布，默认 true'),
                new OA\Property(property: 'note', type: 'string', description: '发布备注'),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '发布成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function publish()
    {
        $id = (int) $this->request->param('id');
        $publish = (bool) $this->request->post('is_published', 1);
        $note = $this->request->post('note');
        $adminId = $this->getUserId() ?: null;
        $this->service->publishPage(
            $id,
            $publish,
            is_string($note) && trim($note) !== '' ? trim($note) : null,
            $adminId ? (int) $adminId : null
        );
        return $this->success($publish ? '发布成功' : '已取消发布');
    }

    #[Permission('diy.page.delete')]
    #[OA\Delete(
        path: '/diy/page/{id}',
        summary: '删除 DIY 页面',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete()
    {
        $this->service->deletePage((int) $this->request->param('id'));
        return $this->success('删除成功');
    }

    #[Permission('diy.page.set_default')]
    #[OA\Post(
        path: '/diy/page/{id}/set-default',
        summary: '设为默认页面',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '已设为默认', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function setDefault()
    {
        $this->service->setDefault((int) $this->request->param('id'));
        return $this->success('已设为默认');
    }

    #[Permission('diy.page.list')]
    #[OA\Post(
        path: '/diy/page/preview-data',
        summary: '预览数据（hydrate components）',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'components', type: 'array', items: new OA\Items(type: 'object'))])
        ),
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function previewData()
    {
        $components = $this->request->post('components', []);
        if (!is_array($components)) return $this->error('参数错误');
        return $this->success('获取成功', ['components' => $this->service->hydrateComponents($components)]);
    }

    #[Permission('diy.page.version.list')]
    #[OA\Get(
        path: '/diy/page/{id}/versions',
        summary: '版本列表',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function listVersions()
    {
        return $this->success('获取成功', $this->versionService->getList((int) $this->request->param('id')));
    }

    #[Permission('diy.page.version.list')]
    #[OA\Get(
        path: '/diy/page/{id}/versions/{vid}',
        summary: '版本详情',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'vid', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function showVersion()
    {
        $row = $this->versionService->getDetail((int) $this->request->param('id'), (int) $this->request->param('vid'));
        if (!$row) return $this->error('版本不存在');
        return $this->success('获取成功', $row);
    }

    #[Permission('diy.page.version.restore')]
    #[OA\Post(
        path: '/diy/page/{id}/versions/{vid}/restore',
        summary: '恢复到指定版本',
        security: [['bearerAuth' => []]],
        tags: ['DIY 装修页面'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'vid', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: '已恢复', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function restoreVersion()
    {
        $this->versionService->restore((int) $this->request->param('id'), (int) $this->request->param('vid'));
        return $this->success('已恢复至所选版本');
    }
}
