<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\adminapi\controller\v1\marketing;

use core\base\Controller;
use core\attribute\Permission;
use app\service\marketing\MarketingAdService;
use app\adminapi\validate\v1\marketing\MarketingAdValidate;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '广告管理', description: '广告的增删改查、状态管理')]
class MarketingAdController extends Controller
{
    protected MarketingAdService $adService;

    /**
     * 广告列表
     */
    #[Permission('marketing.ad.list')]
    #[OA\Get(
        path: '/marketing/ads/list',
        summary: '获取广告列表',
        security: [['bearerAuth' => []]],
        tags: ['广告管理'],
        parameters: [
            new OA\Parameter(name: 'page_no', in: 'query', description: '页码', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'page_size', in: 'query', description: '每页数量', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'position_id', in: 'query', description: '广告位ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: '状态(0禁用 1启用)', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'keyword', in: 'query', description: '关键词搜索', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))
        ]
    )]
    public function list(): Response
    {
        $params = $this->getRequestData([
            'page_no'     => 1,
            'page_size'   => 20,
            'position_id' => '',
            'status'      => '',
            'keyword'     => '',
        ]);
        $result = $this->adService->getList($params);
        return $this->paginate($result);
    }

    /**
     * 广告详情
     */
    #[Permission('marketing.ad.list')]
    #[OA\Get(
        path: '/marketing/ads/{id}',
        summary: '获取广告详情',
        security: [['bearerAuth' => []]],
        tags: ['广告管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '广告ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function detail(): Response
    {
        $id = (int) $this->request->param('id');
        $result = $this->adService->detail($id);
        if (!$result) {
            return $this->error(lang('business.record_not_found'));
        }
        return $this->success(lang('messages.get_success'), $result);
    }

    /**
     * 创建广告
     */
    #[Permission('marketing.ad.create')]
    #[OA\Post(
        path: '/marketing/ads',
        summary: '创建广告',
        security: [['bearerAuth' => []]],
        tags: ['广告管理'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['position_id', 'title', 'image'],
                properties: [
                    new OA\Property(property: 'position_id', type: 'integer', description: '广告位ID'),
                    new OA\Property(property: 'title', type: 'string', description: '广告标题'),
                    new OA\Property(property: 'image', type: 'string', description: '广告图片'),
                    new OA\Property(property: 'link', type: 'string', description: '广告链接'),
                    new OA\Property(property: 'start_at', type: 'string', description: '开始时间', format: 'date-time'),
                    new OA\Property(property: 'end_at', type: 'string', description: '结束时间', format: 'date-time'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态(0禁用 1启用)', enum: [0, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '验证失败', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function create(): Response
    {
        $data = $this->request->only([
            'position_id', 'title', 'image', 'link',
            'start_at', 'end_at', 'sort', 'status',
        ]);
        $this->validate($data, MarketingAdValidate::class, [], false, 'create');
        $result = $this->adService->create($data);
        return $this->success(lang('messages.create_success'), $result);
    }

    /**
     * 更新广告
     */
    #[Permission('marketing.ad.update')]
    #[OA\Put(
        path: '/marketing/ads/{id}',
        summary: '更新广告',
        security: [['bearerAuth' => []]],
        tags: ['广告管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '广告ID', schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'position_id', type: 'integer', description: '广告位ID'),
                    new OA\Property(property: 'title', type: 'string', description: '广告标题'),
                    new OA\Property(property: 'image', type: 'string', description: '广告图片'),
                    new OA\Property(property: 'link', type: 'string', description: '广告链接'),
                    new OA\Property(property: 'start_at', type: 'string', description: '开始时间', format: 'date-time'),
                    new OA\Property(property: 'end_at', type: 'string', description: '结束时间', format: 'date-time'),
                    new OA\Property(property: 'sort', type: 'integer', description: '排序'),
                    new OA\Property(property: 'status', type: 'integer', description: '状态(0禁用 1启用)', enum: [0, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function update(): Response
    {
        $id = (int) $this->request->param('id');
        $data = $this->request->only([
            'position_id', 'title', 'image', 'link',
            'start_at', 'end_at', 'sort', 'status',
        ]);
        $this->validate($data, MarketingAdValidate::class, [], false, 'update');
        $this->adService->update($id, $data);
        return $this->success(lang('messages.update_success'));
    }

    /**
     * 删除广告
     */
    #[Permission('marketing.ad.delete')]
    #[OA\Delete(
        path: '/marketing/ads/{id}',
        summary: '删除广告',
        security: [['bearerAuth' => []]],
        tags: ['广告管理'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '广告ID', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function delete(): Response
    {
        $id = (int) $this->request->param('id');
        $this->adService->delete($id);
        return $this->success(lang('messages.delete_success'));
    }

    /**
     * 批量修改广告状态
     */
    #[Permission('marketing.ad.update')]
    #[OA\Post(
        path: '/marketing/ads/batch-status',
        summary: '批量修改广告状态',
        security: [['bearerAuth' => []]],
        tags: ['广告管理'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids', 'status'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array', description: '广告ID数组', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'status', type: 'integer', description: '状态(0禁用 1启用)', enum: [0, 1]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))
        ]
    )]
    public function batchStatus(): Response
    {
        $ids = $this->request->post('ids', []);
        $status = (int) $this->request->post('status');
        $count = $this->adService->batchStatus(is_array($ids) ? $ids : [], $status);
        return $this->success(lang('messages.operation_success') . '（已处理 ' . $count . ' 条）');
    }
}
