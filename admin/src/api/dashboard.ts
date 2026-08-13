import type {
    DashboardStats, LoginLogInfo, ActivityItem, ActiveRanking,
    EcommerceStats, SalesTrendItem, OrderStatusItem, HotProduct, PendingTasks,
    RealtimeKpi, PaymentMixItem, RecentOrder,
} from '@/types/api'
import { myRequest } from '@/utils/request'

// 获取仪表板统计数据
export const getDashboardStats = (days: number = 7) => {
    return myRequest.get<DashboardStats>('/adminapi/dashboard/stats', { params: { days } })
}

// 获取最近登录日志
export const getRecentLoginLogs = () => {
    return myRequest.get<LoginLogInfo[]>('/adminapi/dashboard/recent-logs')
}

// 获取最近动态
export const getRecentActivities = () => {
    return myRequest.get<ActivityItem[]>('/adminapi/dashboard/recent-activities')
}

// 获取活跃排行
export const getActiveRanking = (period: string = 'day') => {
    return myRequest.get<ActiveRanking>('/adminapi/dashboard/active-ranking', { params: { period } })
}

// ─── 电商仪表板 ───────────────────────────────────────────────

// 获取电商统计卡片数据（今日订单、今日销售额、待发货、待处理售后）
export const getEcommerceStats = () => {
    return myRequest.get<EcommerceStats>('/adminapi/dashboard/ecommerce-stats')
}

// 获取近 N 天销售趋势
export const getSalesTrend = (days: number = 7) => {
    return myRequest.get<SalesTrendItem[]>('/adminapi/dashboard/sales-trend', { params: { days } })
}

// 获取订单状态分布
export const getOrderStatusDistribution = () => {
    return myRequest.get<OrderStatusItem[]>('/adminapi/dashboard/order-status')
}

// 获取热销商品 Top 10
export const getHotProducts = () => {
    return myRequest.get<HotProduct[]>('/adminapi/dashboard/hot-products')
}

// 获取待办事项统计
export const getPendingTasks = () => {
    return myRequest.get<PendingTasks>('/adminapi/dashboard/pending-tasks')
}

// 实时 KPI
export const getRealtimeKpi = () => {
    return myRequest.get<RealtimeKpi>('/adminapi/dashboard/realtime-kpi')
}

// 支付方式分布
export const getPaymentMix = (days: number = 30) => {
    return myRequest.get<PaymentMixItem[]>('/adminapi/dashboard/payment-mix', { params: { days } })
}

// 最近订单流
export const getRecentOrders = (limit: number = 6) => {
    return myRequest.get<RecentOrder[]>('/adminapi/dashboard/recent-orders', { params: { limit } })
}
