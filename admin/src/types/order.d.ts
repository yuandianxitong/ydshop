import type { PageQuery } from './common'

// ========== 订单血缘（拆单 / 合单） ==========
export type OrderRelationType = 'none' | 'split_child' | 'merge_absorbed'

export interface OrderLineageRef {
    id: number
    order_no: string
}

// ========== 订单 ==========
export interface OrderInfo {
    id: number
    order_no: string
    user_id: number
    user_nickname?: string
    user_avatar?: string
    total_amount: number
    pay_amount: number
    pay_method?: string
    status: string
    status_text?: string
    items?: OrderItem[]
    receiver?: string
    phone?: string
    address?: string
    remark?: string
    pay_at?: string
    ship_at?: string
    created_at: string
    updated_at?: string
    /** 血缘关系类型（拆单 / 合单） */
    relation_type?: OrderRelationType
    parent_order_id?: number | null
    /** 拆分来源（当前订单为拆出的子单时） */
    split_from_order_id?: number | null
    split_from_order_no?: string | null
    /** 已合并至（当前订单被合并关闭时） */
    merged_into_order_id?: number | null
    merged_into_order_no?: string | null
    /** 从当前订单拆出的子订单列表 */
    split_child_orders?: OrderLineageRef[]
    /** 合并进当前订单的来源订单列表 */
    merged_from_orders?: OrderLineageRef[]
    [key: string]: any
}

export interface OrderItem {
    id: number
    spu_id: number
    spu_name?: string
    sku_id?: number
    sku_name?: string
    image?: string
    price: number
    quantity: number
    total: number
    [key: string]: any
}

export interface OrderQuery extends PageQuery {
    status?: string
    pay_method?: string
    start_date?: string
    end_date?: string
}

export interface OrderCancelReq {
    reason?: string
    remark?: string
}

export interface OrderRemarkReq {
    remark?: string
    seller_remark?: string
    [key: string]: any
}

export interface OrderAddressUpdateReq {
    name: string
    phone: string
    province: string
    city: string
    district: string
    detail: string
    lng?: number
    lat?: number
}

export interface OrderShipReq {
    order_id: number
    /** express=物流配送，none=无需物流 */
    delivery_mode?: 'express' | 'none'
    /** manual=手动填写，waybill=电子面单（仅 express） */
    ship_mode?: 'manual' | 'waybill'
    express_company?: string
    express_no?: string
    waybill_template_id?: number
    items?: Array<{ item_id: number; quantity: number }>
    [key: string]: any
}

export interface OrderShipResult {
    express_company: string
    express_no: string
    print_template_html?: string | null
    delivery_mode: string
    ship_mode: string
}

export interface OrderLogisticsReq {
    express_company?: string
    express_no?: string
    [key: string]: any
}

export interface OrderTrackingItem {
    time: string
    desc: string
    status?: string
    description?: string
    [key: string]: any
}

export interface OrderSettings {
    auto_cancel_minutes?: number
    auto_confirm_days?: number
    auto_close_refund_days?: number
    [key: string]: any
}

// ========== 改价 / 拆单 / 合单 ==========
/** 改价请求（金额单位：元；仅 pending 订单可用） */
export interface OrderPriceAdjustReq {
    items: Array<{ item_id: number; price: number }>
    freight_amount?: number
    discount_amount?: number
    remark: string
}

/** 拆单请求（仅 paid 未发货订单可用） */
export interface OrderSplitReq {
    items: Array<{ item_id: number; quantity: number }>
    remark?: string
}

export interface OrderSplitResult {
    parent_order_id: number
    child_order_id: number
    child_order_no: string
}

/** 合单请求（同用户全 pending） */
export interface OrderMergeReq {
    order_ids: number[]
    remark?: string
}

export interface OrderMergeResult {
    survivor_order_id: number
    survivor_order_no: string
    closed_order_ids: number[]
}

/** 调整前后金额快照（单位：元） */
export interface OrderAmountSnapshot {
    goods_amount: number
    freight_amount: number
    discount_amount: number
    pay_amount: number
}

export interface OrderAdjustLog {
    id: number
    action: 'split' | 'merge' | 'price_adjust'
    related_order_ids: number[]
    admin_id: number
    admin_name?: string
    before_snapshot: OrderAmountSnapshot
    after_snapshot: OrderAmountSnapshot
    remark?: string
    created_at: string
    [key: string]: any
}

// ========== 退款 ==========
export interface OrderRefundInfo {
    id: number
    refund_no: string
    order_id: number
    order_item_id: number
    order_no?: string
    user_id: number
    user_nickname?: string
    type: 'refund_only' | 'return_refund' | 'exchange'
    refund_amount: number
    reason?: string
    description?: string
    status:
        | 'pending'
        | 'approved'
        | 'returning'
        | 'received'
        | 'refunding'
        | 'retryable_failed'
        | 'manual_review'
        | 'refunded'
        | 'rejected'
    status_text?: string
    images?: string[]
    return_express_company?: string
    return_express_no?: string
    admin_remark?: string
    refuse_reason?: string
    order?: {
        id: number
        order_no: string
        user_id: number
    }
    user?: {
        id: number
        nickname?: string
        avatar?: string
        mobile?: string
    }
    created_at: string
    updated_at?: string
    [key: string]: any
}

export interface OrderRefundQuery extends PageQuery {
    keyword?: string
    /** @deprecated 使用 keyword；服务端仅为旧调用保留兼容。 */
    refund_no?: string
    status?: string
    type?: string
    start_date?: string
    end_date?: string
}

export interface OrderRefundApproveReq {
    refund_amount?: number
    remark?: string
    admin_remark?: string
    [key: string]: any
}

export interface OrderRefundRejectReq {
    reject_reason?: string
    refuse_reason?: string
    remark?: string
    [key: string]: any
}
