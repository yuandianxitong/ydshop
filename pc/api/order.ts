import { get, post } from '~/composables/useRequest'
import type { PageResult } from '~/composables/useRequest'

export type OrderStatus = 'pending' | 'paid' | 'shipped' | 'completed' | 'cancelled' | 'closed'

export interface OrderItemRow {
  id: number
  spu_id: number
  sku_id: number
  goods_name: string
  goods_image: string
  spec_text: string
  /** 单价（decimal 字符串，与后端 bcmath 一致） */
  price: string
  quantity: number
  /** 行小计（decimal 字符串） */
  total_amount: string
  is_reviewed?: number
}

export interface OrderAddress {
  name: string
  phone: string
  province: string
  city: string
  district: string
  detail: string
  region_code?: string
  lng?: number
  lat?: number
}

export interface OrderItem {
  id: number
  order_no: string
  user_id: number
  status: OrderStatus
  /** 商品总额 */
  goods_amount: string
  /** 运费 */
  freight_amount: string
  /** 优惠减免 */
  discount_amount: string
  /** 实付金额 */
  pay_amount: string
  buyer_remark?: string
  pay_type?: string
  paid_at?: string | null
  shipped_at?: string | null
  received_at?: string | null
  created_at: string
  items: OrderItemRow[]
  /** 下单地址快照（json） */
  address_snapshot?: OrderAddress
}

export type DeliveryType = 'express' | 'merchant' | 'pickup'

export interface CreateOrderData {
  items: {
    sku_id: number
    quantity: number
    flash_item_id?: number
    group_activity_id?: number
    group_id?: number
  }[]
  /** 到店自提（pickup）时可不传收货地址 */
  address?: OrderAddress
  buyer_remark?: string
  coupon_user_id?: number
  delivery_type?: DeliveryType
  /** delivery_type = pickup 时必传 */
  pickup_store_id?: number
}

export interface OrderCalcResult {
  goods_amount: string
  freight_amount: string
  discount_amount: string
  pay_amount: string
}

export interface OrderCalcData {
  skus: CreateOrderData['items']
  coupon_user_id?: number
  delivery_type?: DeliveryType
  region_code?: string
  province?: string
  /** 同城配送（merchant）时传用户地址坐标，用于运费计算 + 配送范围校验 */
  lng?: number
  lat?: number
  /** 到店自提时传门店 id */
  pickup_store_id?: number
}

export interface RefundData {
  order_id: number
  order_item_id: number
  type: 'refund_only' | 'refund_with_goods'
  refund_amount?: number
  reason: string
  description?: string
  images?: string[]
}

export interface RefundLogisticsData {
  company: string
  express_no: string
}

// ==================== 售后 ====================

export type RefundStatus =
  | 'pending'
  | 'approved'
  | 'returning'
  | 'received'
  | 'refunding'
  | 'retryable_failed'
  | 'manual_review'
  | 'refunded'
  | 'rejected'

/** 售后类型（后端 enum：refund_only 仅退款 / return_refund 退货退款 / exchange 换货） */
export type RefundType = 'refund_only' | 'return_refund' | 'exchange'

/** 售后单关联的订单项快照（order_items 行） */
export interface RefundOrderItem {
  id: number
  order_id: number
  spu_id: number
  sku_id: number
  goods_name: string
  goods_image: string
  spec_text: string
  price: string
  quantity: number
  total_amount: string
}

/**
 * 售后单（order_refunds r.* + with order/orderItem 关联）
 * 关联键名以后端 toArray 序列化为准，snake / camel 兼容
 */
export interface RefundItem {
  id: number
  refund_no: string
  order_id: number
  order_item_id: number
  user_id: number
  type: RefundType
  status: RefundStatus
  reason: string
  description?: string
  images?: string[] | null
  refund_amount: string
  refunded_at?: string | null
  return_express_company?: string
  return_express_no?: string
  refuse_reason?: string
  created_at: string
  updated_at?: string
  order?: { id: number; order_no: string; user_id: number } | null
  order_item?: RefundOrderItem | null
  /** 兼容 camelCase 序列化 */
  orderItem?: RefundOrderItem | null
}

export const REFUND_STATUS_TEXT: Record<RefundStatus, string> = {
  pending: '待审核',
  approved: '待退货',
  returning: '退货中',
  received: '已收货',
  refunding: '退款中',
  retryable_failed: '退款异常',
  manual_review: '人工处理中',
  refunded: '已退款',
  rejected: '已拒绝',
}

export const REFUND_TYPE_TEXT: Record<string, string> = {
  refund_only: '仅退款',
  return_refund: '退货退款',
  // 历史/前端提交别名
  refund_with_goods: '退货退款',
  exchange: '换货',
}

/** 兼容 orderItem 关联字段的 snake / camel 两种序列化；详情接口返回扁平结构（goods_name 等在顶层），需回退到 refund 本身 */
export function refundOrderItemOf(refund: RefundItem | null | undefined): RefundOrderItem | null {
  if (!refund) return null
  const nested = refund.order_item ?? refund.orderItem
  if (nested) return nested
  return (refund as unknown as RefundOrderItem).goods_name ? (refund as unknown as RefundOrderItem) : null
}

// ==================== 物流轨迹 ====================

export interface TrackingTrace {
  time: string
  desc: string
}

export interface TrackingLogistics {
  id: number
  order_id: number
  express_company: string
  express_no: string
  waybill_no?: string
  traces?: TrackingTrace[] | null
  /** 0待揽收, 1运输中, 2已签收 */
  status: number
  created_at?: string
  updated_at?: string
}

export interface TrackingResult {
  logistics: TrackingLogistics | null
  traces: TrackingTrace[]
}

export interface ReviewData {
  order_id: number
  order_item_id: number
  spu_id: number
  rating: number
  content?: string
  images?: string[]
  is_anonymous?: number
}

export const orderApi = {
  createOrder: (data: CreateOrderData) =>
    post<{ order_no: string }>('/api/order', data),

  /** showError=false 时调用方自行展示错误（如同城配送超出范围提示放在配送方式区域） */
  calc: (data: OrderCalcData, showError = true) =>
    post<OrderCalcResult>('/api/order/calc', data, showError),

  getOrderList: (params?: { page_no?: number; page_size?: number; status?: OrderStatus }) =>
    get<PageResult<OrderItem>>('/api/order', params),

  getOrderDetail: (id: number | string) =>
    get<OrderItem>(`/api/order/${id}`),

  cancelOrder: (id: number | string, data: { reason: string }) =>
    post(`/api/order/${id}/cancel`, data),

  confirmReceive: (id: number | string) =>
    post(`/api/order/${id}/confirm`),

  applyRefund: (data: RefundData) =>
    post('/api/order-refund/apply', data),

  getRefundList: (params?: { page?: number; limit?: number; status?: RefundStatus | '' }) =>
    get<PageResult<RefundItem>>('/api/order-refund', params),

  getRefundDetail: (id: number | string) =>
    get<RefundItem>(`/api/order-refund/${id}`),

  submitRefundLogistics: (id: number | string, data: RefundLogisticsData) =>
    post(`/api/order-refund/${id}/logistics`, data),

  getTracking: (orderId: number | string) =>
    get<TrackingResult>(`/api/order/${orderId}/tracking`),

  createReview: (data: ReviewData) =>
    post('/api/order-review', data),
}
