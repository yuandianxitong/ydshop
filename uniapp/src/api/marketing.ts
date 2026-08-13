import http from '@/utils/request'

export interface CouponItem {
  id: number
  name: string
  type: 'fixed' | 'percent' | 'no_threshold'
  value: number
  min_amount: number
  max_discount?: number
  start_at: string
  end_at: string
  total_count: number | null
  used_count: number
  per_user_limit: number
  status: number
  has_claimed?: boolean
  claimed_count?: number
}

export interface MyCouponItem {
  id: number                                    // coupon_user 行 id
  coupon_id: number
  status: 'unused' | 'used' | 'expired'
  used_order_id?: number
  used_at?: string | null
  // 拍平自 row.coupon.*
  name: string
  type: CouponItem['type']
  value: number
  min_amount: number
  start_at: string
  end_at: string
}

export interface AvailableCouponItem {
  id: number
  coupon_id: number
  status: 'unused'
  discount: number
  coupon: CouponItem & {
    use_scope?: 'all' | 'category' | 'spu'
    scope_ids?: number[]
  }
}

export interface FlashSaleItem {
  id: number
  name: string
  start_time: string
  end_time: string
  status: number
  goods: FlashSaleGoodsItem[]
}

export interface FlashSaleGoodsItem {
  id: number
  flash_sale_id: number
  spu_id: number
  sku_id: number
  name: string
  cover: string
  flash_price: number
  original_price: number
  stock: number
  sold_num: number
  limit_num: number
}

export interface GroupBuyActivity {
  id: number
  spu_id: number
  sku_id: number
  name: string
  cover: string
  group_price: number
  original_price: number
  group_size: number
  start_time: string
  end_time: string
  status: number
}

export interface GroupBuyDetail extends GroupBuyActivity {
  description?: string
  open_groups: OpenGroupItem[]
}

export interface OpenGroupItem {
  id: number
  activity_id: number
  leader_id: number
  leader_avatar: string
  leader_nickname: string
  current_size: number
  group_size: number
  expired_at: string
  members: GroupMemberItem[]
}

export interface GroupMemberItem {
  id: number
  user_id: number
  avatar: string
  nickname: string
  joined_at: string
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

export interface PointsProduct {
  id: number
  name: string
  cover: string
  type: 'physical' | 'virtual'
  points_price: number
  stock: number
  sort: number
  status: number
  description?: string
  exchange_limit: number
  created_at: string
}

export interface PointsOrder {
  id: number
  order_no: string
  user_id: number
  product_id: number
  product_name: string
  points_spent: number
  type: 'physical' | 'virtual'
  status: string
  content?: string
  address_snapshot?: Record<string, any>
  express_company?: string
  express_no?: string
  created_at: string
}

export interface PointsPageResult {
  list: PointsProduct[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface PointsOrderPageResult {
  list: PointsOrder[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface FullDiscountRule {
  id: number
  name: string
  type: 'reduce' | 'percent' | 'freight'
  rules: Array<{ min: number; value: number }>
  end_at: string
  use_scope: 'all' | 'category' | 'spu'
  stackable: number
}

// ---- Lottery ----
export interface LotterySubmitAddress {
  name: string
  phone: string
  province: string
  city: string
  district: string
  detail: string
  region_code?: string
}

export interface LotteryShipment {
  id: number
  order_no: string
  user_id: number
  activity_id: number
  record_id: number
  prize_id: number
  prize_name: string
  prize_image: string
  address_snapshot: LotterySubmitAddress | null
  express_company: string
  express_no: string
  status: 'pending' | 'shipped' | 'completed' | 'cancelled' | 'expired'
  status_text: string
  expire_at: string | null
  shipped_at: string | null
  completed_at: string | null
  cancelled_at: string | null
  cancel_reason: string
  created_at: string
}

export interface LotteryShipmentsPage {
  list: LotteryShipment[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface LotteryPrize {
  id: number
  position: number
  name: string
  image: string
  type: number              // 1=优惠券 2=积分 3=谢谢参与
  reference_id: number
  value: number
  stock: number
  original_stock: number
}

export interface LotteryActivity {
  id: number
  title: string
  cover: string
  description: string
  start_at: string
  end_at: string
  status: number
  daily_free_count: number
  points_per_draw: number
  created_at: string
  updated_at: string
}

export interface LotteryActivityDetail extends LotteryActivity {
  prizes: LotteryPrize[]
  quota: LotteryQuota | null
}

export interface LotteryQuota {
  daily_free_count: number
  used_free_today: number
  remaining_free: number
  points_per_draw: number
}

export interface LotteryDrawResult {
  record_id: number
  prize_id: number
  position: number
  prize_name: string
  prize_image: string
  prize_type: number
  prize_value: number
  is_free: number
  cost_points: number
}

export interface LotteryRecord {
  id: number
  activity_id: number
  prize_id: number
  prize_type: number
  prize_name: string
  prize_value: number
  is_free: number
  cost_points: number
  created_at: string
}

export interface LotteryRecordsPage {
  list: LotteryRecord[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface FlashSaleMatched {
  id: number
  name: string
  start_at: string
  end_at: string
  matched_item: {
    id: number
    sku_id: number
    flash_price: number | string
    flash_stock: number
    sold_count: number
    per_user_limit: number
    sku: {
      id: number
      spu_id: number
      price: number | string
      image: string
    }
  }
}

export const marketingApi = {
  getAvailableCoupons: (params: { order_amount: number; spu_ids: number[] | string }) =>
    http.get<AvailableCouponItem[]>('/api/marketing/coupon/available', params),

  getReceivableCoupons: () =>
    http.get<CouponItem[]>('/api/marketing/coupon/receivable'),

  claimCoupon: (id: number | string) =>
    http.post('/api/marketing/coupon/claim', { coupon_id: Number(id) }),

  getMyCoupons: (params?: { status?: 'unused' | 'used' | 'expired'; page_no?: number; page_size?: number }) =>
    http.get<PageResult<MyCouponItem>>('/api/marketing/coupon/my', params),

  getFlashSales: () =>
    http.get<FlashSaleItem[]>('/api/marketing/flash-sale/active'),

  getFlashSaleDetail: (id: number | string) =>
    http.get<FlashSaleItem>(`/api/marketing/flash-sale/${id}`),

  getGroupBuyActivities: () =>
    http.get<GroupBuyActivity[]>('/api/marketing/group-buy/active'),

  getGroupBuyDetail: (id: number | string) =>
    http.get<GroupBuyDetail>(`/api/marketing/group-buy/${id}`),

  startGroup: (data: { activity_id: number; order_id: number }) =>
    http.post<{ group_id: number; order_no: string }>('/api/marketing/group-buy/start', data),

  joinGroup: (data: { group_id: number; order_id: number }) =>
    http.post<{ order_no: string }>('/api/marketing/group-buy/join', data),

  getMyGroups: (params?: { page_no?: number; page_size?: number }) =>
    http.get<PageResult<OpenGroupItem>>('/api/marketing/group-buy/my-groups', params),

  // ---- Points Mall ----
  getPointsProducts: (params?: { page?: number; limit?: number; type?: string }) =>
    http.get<PointsPageResult>('/api/points-mall/list', params),

  getPointsProductDetail: (id: number | string) =>
    http.get<PointsProduct>(`/api/points-mall/${id}`),

  exchangePoints: (data: { product_id: number; address?: Record<string, any> | null }) =>
    http.post<PointsOrder>('/api/points-mall/exchange', data),

  getMyPointsOrders: (params?: { page?: number; limit?: number; status?: string }) =>
    http.get<PointsOrderPageResult>('/api/points-mall/my-orders', params),

  confirmPointsOrder: (id: number | string) =>
    http.post<PointsOrder>(`/api/points-mall/${id}/confirm`),

  getFullDiscountRules: (spuId: number) =>
    http.get<FullDiscountRule[]>(`/api/marketing/full-discount/goods/${spuId}`),

  getFlashSaleByGoods: (spuId: number) =>
    http.get<FlashSaleMatched | null>(`/api/marketing/flash-sale/goods/${spuId}`),

  // ---- Lottery ----
  getLotteryActivities: () =>
    http.get<LotteryActivity[]>('/api/marketing/lottery/active'),

  getLotteryDetail: (id: number | string) =>
    http.get<LotteryActivityDetail>(`/api/marketing/lottery/detail/${id}`),

  getLotteryQuota: (id: number | string) =>
    http.get<LotteryQuota>(`/api/marketing/lottery/quota/${id}`),

  drawLottery: (id: number | string) =>
    http.post<LotteryDrawResult>(`/api/marketing/lottery/draw/${id}`),

  getMyLotteryRecords: (params?: { activity_id?: number; page_no?: number; page_size?: number }) =>
    http.get<LotteryRecordsPage>('/api/marketing/lottery/my-records', params),

  // ---- Lottery 实物奖品发货 ----
  claimLotteryShipment: (record_id: number, address: LotterySubmitAddress) =>
    http.post<LotteryShipment>('/api/marketing/lottery/shipments/claim', { record_id, address }),

  getMyLotteryShipments: (params?: { status?: string; activity_id?: number; page_no?: number; page_size?: number }) =>
    http.get<LotteryShipmentsPage>('/api/marketing/lottery/shipments', params),

  getLotteryShipmentDetail: (id: number | string) =>
    http.get<LotteryShipment>(`/api/marketing/lottery/shipments/${id}`),

  confirmLotteryShipment: (id: number | string) =>
    http.post<LotteryShipment>(`/api/marketing/lottery/shipments/${id}/confirm`),
}
