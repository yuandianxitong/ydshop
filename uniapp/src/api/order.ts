import http from '@/utils/request'

export type OrderStatus = 'pending' | 'paid' | 'shipped' | 'completed' | 'cancelled' | 'closed'

export interface OrderItem {
  id: number
  order_no: string
  user_id: number
  goods_amount: string       // decimal 字符串（后端 bcmath）
  freight_amount: string
  discount_amount: string
  pay_amount: string
  status: OrderStatus
  buyer_remark?: string
  pay_channel?: string
  paid_at?: string
  created_at: string
  items: OrderItemRow[]              // 后端 with(['items']) 注入
  address_snapshot?: OrderAddress    // 下单时的地址快照（json）
  // 自提字段
  delivery_type?: 'express' | 'merchant' | 'pickup'
  pickup_store_id?: number
  pickup_code?: string
  pickup_status?: 'pending' | 'picked' | 'timeout'
  pickup_expired_at?: string
  // 同城配送：商家配送订单的派单/骑手/状态信息
  delivery_order?: DeliveryOrder
}

export type DeliveryOrderStatus = 'pending' | 'assigned' | 'picking' | 'completed' | 'cancelled'

export interface DeliveryOrder {
  id: number
  order_id: number
  staff_id?: number
  platform: string
  status: DeliveryOrderStatus
  distance: number
  fee: number
  remark?: string
  assigned_at?: string
  picked_at?: string
  completed_at?: string
  staff?: { id: number; name: string; phone: string }
}

export interface OrderItemRow {
  id: number
  spu_id: number
  sku_id: number
  goods_name: string         // 商品名（snapshot from spu.name）
  goods_image: string        // 主图
  spec_text: string          // 规格文本
  price: string              // 单价（decimal 字符串）
  quantity: number
  total_amount: string       // 行小计
}

/** @deprecated 旧名兼容，新代码用 OrderItemRow */
export type OrderGoodsItem = OrderItemRow

export interface OrderAddress {
  name: string
  phone: string
  /** @deprecated 历史 address_snapshot 字段，新数据用 phone */
  mobile?: string
  province: string
  city: string
  district: string
  detail: string
  lng?: number
  lat?: number
  region_code?: string
}

export interface PageResult<T> {
  list: T[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface CreateOrderData {
  items: Array<{
    sku_id: number
    quantity: number
    flash_item_id?: number
    group_activity_id?: number
    group_id?: number
  }>
  address?: OrderAddress
  buyer_remark?: string
  coupon_user_id?: number
  delivery_type?: 'express' | 'merchant' | 'pickup'
  pickup_store_id?: number
}

export interface RefundData {
  order_id: number
  order_item_id: number
  type: 'refund_only' | 'return_refund'
  refund_amount?: number
  reason: string
  description?: string
  images?: string[]
}

export interface RefundLogisticsData {
  company: string
  express_no: string
}

// ─── 售后（C 端退款单） ─────────────────────────────────────────

export type RefundStatus =
  | 'pending'          // 待审核
  | 'approved'         // 已同意（退货退款=待退货；仅退款=退款处理中）
  | 'returning'        // 退货中（买家已填物流）
  | 'received'         // 商家已收货
  | 'refunding'        // 退款中
  | 'retryable_failed' // 退款异常（可重试）
  | 'manual_review'    // 人工处理中
  | 'refunded'         // 已退款
  | 'rejected'         // 已拒绝

export interface RefundItem {
  id: number
  refund_no: string
  order_id: number
  order_item_id: number
  user_id?: number
  /** refund_only 仅退款 / return_refund 退货退款（refund_with_goods 为后端兼容存量数据的别名，语义同 return_refund） */
  type: 'refund_only' | 'return_refund' | 'refund_with_goods'
  status: RefundStatus
  /** 后端 model $type 强转 float，单位元 */
  refund_amount: number
  reason: string
  description?: string
  images?: string[]
  refund_trade_no?: string
  failure_reason?: string
  refuse_reason?: string
  admin_remark?: string
  return_express_company?: string
  return_express_no?: string
  refunded_at?: string
  created_at: string
  updated_at?: string
  /** 后端可能直接平铺 order_no（契约字段），也可能只在关联 order 里 */
  order_no?: string
  /** with(['order']) 关联 */
  order?: { id: number; order_no: string; user_id?: number }
  /** with(['orderItem']) 关联，toArray 后键名 order_item——商品名/图/规格来源 */
  order_item?: OrderItemRow
}

export interface RefundPageResult {
  list: RefundItem[]
  /** 兼容两种分页返回：平铺 total 或嵌套 pagination */
  total?: number
  pagination?: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

// ─── 物流轨迹（快递鸟） ─────────────────────────────────────────

/** TrackingService 轨迹节点：[['time' => '...', 'desc' => '...'], ...]，时间升序 */
export interface TrackingTrace {
  time: string
  desc: string
}

export interface TrackingResult {
  /** order_logistics 记录，未发货/无记录时为 null */
  logistics: {
    express_company?: string
    express_no?: string
    /** 0=待揽收 1=运输中 2=已签收 */
    status?: number
    [key: string]: any
  } | null
  traces: TrackingTrace[]
}

export interface ReviewData {
  order_id: number
  /** 后端 OrderReviewService 期望字段名是 order_item_id（OrderItem 主键） */
  order_item_id: number
  spu_id: number
  rating: number
  content?: string
  images?: string[]
  is_anonymous?: number
}

export interface OrderCalcResult {
  goods_amount: string
  freight_amount: string
  discount_amount: string
  pay_amount: string
}

export interface OrderCalcPayload {
  skus: Array<{
    sku_id: number
    quantity: number
    flash_item_id?: number
    group_activity_id?: number
    group_id?: number
  }>
  coupon_user_id?: number
  delivery_type?: 'express' | 'merchant' | 'pickup'
  pickup_store_id?: number
  lng?: number
  lat?: number
  region_code?: string
  province?: string
}

export const orderApi = {
  createOrder: (data: CreateOrderData) =>
    http.post<{ order_no: string; id: number }>('/api/order', data),

  calc: (data: OrderCalcPayload) =>
    http.post<OrderCalcResult>('/api/order/calc', data),

  getOrderList: (params?: { page_no?: number; page_size?: number; status?: number }) =>
    http.get<PageResult<OrderItem>>('/api/order', params),

  /** 我的页 quick entry 上标用：当前用户各状态订单数 */
  getOrderCounts: () =>
    http.get<{ pending: number; paid: number; shipped: number; refunding: number }>('/api/order/counts'),

  getOrderDetail: (id: number | string) =>
    http.get<OrderItem>(`/api/order/${id}`),

  cancelOrder: (id: number | string, data: { reason: string }) =>
    http.post(`/api/order/${id}/cancel`, data),

  confirmReceive: (id: number | string) =>
    http.post(`/api/order/${id}/confirm`),

  applyRefund: (data: RefundData) =>
    http.post('/api/order-refund/apply', data),

  /** 我的售后列表（order_id 为前端扩展过滤参数，后端未支持时会被忽略、由前端二次过滤） */
  getRefundList: (params?: { page?: number; limit?: number; status?: string; order_id?: number | string }) =>
    http.get<RefundPageResult>('/api/order-refund', params),

  getRefundDetail: (id: number | string) =>
    http.get<RefundItem>(`/api/order-refund/${id}`),

  submitRefundLogistics: (id: number | string, data: RefundLogisticsData) =>
    http.post(`/api/order-refund/${id}/logistics`, data),

  /** 订单真实物流轨迹（快递鸟） */
  getTracking: (orderId: number | string) =>
    http.get<TrackingResult>(`/api/order/${orderId}/tracking`),

  createReview: (data: ReviewData) =>
    http.post('/api/order-review', data),
}

// ─── 发票 ─────────────────────────────────────────────────────────

export interface InvoicePayload {
  order_id: number
  type: 'personal' | 'company'
  title: string
  tax_no?: string
  recipient_email: string
  content?: string
}

export interface InvoiceItem {
  id: number
  order_id: number
  order_no: string
  type: 'personal' | 'company' | 'vat'
  invoice_type: 'electronic' | 'paper'
  title: string
  tax_no: string
  recipient_email: string
  amount: number
  content: string
  status: 'pending' | 'processing' | 'issued' | 'cancelled'
  file_url: string
  issued_at: string | null
  created_at: string
}

export const invoiceApi = {
  submit: (data: InvoicePayload) =>
    http.post<InvoiceItem>('/api/order/invoice', data),
  list: (params?: { page?: number; limit?: number }) =>
    http.get<{ list: InvoiceItem[]; pagination?: any }>('/api/order/invoice', params),
  detail: (id: number) =>
    http.get<InvoiceItem>(`/api/order/invoice/${id}`),
}
