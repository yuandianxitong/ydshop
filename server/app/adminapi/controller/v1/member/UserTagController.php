<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\adminapi\validate\v1\member\UserTagValidate;
use app\service\user\UserTagService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '用户标签', description: '用户画像标签管理 + 批量打标')]
class UserTagController extends Controller
{
    protected UserTagService $userTagService;

    #[Permission('member.tag.list')]
    #[OA\Get(
        path: '/member/user-tag',
        summary: '标签列表（分页）',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'group_type', in: 'query', description: '分组类型', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'auto_update', in: 'query', description: '是否自动更新', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->userTagService->getList($this->getRequestData())); }

    #[Permission('member.tag.list')]
    #[OA\Get(
        path: '/member/user-tag/all',
        summary: '全部启用标签（无分页）',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        description: '前端 4 列分组卡片网格使用',
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function all(): Response { return $this->success('获取成功', $this->userTagService->getAllActive()); }

    #[Permission('member.tag.list')]
    #[OA\Get(
        path: '/member/user-tag/{id}',
        summary: '标签详情',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response
    {
        return $this->success('获取成功', $this->userTagService->getDetail((int)$this->request->param('id')));
    }

    #[Permission('member.tag.create')]
    #[OA\Post(
        path: '/member/user-tag',
        summary: '创建标签',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'group_type', type: 'string', description: '分组类型'),
                    new OA\Property(property: 'color', type: 'string', description: '色板'),
                    new OA\Property(property: 'auto_update', type: 'integer', enum: [0, 1], description: '是否自动更新'),
                    new OA\Property(property: 'rules', type: 'array', items: new OA\Items(type: 'object'), description: '自动更新规则 JSON'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                    new OA\Property(property: 'sort', type: 'integer'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, UserTagValidate::class, [], 'create');
        return $this->success('创建成功', $this->userTagService->create($data));
    }

    #[Permission('member.tag.update')]
    #[OA\Put(
        path: '/member/user-tag/{id}',
        summary: '更新标签',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, UserTagValidate::class, [], 'update');
        $this->userTagService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('member.tag.delete')]
    #[OA\Delete(
        path: '/member/user-tag/{id}',
        summary: '删除标签',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->userTagService->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }

    #[Permission('member.tag.refresh')]
    #[OA\Post(
        path: '/member/user-tag/{id}/refresh',
        summary: '立即按规则刷新标签',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '刷新完成，返回 count 匹配用户数', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function refresh(): Response
    {
        $count = $this->userTagService->refreshTag((int)$this->request->param('id'));
        return $this->success("刷新完成，匹配 {$count} 位用户", ['count' => $count]);
    }

    #[Permission('member.tag.assign')]
    #[OA\Post(
        path: '/member/user-tag/assign',
        summary: '批量打标签',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids', 'tag_ids'],
                properties: [
                    new OA\Property(property: 'user_ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '打标成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function assign(): Response
    {
        $data    = $this->request->post();
        $userIds = array_filter(array_map('intval', (array) ($data['user_ids'] ?? [])));
        $tagIds  = array_filter(array_map('intval', (array) ($data['tag_ids'] ?? [])));

        if (empty($userIds) || empty($tagIds)) {
            return $this->error('用户ID和标签ID不能为空');
        }

        $this->userTagService->assignTags(array_values($userIds), array_values($tagIds));
        return $this->success('打标成功');
    }

    #[Permission('member.tag.remove')]
    #[OA\Post(
        path: '/member/user-tag/remove',
        summary: '批量移除标签',
        security: [['bearerAuth' => []]],
        tags: ['用户标签'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids', 'tag_ids'],
                properties: [
                    new OA\Property(property: 'user_ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '移除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function remove(): Response
    {
        $data    = $this->request->post();
        $userIds = array_filter(array_map('intval', (array) ($data['user_ids'] ?? [])));
        $tagIds  = array_filter(array_map('intval', (array) ($data['tag_ids'] ?? [])));

        if (empty($userIds) || empty($tagIds)) {
            return $this->error('用户ID和标签ID不能为空');
        }

        $this->userTagService->removeTags(array_values($userIds), array_values($tagIds));
        return $this->success('移除成功');
    }
}
