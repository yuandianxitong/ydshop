<?php
declare(strict_types=1);

namespace plugins\new_user_gift\controller;

use plugins\new_user_gift\service\NewUserGiftService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '新人礼包', description: '新人礼包配置 + 规则 + 发放日志')]
class NewUserGiftController extends Controller
{
    protected NewUserGiftService $newUserGiftService;

    #[Permission('marketing.new_user_gift.list')]
    #[OA\Get(
        path: '/marketing/new-user-gift',
        summary: '新人礼包列表',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response
    {
        $page  = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $filters = [
            'keyword' => $this->request->get('keyword', ''),
            'status'  => $this->request->get('status', ''),
        ];
        return $this->success('获取成功', $this->newUserGiftService->getList($filters, $page, $limit));
    }

    #[Permission('marketing.new_user_gift.create')]
    #[OA\Post(
        path: '/marketing/new-user-gift',
        summary: '创建新人礼包',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'conditions', type: 'array', items: new OA\Items(type: 'string'), description: '受众标签：new_register/no_order_7d/invited/profile_complete'),
                new OA\Property(property: 'points', type: 'integer'),
                new OA\Property(property: 'balance', type: 'number', format: 'float'),
                new OA\Property(property: 'coupon_ids', type: 'array', items: new OA\Items(type: 'integer')),
                new OA\Property(property: 'valid_start', type: 'string', format: 'date-time'),
                new OA\Property(property: 'valid_end', type: 'string', format: 'date-time'),
                new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $gift = $this->newUserGiftService->create($this->getRequestData());
        return $this->success('创建成功', ['id' => $gift['id']]);
    }

    #[Permission('marketing.new_user_gift.update')]
    #[OA\Put(
        path: '/marketing/new-user-gift/{id}',
        summary: '更新新人礼包',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(int $id): Response
    {
        $this->newUserGiftService->update($id, $this->getRequestData());
        return $this->success('更新成功');
    }

    #[Permission('marketing.new_user_gift.delete')]
    #[OA\Delete(
        path: '/marketing/new-user-gift/{id}',
        summary: '删除新人礼包',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(int $id): Response
    {
        $this->newUserGiftService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('marketing.new_user_gift.view')]
    #[OA\Get(
        path: '/marketing/new-user-gift/rules',
        summary: '获取全局规则',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function getRules(): Response { return $this->success('获取成功', $this->newUserGiftService->getRules()); }

    #[Permission('marketing.new_user_gift.update')]
    #[OA\Put(
        path: '/marketing/new-user-gift/rules',
        summary: '更新全局规则',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        responses: [new OA\Response(response: 200, description: '保存成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function updateRules(): Response
    {
        $this->newUserGiftService->updateRules($this->getRequestData());
        return $this->success('保存成功');
    }

    #[Permission('marketing.new_user_gift.list')]
    #[OA\Get(
        path: '/marketing/new-user-gift/stats',
        summary: '发放统计',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function getStats(): Response
    {
        return $this->success('获取成功', app(\plugins\new_user_gift\service\NewUserGiftLogService::class)->getStats());
    }

    #[Permission('marketing.new_user_gift.list')]
    #[OA\Get(
        path: '/marketing/new-user-gift/logs',
        summary: '发放日志（分页）',
        security: [['bearerAuth' => []]],
        tags: ['新人礼包'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gift_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date_start', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_end', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function logs(): Response
    {
        $page  = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $filters = [
            'user_id'    => $this->request->get('user_id', ''),
            'gift_id'    => $this->request->get('gift_id', ''),
            'date_start' => $this->request->get('date_start', ''),
            'date_end'   => $this->request->get('date_end', ''),
        ];
        return $this->success('获取成功', app(\plugins\new_user_gift\service\NewUserGiftLogService::class)->getList($filters, $page, $limit));
    }
}
