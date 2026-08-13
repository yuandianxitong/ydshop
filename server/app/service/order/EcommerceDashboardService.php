<?php
declare(strict_types=1);

namespace app\service\order;

use app\repository\order\EcommerceDashboardRepository;
use core\base\Service;

/**
 * 电商仪表板 Service
 *
 * 缓存策略已下沉到 Repository（tag = ecommerce_dashboard），Service 仅
 * 做接口编排；批量失效请调用 $this->ecommerceDashboardRepository->clearCache()
 */
class EcommerceDashboardService extends Service
{
    protected EcommerceDashboardRepository $ecommerceDashboardRepository;

    /**
     * 统计卡片数据（含同比基础数据 + 新客率 + 退款率）
     */
    public function getStats(): array
    {
        return $this->ecommerceDashboardRepository->getCachedStatsBundle();
    }

    /**
     * 近 N 天销售趋势
     */
    public function getSalesTrend(int $days = 7): array
    {
        return $this->ecommerceDashboardRepository->getCachedSalesTrend($days);
    }

    /**
     * 订单状态分布
     */
    public function getOrderStatusDistribution(): array
    {
        return $this->ecommerceDashboardRepository->getCachedOrderStatusDistribution();
    }

    /**
     * 热销商品 Top 10
     */
    public function getHotProducts(): array
    {
        return $this->ecommerceDashboardRepository->getCachedHotProducts(10);
    }

    /**
     * 待办事项
     */
    public function getPendingTasks(): array
    {
        return $this->ecommerceDashboardRepository->getCachedPendingTasks();
    }

    /**
     * 实时 KPI 6 项 × today/yesterday
     */
    public function getRealtimeKpi(): array
    {
        return $this->ecommerceDashboardRepository->getCachedRealtimeKpi();
    }

    /**
     * 支付方式分布（近 N 天）
     */
    public function getPaymentMix(int $days = 30): array
    {
        return $this->ecommerceDashboardRepository->getCachedPaymentMix($days);
    }

    /**
     * 最近 N 条订单流（不缓存：实时性要求高）
     */
    public function getRecentOrders(int $limit = 6): array
    {
        return $this->ecommerceDashboardRepository->getRecentOrders($limit);
    }
}
