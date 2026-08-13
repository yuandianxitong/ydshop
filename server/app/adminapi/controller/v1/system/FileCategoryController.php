<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\system;

use app\adminapi\validate\v1\system\FileCategoryValidate;
use app\service\system\FileCategoryService;
use core\base\Controller;
use think\Response;
use core\attribute\Permission;
use core\attribute\PermissionSkip;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '文件分类管理', description: '文件分类的树形列表、创建、更新、删除')]
class FileCategoryController extends Controller
{
    protected FileCategoryService $fileCategoryService;

    #[PermissionSkip]
    #[OA\Get(
        path: '/system/file-category/tree',
        summary: '获取文件分类树',
        security: [['bearerAuth' => []]],
        tags: ['文件分类管理'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function tree(): Response
    {
        $result = $this->fileCategoryService->getTree();

        return $this->success(lang('messages.get_success'), $result);
    }

    #[Permission('system.file.update')]
    #[OA\Post(
        path: '/system/file-category',
        summary: '创建文件分类',
        security: [['bearerAuth' => []]],
        tags: ['文件分类管理'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '分类名称'),
                    new OA\Property(property: 'parent_id', type: 'integer', description: '父分类ID', default: 0),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序', default: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '请求失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function store(): Response
    {
        $data   = $this->validate($this->request->post(), FileCategoryValidate::class, [], false, 'store');
        $result = $this->fileCategoryService->create($data);
        return $this->success(lang('messages.create_success'), $result);
    }

    #[Permission('system.file.update')]
    #[OA\Put(
        path: '/system/file-category/{id}',
        summary: '更新文件分类',
        security: [['bearerAuth' => []]],
        tags: ['文件分类管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '分类ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '分类名称'),
                    new OA\Property(property: 'parent_id', type: 'integer', description: '父分类ID'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '请求失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), FileCategoryValidate::class, [], false, 'update');
        $this->fileCategoryService->update($id, $data);
        return $this->success(lang('messages.update_success'));
    }

    #[Permission('system.file.delete')]
    #[OA\Delete(
        path: '/system/file-category/{id}',
        summary: '删除文件分类',
        security: [['bearerAuth' => []]],
        tags: ['文件分类管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '分类ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function delete(): Response
    {
        $id = (int) $this->request->param('id');
        $this->fileCategoryService->delete($id);

        return $this->success(lang('messages.delete_success'));
    }
}
