<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\adminapi\validate\v1\member\UserGroupValidate;
use app\service\user\UserGroupService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '用户分组', description: '用户分组管理 + 成员维护')]
class UserGroupController extends Controller
{
    protected UserGroupService $userGroupService;

    #[Permission('member.user_group.list')]
    #[OA\Get(
        path: '/member/user-group',
        summary: '用户分组列表',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->success('获取成功', $this->userGroupService->getList($this->getRequestData())); }

    #[Permission('member.user_group.create')]
    #[OA\Post(
        path: '/member/user-group',
        summary: '创建用户分组',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'conditions', type: 'array', items: new OA\Items(type: 'object'), description: '自动匹配条件 JSON'),
                    new OA\Property(property: 'sort', type: 'integer'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->validate($this->request->post(), UserGroupValidate::class, [], false, 'store');
        return $this->success('创建成功', $this->userGroupService->create($data));
    }

    #[Permission('member.user_group.update')]
    #[OA\Put(
        path: '/member/user-group/{id}',
        summary: '更新用户分组',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), UserGroupValidate::class, [], false, 'update');
        return $this->success('更新成功', $this->userGroupService->update($id, $data));
    }

    #[Permission('member.user_group.delete')]
    #[OA\Delete(
        path: '/member/user-group/{id}',
        summary: '删除用户分组',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->userGroupService->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }

    #[Permission('member.user_group.list')]
    #[OA\Get(
        path: '/member/user-group/{id}',
        summary: '用户分组详情（含成员分页）',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功，含 members 字段', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response
    {
        $id     = (int) $this->request->param('id');
        $params = $this->getRequestData();
        $page   = (int) ($params['page'] ?? 1);
        $limit  = (int) ($params['limit'] ?? 20);

        $detail = $this->userGroupService->detail($id);
        $users  = $this->userGroupService->getGroupUsers($id, $page, $limit);

        return $this->success('获取成功', array_merge($detail, ['members' => $users]));
    }

    #[Permission('member.user_group.update')]
    #[OA\Post(
        path: '/member/user-group/{id}/refresh',
        summary: '刷新分组成员（按条件重算）',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '刷新成功，返回 matched 命中数', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function refresh(): Response
    {
        $matched = $this->userGroupService->refreshGroup((int) $this->request->param('id'));
        return $this->success('刷新成功', ['matched' => $matched]);
    }

    #[Permission('member.user_group.update')]
    #[OA\Post(
        path: '/member/user-group/{id}/add-users',
        summary: '批量添加成员',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids'],
                properties: [new OA\Property(property: 'user_ids', type: 'array', items: new OA\Items(type: 'integer'))]
            )
        ),
        responses: [new OA\Response(response: 200, description: '添加成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function addUsers(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), UserGroupValidate::class, [], false, 'add_users');
        $this->userGroupService->addUsers($id, (array)$data['user_ids']);
        return $this->success('添加成功');
    }

    #[Permission('member.user_group.update')]
    #[OA\Post(
        path: '/member/user-group/{id}/remove-users',
        summary: '批量移除成员',
        security: [['bearerAuth' => []]],
        tags: ['用户分组'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids'],
                properties: [new OA\Property(property: 'user_ids', type: 'array', items: new OA\Items(type: 'integer'))]
            )
        ),
        responses: [new OA\Response(response: 200, description: '移除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function removeUsers(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->validate($this->request->post(), UserGroupValidate::class, [], false, 'remove_users');
        $this->userGroupService->removeUsers($id, (array)$data['user_ids']);
        return $this->success('移除成功');
    }
}
