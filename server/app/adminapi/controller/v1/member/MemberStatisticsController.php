<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\member;

use app\service\member\MemberStatisticsService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '会员统计', description: '注册 / 活跃 / 等级 / 消费 / 来源 / 留存矩阵')]
class MemberStatisticsController extends Controller
{
    protected MemberStatisticsService $memberStatisticsService;

    #[Permission('member.statistics')]
    #[OA\Get(
        path: '/member/statistics/overview',
        summary: '会员统计概览',
        security: [['bearerAuth' => []]],
        tags: ['会员统计'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function overview(): Response { return $this->success('success', $this->memberStatisticsService->overview()); }

    #[Permission('member.statistics')]
    #[OA\Get(
        path: '/member/statistics/register-trend',
        summary: '注册趋势',
        security: [['bearerAuth' => []]],
        tags: ['会员统计'],
        parameters: [new OA\Parameter(name: 'days', in: 'query', description: '统计天数', schema: new OA\Schema(type: 'integer', default: 30))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function registerTrend(): Response
    {
        $days = max(1, (int)$this->request->get('days', 30));
        return $this->success('success', $this->memberStatisticsService->registerTrend($days));
    }

    #[Permission('member.statistics')]
    #[OA\Get(
        path: '/member/statistics/active-analysis',
        summary: '活跃分析',
        security: [['bearerAuth' => []]],
        tags: ['会员统计'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function activeAnalysis(): Response { return $this->success('success', $this->memberStatisticsService->activeAnalysis()); }

    #[Permission('member.statistics')]
    #[OA\Get(
        path: '/member/statistics/level-distribution',
        summary: '等级分布',
        security: [['bearerAuth' => []]],
        tags: ['会员统计'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function levelDistribution(): Response { return $this->success('success', $this->memberStatisticsService->levelDistribution()); }

    #[Permission('member.statistics')]
    #[OA\Get(
        path: '/member/statistics/consume-distribution',
        summary: '消费分布',
        security: [['bearerAuth' => []]],
        tags: ['会员统计'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function consumeDistribution(): Response { return $this->success('success', $this->memberStatisticsService->consumeDistribution()); }

    #[Permission('member.statistics')]
    #[OA\Get(
        path: '/member/statistics/source-distribution',
        summary: '来源分布（注册渠道）',
        security: [['bearerAuth' => []]],
        tags: ['会员统计'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function sourceDistribution(): Response { return $this->success('success', $this->memberStatisticsService->sourceDistribution()); }

    #[Permission('member.statistics')]
    #[OA\Get(
        path: '/member/statistics/retention-matrix',
        summary: '留存矩阵',
        security: [['bearerAuth' => []]],
        tags: ['会员统计'],
        parameters: [new OA\Parameter(name: 'cohorts', in: 'query', description: '队列数 1~20', schema: new OA\Schema(type: 'integer', default: 8))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function retentionMatrix(): Response
    {
        $cohorts = max(1, min(20, (int)$this->request->get('cohorts', 8)));
        return $this->success('success', $this->memberStatisticsService->retentionMatrix($cohorts));
    }
}
