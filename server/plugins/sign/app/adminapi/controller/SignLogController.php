<?php
declare(strict_types=1);

namespace plugins\sign\adminapi\controller;

use plugins\sign\service\MemberSignService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '签到记录', description: '签到日志查询与统计')]
class SignLogController extends Controller
{
    protected MemberSignService $memberSignService;

    #[Permission('marketing.sign.logs.view')]
    #[OA\Get(
        path: '/marketing/sign/logs',
        summary: '签到记录列表',
        security: [['bearerAuth' => []]],
        tags: ['签到记录'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sign_date_start', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sign_date_end', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'is_makeup', in: 'query', description: '0=正常签到 1=补签', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'source', in: 'query', description: '来源：app/h5/wechat', schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function list(): Response
    {
        $page  = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $filters = [
            'user_id'         => $this->request->get('user_id', ''),
            'sign_date_start' => $this->request->get('sign_date_start', ''),
            'sign_date_end'   => $this->request->get('sign_date_end', ''),
            'is_makeup'       => $this->request->get('is_makeup', ''),
            'source'          => $this->request->get('source', ''),
        ];
        return $this->success('获取成功', $this->memberSignService->getLogs($filters, $page, $limit));
    }

    #[Permission('marketing.sign.logs.view')]
    #[OA\Get(
        path: '/marketing/sign/stats',
        summary: '签到统计',
        security: [['bearerAuth' => []]],
        tags: ['签到记录'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function stats(): Response { return $this->success('获取成功', $this->memberSignService->getStats()); }
}
