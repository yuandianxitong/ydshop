<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\service\member\MemberLevelService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '会员等级', description: '会员等级体系配置')]
class MemberLevelController extends Controller
{
    protected MemberLevelService $memberLevelService;

    #[Permission('member.level.list')]
    #[OA\Get(
        path: '/member/member-level',
        summary: '等级列表（分页）',
        security: [['bearerAuth' => []]],
        tags: ['会员等级'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response { return $this->paginate($this->memberLevelService->getList($this->getRequestData())); }

    #[Permission('member.level.create')]
    #[OA\Post(
        path: '/member/member-level',
        summary: '创建会员等级',
        security: [['bearerAuth' => []]],
        tags: ['会员等级'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'growth_required'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', description: '等级名称'),
                    new OA\Property(property: 'growth_required', type: 'integer', description: '所需成长值'),
                    new OA\Property(property: 'discount_rate', type: 'number', format: 'float', description: '折扣比例（0~1）'),
                    new OA\Property(property: 'sort', type: 'integer'),
                    new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, \app\adminapi\validate\v1\member\MemberLevelValidate::class, [], 'create');
        return $this->success('创建成功', $this->memberLevelService->create($data));
    }

    #[Permission('member.level.update')]
    #[OA\Put(
        path: '/member/member-level/{id}',
        summary: '更新会员等级',
        security: [['bearerAuth' => []]],
        tags: ['会员等级'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id   = (int) $this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, \app\adminapi\validate\v1\member\MemberLevelValidate::class, [], 'update');
        return $this->success('更新成功', $this->memberLevelService->update($id, $data));
    }

    #[Permission('member.level.delete')]
    #[OA\Delete(
        path: '/member/member-level/{id}',
        summary: '删除会员等级',
        security: [['bearerAuth' => []]],
        tags: ['会员等级'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->memberLevelService->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }
}
