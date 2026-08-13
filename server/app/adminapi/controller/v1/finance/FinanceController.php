<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\finance;

use app\service\finance\FinanceService;
use app\service\finance\PointsRulesService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '财务', description: '财务总览 / 趋势 / 收入组成 / 提现统计 / 流水导出')]
class FinanceController extends Controller
{
    protected FinanceService $financeService;
    protected PointsRulesService $pointsRulesService;

    #[OA\Get(
        path: '/finance/overview',
        summary: '财务总览',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function overview(): Response { return $this->success('获取成功', $this->financeService->getOverview()); }

    #[OA\Get(
        path: '/finance/trend',
        summary: '财务趋势',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        parameters: [new OA\Parameter(name: 'days', in: 'query', schema: new OA\Schema(type: 'integer', default: 30))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function trend(): Response { return $this->success('获取成功', $this->financeService->getTrend((int) $this->request->get('days', 30))); }

    #[OA\Get(
        path: '/finance/income-composition',
        summary: '收入组成（商品/服务/订阅等占比）',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function incomeComposition(): Response { return $this->success('获取成功', $this->financeService->getIncomeComposition()); }

    #[OA\Get(
        path: '/finance/withdrawal-stats',
        summary: '提现统计',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function withdrawalStats(): Response { return $this->success('获取成功', $this->financeService->getWithdrawalStats()); }

    #[OA\Get(
        path: '/finance/transactions',
        summary: '交易流水',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date_start', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_end', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function transactionList(): Response { return $this->paginate($this->financeService->getList($this->getRequestData())); }

    #[Permission('finance.transaction.export')]
    #[OA\Get(
        path: '/finance/transactions/export',
        summary: '导出交易流水 xlsx',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function transactionExport(): Response { return $this->financeService->exportTransactions($this->getRequestData()); }

    #[Permission('finance.overview.export')]
    #[OA\Get(
        path: '/finance/overview/export-month',
        summary: '导出月度财务报表',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        responses: [new OA\Response(response: 200, description: '导出文件流')]
    )]
    public function overviewExportMonth(): Response { return $this->financeService->exportMonthlyReport(); }

    #[Permission('finance.points.rules')]
    #[OA\Get(
        path: '/finance/points-rules',
        summary: '积分规则总览',
        security: [['bearerAuth' => []]],
        tags: ['财务'],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function pointsRules(): Response { return $this->success('获取成功', $this->pointsRulesService->getOverview()); }
}
