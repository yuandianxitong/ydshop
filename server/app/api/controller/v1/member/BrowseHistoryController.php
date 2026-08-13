<?php
declare(strict_types=1);

namespace app\api\controller\v1\member;

use app\service\member\MemberBrowseHistoryService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '浏览记录', description: '用户浏览历史增删查')]
class BrowseHistoryController extends Controller
{
    protected MemberBrowseHistoryService $browseHistoryService;

    /**
     * 浏览记录列表（分页）
     */
    #[OA\Get(
        path: '/browse-history',
        summary: '浏览记录列表',
        security: [['bearerAuth' => []]],
        tags: ['浏览记录'],
        parameters: [
            new OA\Parameter(name: 'page_no', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'page_size', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function index(): Response
    {
        try {
            $userId = $this->getUserId();
            $page   = (int) $this->request->get('page_no', 1);
            $limit  = (int) $this->request->get('page_size', 20);

            $result = $this->browseHistoryService->getList($userId, $page, $limit);
            return $this->success('success', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 记录一次浏览（详情页埋点调用）
     */
    #[OA\Post(
        path: '/browse-history/record',
        summary: '记录浏览埋点',
        security: [['bearerAuth' => []]],
        tags: ['浏览记录'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['spu_id'],
                properties: [
                    new OA\Property(property: 'spu_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: '记录成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function record(): Response
    {
        try {
            $userId = $this->getUserId();
            $spuId  = (int) $this->request->post('spu_id');
            if ($spuId <= 0) {
                return $this->error('参数错误');
            }
            $this->browseHistoryService->record($userId, $spuId);
            return $this->success('success');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除单条
     */
    #[OA\Delete(
        path: '/browse-history/{id}',
        summary: '删除一条浏览记录',
        security: [['bearerAuth' => []]],
        tags: ['浏览记录'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function remove(int $id): Response
    {
        try {
            $userId = $this->getUserId();
            $ok = $this->browseHistoryService->remove($id, $userId);
            if (!$ok) {
                return $this->error('记录不存在');
            }
            return $this->success('success');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 清空当前用户全部浏览记录
     */
    #[OA\Delete(
        path: '/browse-history',
        summary: '清空全部浏览记录',
        security: [['bearerAuth' => []]],
        tags: ['浏览记录'],
        responses: [
            new OA\Response(response: 200, description: '清空成功，返回 removed 数量', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function clear(): Response
    {
        try {
            $userId = $this->getUserId();
            $count = $this->browseHistoryService->clear($userId);
            return $this->success('success', ['removed' => $count]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
