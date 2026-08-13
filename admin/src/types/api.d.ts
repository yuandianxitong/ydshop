// ============================================================
// Shop Admin · 类型聚合入口
//
// 共享平台类型分散在 common/system/generator/content/channel/
// dashboard/user 七个 .d.ts 中，这里通过 re-export 让历史代码
// `from '@/types/api'` 仍可工作。
//
// 本文件仅定义商城专属业务类型（电商仪表盘等）。
// ============================================================

export * from './common'
export * from './system'
export * from './generator'
export * from './content'
export * from './channel'
export * from './dashboard'
export * from './user'
export * from './ai'
export * from './finance'
export * from './delivery'
export * from './waybill'
export * from './marketing'
export * from './distribution'
export * from './member-shop'
export * from './order'

// ========== 电商仪表板 ==========
export interface EcommerceStats {
    today_revenue: string | number
    today_orders: number
    pending_shipment: number
    pending_refund: number
    // 新增（同比基础）
    yesterday_revenue: string | number
    yesterday_orders: number
    // 新增（新客占比 0-100）
    today_new_buyer_rate: number
    yesterday_new_buyer_rate: number
    // 新增（退款率 0-100）
    today_refund_rate: number
}

export interface SalesTrendItem {
    date: string
    revenue: number
    orders: number
}

export interface OrderStatusItem {
    status: string
    label: string
    count: number
}

export interface HotProduct {
    id: number
    name: string
    images: string[]
    min_price: number
    sales_count: number
    total_stock: number
    status: string
}

export interface PendingTasks {
    pending_shipment: number
    pending_refund: number
    pending_withdrawal: number
    low_stock: number
}

export interface RealtimeKpi {
    today_orders_total: number
    yesterday_orders_total: number
    today_buyers: number
    yesterday_buyers: number
    today_cart_items: number
    yesterday_cart_items: number
    today_paid: number
    yesterday_paid: number
    today_new_members: number
    yesterday_new_members: number
    today_refunds: number
    yesterday_refunds: number
}

export interface PaymentMixItem {
    pay_type: string
    count: number
    pct: number
}

export interface RecentOrder {
    id: number
    order_no: string
    nickname: string
    pay_amount: string | number
    pay_type: string
    status: string
    created_at: string
}

// ============================================================
// 配送异常工单（SP11）
// ============================================================
export type DeliveryExceptionType =
    | 'delay'
    | 'wrong_delivery'
    | 'complaint'
    | 'reassign'
    | 'weather'

export type DeliveryExceptionStatus =
    | 'open'
    | 'in_progress'
    | 'resolved'
    | 'closed'

export interface DeliveryExceptionTicket {
    id: number
    ticket_no: string
    type: DeliveryExceptionType
    status: DeliveryExceptionStatus
    delivery_order_id: number | null
    title: string
    description: string | null
    evidence: string[] | null
    reporter: string | null
    contact: string | null
    resolution_note: string | null
    handled_by: number | null
    handled_by_name: string | null
    handled_at: string | null
    created_at: string
    updated_at: string
}

export interface DeliveryExceptionTicketQuery {
    page?: number
    limit?: number
    type?: DeliveryExceptionType | ''
    status?: DeliveryExceptionStatus | ''
    keyword?: string
    delivery_order_id?: number
}

// ============================================================
// 实时地图（SP12）
// ============================================================
export interface DeliveryMapConfig {
    js_api_key: string
    js_security_code: string
    default_city: string
}

export interface DeliveryMapOrder {
    id: number
    order_no: string
    address: string
    status: 'pending' | 'assigned' | 'picking' | 'delivering' | 'completed' | 'cancelled'
    dest_lat: number
    dest_lng: number
}

// ============================================================
// 配送排班（SP13）
// ============================================================
export interface DeliveryShift {
    id: number
    staff_id: number
    staff_name?: string
    weekday: number       // 1..7 (1=Mon..7=Sun)
    start_time: string    // 'HH:MM:SS' or 'HH:MM'
    end_time: string
    remark: string
    created_at: string
    updated_at: string
}

export interface DeliveryShiftQuery {
    page?: number
    limit?: number
    staff_id?: number
    weekday?: number
}

export interface AutoDispatchResult {
    assigned: Array<{ id: number; staff_id: number }>
    skipped: Array<{ id: number; reason: string }>
}

// ============================================================
// 电子面单批量打印（SP14）
// ============================================================
export interface WaybillBatchResult {
    success: Array<{ order_id: number; waybill_no: string; print_template_html: string }>
    failed:  Array<{ order_id: number; reason: string }>
}

