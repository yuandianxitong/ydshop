import { get, post } from '~/composables/useRequest'
import type { PageResult } from '~/composables/useRequest'

export type CouponType = 'fixed' | 'percent' | 'no_threshold'

export interface CouponItem {
  id: number
  name: string
  type: CouponType
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
  id: number
  coupon_id: number
  status: 'unused' | 'used' | 'expired'
  used_order_id?: number
  used_at?: string | null
  name: string
  type: CouponType
  value: number
  min_amount: number
  max_discount?: number
  start_at: string
  end_at: string
}

export interface AvailableCouponItem {
  id: number
  coupon_id: number
  status: 'unused'
  discount: number
  coupon: CouponItem
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
  }
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

export const marketingApi = {
  getReceivableCoupons: () =>
    get<CouponItem[]>('/api/marketing/coupon/receivable'),

  getAvailableCoupons: (params: { order_amount: number; spu_ids: number[] | string }) =>
    get<AvailableCouponItem[]>('/api/marketing/coupon/available', params),

  claimCoupon: (id: number | string) =>
    post('/api/marketing/coupon/claim', { coupon_id: Number(id) }),

  getMyCoupons: (params?: { status?: 'unused' | 'used' | 'expired'; page_no?: number; page_size?: number }) =>
    get<PageResult<MyCouponItem>>('/api/marketing/coupon/my', params),

  getFlashSales: () =>
    get<FlashSaleItem[]>('/api/marketing/flash-sale/active'),

  getFlashSaleDetail: (id: number | string) =>
    get<FlashSaleItem>(`/api/marketing/flash-sale/${id}`),

  getFlashSaleByGoods: (spuId: number) =>
    get<FlashSaleMatched | null>(`/api/marketing/flash-sale/goods/${spuId}`),

  getGroupBuyActivities: () =>
    get<GroupBuyActivity[]>('/api/marketing/group-buy/active'),

  getGroupBuyDetail: (id: number | string) =>
    get<GroupBuyDetail>(`/api/marketing/group-buy/${id}`),

  startGroup: (data: { activity_id: number; order_id: number }) =>
    post<{ group_id: number; order_no: string }>('/api/marketing/group-buy/start', data),

  joinGroup: (data: { group_id: number; order_id: number }) =>
    post<{ order_no: string }>('/api/marketing/group-buy/join', data),

  getMyGroups: (params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<OpenGroupItem>>('/api/marketing/group-buy/my-groups', params),

  // ---- Points Mall ----
  getPointsProducts: (params?: { page?: number; limit?: number; type?: string }) =>
    get<{ list: PointsProduct[]; pagination: { current_page: number; per_page: number; total: number; last_page: number } }>('/api/points-mall/list', params),

  getPointsProductDetail: (id: number | string) =>
    get<PointsProduct>(`/api/points-mall/${id}`),

  exchangePoints: (data: { product_id: number; address?: Record<string, any> | null }) =>
    post<PointsOrder>('/api/points-mall/exchange', data),

  getMyPointsOrders: (params?: { page?: number; limit?: number; status?: string }) =>
    get<{ list: PointsOrder[]; pagination: { current_page: number; per_page: number; total: number; last_page: number } }>('/api/points-mall/my-orders', params),

  confirmPointsOrder: (id: number | string) =>
    post<PointsOrder>(`/api/points-mall/${id}/confirm`),
}
