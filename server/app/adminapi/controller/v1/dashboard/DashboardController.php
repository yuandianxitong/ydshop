<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\adminapi\controller\v1\dashboard;

use app\service\system\DashboardService;
use app\service\order\EcommerceDashboardService;
use core\base\Controller;
use think\Response;
use core\attribute\PermissionSkip;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '仪表板', description: '仪表板统计数据和最近日志')]
class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    protected EcommerceDashboardService $ecommerceDashboardService;

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/stats',
        summary: '获取仪表板统计数据',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function stats(): Response
    {
        try {
            $days = (int) $this->request->get('days', 7);
            $data = $this->dashboardService->getStats($days);
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error(sprintf(lang('business.get_stats_failed'), $e->getMessage()));
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/recent-logs',
        summary: '获取最近登录日志',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function recentLogs(): Response
    {
        try {
            $logs = $this->dashboardService->getRecentLogs();
            return $this->success(lang('messages.get_success'), $logs);
        } catch (\Exception $e) {
            return $this->error(sprintf(lang('business.get_login_log_failed'), $e->getMessage()));
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/recent-activities',
        summary: '获取最近动态',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function recentActivities(): Response
    {
        try {
            $data = $this->dashboardService->getRecentActivities();
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/active-ranking',
        summary: '获取活跃排行',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        parameters: [
            new OA\Parameter(name: 'period', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['day', 'week', 'month']))
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function activeRanking(): Response
    {
        try {
            $period = $this->request->get('period', 'day');
            $data = $this->dashboardService->getActiveRanking($period);
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 电商仪表板接口
    // ═══════════════════════════════════════════════════════════

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/ecommerce-stats',
        summary: '获取电商统计卡片数据',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function ecommerceStats(): Response
    {
        try {
            $data = $this->ecommerceDashboardService->getStats();
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/sales-trend',
        summary: '获取近N天销售趋势',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        parameters: [
            new OA\Parameter(name: 'days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 7))
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function salesTrend(): Response
    {
        try {
            $days = (int) $this->request->get('days', 7);
            $data = $this->ecommerceDashboardService->getSalesTrend($days);
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/order-status',
        summary: '获取订单状态分布',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function orderStatus(): Response
    {
        try {
            $data = $this->ecommerceDashboardService->getOrderStatusDistribution();
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/hot-products',
        summary: '获取热销商品 Top 10',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function hotProducts(): Response
    {
        try {
            $data = $this->ecommerceDashboardService->getHotProducts();
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/pending-tasks',
        summary: '获取待办事项统计',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function pendingTasks(): Response
    {
        try {
            $data = $this->ecommerceDashboardService->getPendingTasks();
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/realtime-kpi',
        summary: '获取实时 KPI 6 项（今日/昨日）',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function realtimeKpi(): Response
    {
        try {
            $data = $this->ecommerceDashboardService->getRealtimeKpi();
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/payment-mix',
        summary: '获取支付方式分布（近 N 天）',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        parameters: [
            new OA\Parameter(name: 'days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30))
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function paymentMix(): Response
    {
        try {
            $days = (int) $this->request->get('days', 30);
            $data = $this->ecommerceDashboardService->getPaymentMix($days);
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PermissionSkip]
    #[OA\Get(
        path: '/dashboard/recent-orders',
        summary: '获取最近 N 条订单',
        security: [['bearerAuth' => []]],
        tags: ['仪表板'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 6))
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功'),
            new OA\Response(response: 400, description: '请求失败')
        ]
    )]
    public function recentOrders(): Response
    {
        try {
            $limit = (int) $this->request->get('limit', 6);
            $data = $this->ecommerceDashboardService->getRecentOrders($limit);
            return $this->success(lang('messages.get_success'), $data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
