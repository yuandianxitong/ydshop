import { get, post } from '~/composables/useRequest'

export type PayChannel = 'wechat' | 'alipay'

/** PC 侧渠道 → 服务端驱动 trade_type 的映射：
 *  wechat → native（生成二维码扫码）
 *  alipay → page（返回自动提交表单 body）
 *  服务端对 wechat 会根据 X-Client-Type 自行重解析，client 端传值仅作 hint。
 */
export type TradeType = 'native' | 'page'

export interface CreateOrderParams {
  order_no: string
  channel: PayChannel
  trade_type: TradeType
}

export interface CreateOrderResult {
  order_no: string
  payment_id: number
  payment_data: {
    trade_type: 'native' | 'page' | 'jsapi' | 'h5' | 'app' | 'wap'
    data: Record<string, any>
  }
}

export interface QueryOrderResult {
  order_no: string
  status: 'pending' | 'paid' | 'failed' | 'refunded'
  amount: number
  channel: PayChannel
  paid_at: string | null
}

export const paymentApi = {
  createOrder: (data: CreateOrderParams) =>
    post<CreateOrderResult>('/api/payment/create', data),

  queryOrder: (order_no: string) =>
    get<QueryOrderResult>('/api/payment/query', { order_no }),
}
