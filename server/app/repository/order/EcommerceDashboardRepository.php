<?php
declare(strict_types=1);

namespace app\repository\order;

use app\model\goods\GoodsSku;
use app\model\goods\GoodsSpu;
use app\model\member\MemberCart;
use app\model\order\OrderOrder;
use app\model\order\OrderRefund;
use app\model\user\User;
use core\base\Repository;
use core\cache\CacheableRepository;
use core\plugin\HookManager;
use think\Model as ThinkModel;

/**
 * 电商仪表板数据仓库
 *
 * 数据更新滞后≤5min 即可，故 stats/trend/distribution 等聚合查询统一在
 * Repository 内做缓存，tag = ecommerce_dashboard。Service 层仅调用
 * getCached* 方法，不再各自管理 Cache::remember key。
 */
class EcommerceDashboardRepository extends Repository
{
    use CacheableRepository;

    protected string $cacheTag = 'ecommerce_dashboard';
    protected int $cacheTTL = 300;

    protected function getModel(): ThinkModel
    {
        return new OrderOrder();
    }

    // ─────────────────────────────────────────────────────────────
    // 缓存包装层（按业务节奏区分 TTL，统一 ecommerce_dashboard tag）
    // ─────────────────────────────────────────────────────────────

    /**
     * 顶部统计卡片（含同比基础 + 新客率 + 退款率），缓存 120s
     */
    public function getCachedStatsBundle(): array
    {
        return $this->cacheRemember('stats_bundle_' . date('Ymd'), function () {
            $today     = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            return [
                'today_orders'             => $this->getTodayOrderCount(),
                'today_revenue'            => $this->getTodayRevenue(),
                'pending_shipment'         => $this->getPendingShipmentCount(),
                'pending_refund'           => $this->getPendingRefundCount(),
                'yesterday_revenue'        => $this->getYesterdayRevenue(),
                'yesterday_orders'         => $this->getYesterdayOrderCount(),
                'today_new_buyer_rate'     => $this->getNewBuyerRate($today),
                'yesterday_new_buyer_rate' => $this->getNewBuyerRate($yesterday),
                'today_refund_rate'        => $this->getRefundRate($today),
            ];
        }, 120);
    }

    /**
     * 近 N 天销售趋势，缓存 300s
     */
    public function getCachedSalesTrend(int $days): array
    {
        return $this->cacheRemember('sales_trend_' . $days . '_' . date('Ymd'), function () use ($days) {
            return $this->getSalesTrend($days);
        }, 300);
    }

    /**
     * 订单状态分布，缓存 300s
     */
    public function getCachedOrderStatusDistribution(): array
    {
        return $this->cacheRemember('order_status_dist_' . date('Ymd'), function () {
            return $this->getOrderStatusDistribution();
        }, 300);
    }

    /**
     * 热销商品 Top 10，缓存 300s
     */
    public function getCachedHotProducts(int $limit = 10): array
    {
        return $this->cacheRemember('hot_products_' . $limit . '_' . date('Ymd'), function () use ($limit) {
            return $this->getHotProducts($limit);
        }, 300);
    }

    /**
     * 待办事项，缓存 120s
     */
    public function getCachedPendingTasks(): array
    {
        return $this->cacheRemember('pending_tasks_' . date('Ymd'), function () {
            return $this->getPendingTasks();
        }, 120);
    }

    /**
     * 实时 KPI，缓存 120s
     */
    public function getCachedRealtimeKpi(): array
    {
        return $this->cacheRemember('realtime_kpi_' . date('Ymd'), function () {
            return $this->getRealtimeKpi();
        }, 120);
    }

    /**
     * 支付方式分布，缓存 300s
     */
    public function getCachedPaymentMix(int $days): array
    {
        return $this->cacheRemember('payment_mix_' . $days . '_' . date('Ymd'), function () use ($days) {
            return $this->getPaymentMix($days);
        }, 300);
    }

    // ─────────────────────────────────────────────────────────────
    // Stats cards
    // ─────────────────────────────────────────────────────────────

    /**
     * 今日订单数（排除已取消）
     */
    public function getTodayOrderCount(): int
    {
        return (int) OrderOrder::whereDay('created_at')
            ->whereNotIn('status', [OrderOrder::STATUS_CANCELLED])
            ->count();
    }

    /**
     * 今日销售额（已支付/已发货/已完成）
     */
    public function getTodayRevenue(): float
    {
        return (float) OrderOrder::whereDay('created_at')
            ->whereIn('status', [
                OrderOrder::STATUS_PAID,
                OrderOrder::STATUS_SHIPPED,
                OrderOrder::STATUS_COMPLETED,
            ])
            ->sum('pay_amount');
    }

    /**
     * 待发货订单数（已支付但未发货）
     */
    public function getPendingShipmentCount(): int
    {
        return (int) OrderOrder::where('status', OrderOrder::STATUS_PAID)->count();
    }

    /**
     * 待处理售后数（退款申请待审核）
     */
    public function getPendingRefundCount(): int
    {
        return (int) OrderRefund::where('status', 'pending')->count();
    }

    // ─────────────────────────────────────────────────────────────
    // Sales trend（近 N 天）
    // ─────────────────────────────────────────────────────────────

    /**
     * 获取最近 N 天的销售趋势
     *
     * @return array  每项: ['date'=>'Y-m-d', 'revenue'=>float, 'orders'=>int]
     */
    public function getSalesTrend(int $days = 7): array
    {
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $revenue = (float) OrderOrder::whereDay('created_at', $date)
                ->whereIn('status', [
                    OrderOrder::STATUS_PAID,
                    OrderOrder::STATUS_SHIPPED,
                    OrderOrder::STATUS_COMPLETED,
                ])
                ->sum('pay_amount');
            $orders = (int) OrderOrder::whereDay('created_at', $date)
                ->whereNotIn('status', [OrderOrder::STATUS_CANCELLED])
                ->count();
            $result[] = [
                'date'    => $date,
                'revenue' => $revenue,
                'orders'  => $orders,
            ];
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // Order status distribution
    // ─────────────────────────────────────────────────────────────

    /**
     * 按状态统计订单数量
     *
     * @return array  每项: ['status'=>string, 'label'=>string, 'count'=>int]
     */
    public function getOrderStatusDistribution(): array
    {
        $statuses = [
            OrderOrder::STATUS_PENDING   => '待付款',
            OrderOrder::STATUS_PAID      => '待发货',
            OrderOrder::STATUS_SHIPPED   => '已发货',
            OrderOrder::STATUS_COMPLETED => '已完成',
            OrderOrder::STATUS_CANCELLED => '已取消',
            OrderOrder::STATUS_CLOSED    => '已关闭',
        ];

        $result = [];
        foreach ($statuses as $status => $label) {
            $count = (int) OrderOrder::where('status', $status)->count();
            $result[] = [
                'status' => $status,
                'label'  => $label,
                'count'  => $count,
            ];
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // Hot products Top 10
    // ─────────────────────────────────────────────────────────────

    /**
     * 热销商品 Top 10（按 sales_count 排序）
     *
     * @return array
     */
    public function getHotProducts(int $limit = 10): array
    {
        return GoodsSpu::order('sales_count', 'desc')
            ->limit($limit)
            ->field('id,name,images,min_price,sales_count,total_stock,status')
            ->select()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // Pending tasks
    // ─────────────────────────────────────────────────────────────

    /**
     * 获取各类待办事项数量
     *
     * @return array
     */
    public function getPendingTasks(): array
    {
        return [
            'pending_shipment'   => (int) OrderOrder::where('status', OrderOrder::STATUS_PAID)->count(),
            'pending_refund'     => (int) OrderRefund::where('status', 'pending')->count(),
            'pending_withdrawal' => $this->countPendingDistributionWithdrawals(),
            'low_stock'          => (int) GoodsSku::where('stock', '<', 10)->where('status', 1)->count(),
        ];
    }

    private function countPendingDistributionWithdrawals(): int
    {
        return (int) HookManager::apply('finance.pending_withdrawal_count', [], 0);
    }

    // ─────────────────────────────────────────────────────────────
    // 同比 / 环比基础数据
    // ─────────────────────────────────────────────────────────────

    /**
     * 昨日销售额（已支付/已发货/已完成）
     */
    public function getYesterdayRevenue(): float
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        return (float) OrderOrder::whereDay('created_at', $yesterday)
            ->whereIn('status', [
                OrderOrder::STATUS_PAID,
                OrderOrder::STATUS_SHIPPED,
                OrderOrder::STATUS_COMPLETED,
            ])
            ->sum('pay_amount');
    }

    /**
     * 昨日订单数（排除已取消）
     */
    public function getYesterdayOrderCount(): int
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        return (int) OrderOrder::whereDay('created_at', $yesterday)
            ->whereNotIn('status', [OrderOrder::STATUS_CANCELLED])
            ->count();
    }

    /**
     * 指定日期的新客占比（0-100）
     * 定义：当日下单（status>=PAID）的 distinct user_id 中，
     *      该 user_id 在 order_orders 表中（PAID+ 状态）的 MIN(created_at) 在当日的 = 新客
     */
    public function getNewBuyerRate(string $date): float
    {
        $statuses = [
            OrderOrder::STATUS_PAID,
            OrderOrder::STATUS_SHIPPED,
            OrderOrder::STATUS_COMPLETED,
        ];

        $userIds = OrderOrder::whereDay('created_at', $date)
            ->whereIn('status', $statuses)
            ->group('user_id')
            ->column('user_id');

        if (empty($userIds)) {
            return 0.0;
        }

        // 单次批量查询：每个 user_id 的全期 MIN(created_at)
        $rows = OrderOrder::whereIn('user_id', $userIds)
            ->whereIn('status', $statuses)
            ->field('user_id, MIN(created_at) as first_at')
            ->group('user_id')
            ->select()
            ->toArray();

        $newCount = 0;
        foreach ($rows as $r) {
            if (!empty($r['first_at']) && substr((string)$r['first_at'], 0, 10) === $date) {
                $newCount++;
            }
        }
        return round($newCount / count($userIds) * 100, 2);
    }

    /**
     * 指定日期的退款率（0-100）：当日新建退款单数 / 当日订单数
     */
    public function getRefundRate(string $date): float
    {
        $orderCount = (int) OrderOrder::whereDay('created_at', $date)
            ->whereNotIn('status', [OrderOrder::STATUS_CANCELLED])
            ->count();
        if ($orderCount === 0) {
            return 0.0;
        }
        $refundCount = (int) OrderRefund::whereDay('created_at', $date)->count();
        return round($refundCount / $orderCount * 100, 2);
    }

    // ─────────────────────────────────────────────────────────────
    // 实时 KPI（6 项 today + yesterday）
    // ─────────────────────────────────────────────────────────────

    /**
     * 实时 KPI 6 项 × 2 (today / yesterday)
     */
    public function getRealtimeKpi(): array
    {
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $orderTotal = function (string $d): int {
            return (int) OrderOrder::whereDay('created_at', $d)
                ->whereNotIn('status', [OrderOrder::STATUS_CANCELLED])
                ->count();
        };
        $buyers = function (string $d): int {
            return (int) OrderOrder::whereDay('created_at', $d)
                ->whereIn('status', [
                    OrderOrder::STATUS_PAID,
                    OrderOrder::STATUS_SHIPPED,
                    OrderOrder::STATUS_COMPLETED,
                ])
                ->distinct(true)
                ->count('user_id');
        };
        $cartItems = function (string $d): int {
            return (int) MemberCart::whereDay('created_at', $d)->count();
        };
        $paid = function (string $d): int {
            return (int) OrderOrder::whereDay('created_at', $d)
                ->whereIn('status', [
                    OrderOrder::STATUS_PAID,
                    OrderOrder::STATUS_SHIPPED,
                    OrderOrder::STATUS_COMPLETED,
                ])
                ->count();
        };
        $newMembers = function (string $d): int {
            return (int) User::whereDay('created_at', $d)->count();
        };
        $refunds = function (string $d): int {
            return (int) OrderRefund::whereDay('created_at', $d)->count();
        };

        return [
            'today_orders_total'     => $orderTotal($today),
            'yesterday_orders_total' => $orderTotal($yesterday),
            'today_buyers'           => $buyers($today),
            'yesterday_buyers'       => $buyers($yesterday),
            'today_cart_items'       => $cartItems($today),
            'yesterday_cart_items'   => $cartItems($yesterday),
            'today_paid'             => $paid($today),
            'yesterday_paid'         => $paid($yesterday),
            'today_new_members'      => $newMembers($today),
            'yesterday_new_members'  => $newMembers($yesterday),
            'today_refunds'          => $refunds($today),
            'yesterday_refunds'      => $refunds($yesterday),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 支付方式分布
    // ─────────────────────────────────────────────────────────────

    /**
     * 近 N 天支付方式分布
     * 返回 [['pay_type'=>'wechat','count'=>1234,'pct'=>38.5], ...]
     */
    public function getPaymentMix(int $days = 30): array
    {
        if ($days <= 0) {
            return [];
        }
        $since = date('Y-m-d 00:00:00', strtotime("-" . max(0, $days - 1) . " days"));
        $rows = OrderOrder::where('created_at', '>=', $since)
            ->whereIn('status', [
                OrderOrder::STATUS_PAID,
                OrderOrder::STATUS_SHIPPED,
                OrderOrder::STATUS_COMPLETED,
            ])
            ->where('pay_type', '<>', '')
            ->field('pay_type, COUNT(*) as count')
            ->group('pay_type')
            ->order('count', 'desc')
            ->select()
            ->toArray();

        $total = array_sum(array_column($rows, 'count'));
        if ($total === 0) {
            return [];
        }
        foreach ($rows as &$r) {
            $r['count'] = (int) $r['count'];
            $r['pct']   = round($r['count'] / $total * 100, 1);
        }
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────
    // 最近订单流
    // ─────────────────────────────────────────────────────────────

    /**
     * 最近 N 条订单（用于工作台订单流）
     */
    public function getRecentOrders(int $limit = 6): array
    {
        $orders = OrderOrder::order('created_at', 'desc')
            ->limit($limit)
            ->field('id, order_no, user_id, pay_amount, pay_type, status, created_at')
            ->select()
            ->toArray();

        if (empty($orders)) {
            return [];
        }

        $userIds = array_unique(array_column($orders, 'user_id'));
        $users = User::whereIn('id', $userIds)
            ->field('id, nickname')
            ->select()
            ->toArray();
        $userMap = array_column($users, 'nickname', 'id');

        foreach ($orders as &$o) {
            $o['nickname'] = $userMap[$o['user_id']] ?? '匿名用户';
            unset($o['user_id']);
        }
        return $orders;
    }
}
