import http from '@/utils/request'

export interface AddressItem {
  id: number
  user_id: number
  name: string
  phone: string
  province: string
  city: string
  district: string
  detail: string
  /** 经度（同城配送距离计算需要，新数据有；老数据为空时同城配送将兜底用门店周边） */
  lng?: number
  /** 纬度（同上） */
  lat?: number
  /** 末级地区编码，用于运费模板匹配 */
  region_code?: string
  is_default: number
  created_at: string
}

export interface FavoriteItem {
  id: number
  user_id: number
  spu_id: number
  spu_name: string       // 商品名（来自 spu.name）
  spu_image: string      // 主图（spu.images[0]）
  min_price: number      // 最低价（spu.min_price）
  spu_status?: string    // spu 上架状态
  created_at: string
}

export interface BrowseHistoryItem {
  id: number
  user_id: number
  spu_id: number
  spu_name: string
  spu_image: string
  min_price: number
  spu_status?: string
  viewed_at: string
}

export interface MemberProfile {
  id: number
  mobile: string
  nickname: string
  avatar: string
  gender: number
  birthday?: string
  points: number
  balance: number
  member_level_id?: number
  growth_value?: number
  total_points?: number
  total_consume?: number
  order_count?: number
  member_level?: MemberLevel | null
  created_at: string
}

export interface MemberLevel {
  id: number
  name: string
  icon?: string
  growth_min: number
  discount: number
  points_rate: number
  free_freight: boolean | number
  privileges: string[]
  sort: number
  status: number
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

export interface PointsLogItem {
  id: number
  user_id: number
  points: number
  type: number
  type_text: string
  remark: string
  created_at: string
}

export interface BalanceLogItem {
  id: number
  user_id: number
  amount: number
  type: number
  type_text: string
  remark: string
  created_at: string
}

export interface SignCalendar {
  signed_dates: string[]
  continuous_days: number
  today_signed: boolean
  today_points: number
}

export interface SignCheckinResult {
  sign_date: string
  continuous_days: number
  points_awarded: number
}

export const memberApi = {
  getAddressList: () =>
    http.get<AddressItem[]>('/api/address'),

  getDefaultAddress: () =>
    http.get<AddressItem | null>('/api/address/default'),

  createAddress: (data: Omit<AddressItem, 'id' | 'user_id' | 'created_at'>) =>
    http.post<AddressItem>('/api/address', data),

  updateAddress: (id: number | string, data: Partial<Omit<AddressItem, 'id' | 'user_id' | 'created_at'>>) =>
    http.put<AddressItem>(`/api/address/${id}`, data),

  deleteAddress: (id: number | string) =>
    http.delete(`/api/address/${id}`),

  getFavoriteList: (params?: { page_no?: number; page_size?: number }) =>
    http.get<PageResult<FavoriteItem>>('/api/favorite', params),

  toggleFavorite: (data: { spu_id: number }) =>
    http.post<{ favorited: boolean }>('/api/favorite/toggle', data),

  checkFavorite: (spuId: number | string) =>
    http.get<{ favorited: boolean }>(`/api/favorite/check/${spuId}`),

  getMemberProfile: () =>
    http.get<MemberProfile>('/api/member/profile'),

  getMemberLevels: () =>
    http.get<MemberLevel[]>('/api/member/levels'),

  getPointsLog: (params?: { page_no?: number; page_size?: number }) =>
    http.get<PageResult<PointsLogItem>>('/api/member/points-log', params),

  getBalanceLog: (params?: { page_no?: number; page_size?: number }) =>
    http.get<PageResult<BalanceLogItem>>('/api/member/balance-log', params),

  signCheckin: () =>
    http.post<SignCheckinResult>('/api/sign/checkin'),

  getSignCalendar: (month?: string) =>
    http.get<SignCalendar>('/api/sign/calendar', month ? { month } : undefined),

  // ─── 浏览记录 ───
  // 后端返回 { list, pagination: { current_page, per_page, total, last_page } }
  getBrowseHistory: (params?: { page_no?: number; page_size?: number }) =>
    http.get<{
      list: BrowseHistoryItem[]
      pagination: { current_page: number; per_page: number; total: number; last_page: number }
    }>('/api/browse-history', params),

  recordBrowseHistory: (spu_id: number) =>
    http.post('/api/browse-history/record', { spu_id }),

  removeBrowseHistory: (id: number) =>
    http.delete(`/api/browse-history/${id}`),

  clearBrowseHistory: () =>
    http.delete<{ removed: number }>('/api/browse-history'),
}
