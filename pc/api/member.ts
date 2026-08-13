import { get, post, put, del } from '~/composables/useRequest'
import type { PageResult } from '~/composables/useRequest'

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

export interface SignConfig {
  'sign.points_base': number
  'sign.points_increment': number
  'sign.points_max': number
  'sign.continuous_bonus_days': number
  'sign.continuous_bonus_points': number
}

export interface AddressItem {
  id: number
  user_id: number
  name: string
  phone: string
  province: string
  city: string
  district: string
  detail: string
  region_code?: string
  lng?: number
  lat?: number
  is_default: number
  created_at: string
}

export interface FavoriteItem {
  id: number
  user_id: number
  spu_id: number
  spu_name: string
  spu_image: string
  min_price: number
  spu_status?: string
  created_at: string
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
  created_at: string
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

export const memberApi = {
  getAddressList: () =>
    get<AddressItem[]>('/api/address'),

  getDefaultAddress: () =>
    get<AddressItem | null>('/api/address/default'),

  createAddress: (data: Omit<AddressItem, 'id' | 'user_id' | 'created_at'>) =>
    post<AddressItem>('/api/address', data),

  updateAddress: (id: number | string, data: Partial<Omit<AddressItem, 'id' | 'user_id' | 'created_at'>>) =>
    put<AddressItem>(`/api/address/${id}`, data),

  deleteAddress: (id: number | string) =>
    del(`/api/address/${id}`),

  getFavoriteList: (params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<FavoriteItem>>('/api/favorite', params),

  toggleFavorite: (data: { spu_id: number }) =>
    post<{ favorited: boolean }>('/api/favorite/toggle', data),

  checkFavorite: (spuId: number | string) =>
    get<{ favorited: boolean }>(`/api/favorite/check/${spuId}`),

  getMemberProfile: () =>
    get<MemberProfile>('/api/member/profile'),

  getPointsLog: (params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<PointsLogItem>>('/api/member/points-log', params),

  getBalanceLog: (params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<BalanceLogItem>>('/api/member/balance-log', params),

  signCheckin: () =>
    post<SignCheckinResult>('/api/sign/checkin'),

  getSignCalendar: (month?: string) =>
    get<SignCalendar>('/api/sign/calendar', month ? { month } : undefined),

  getSignConfig: () =>
    get<SignConfig>('/api/sign/config'),
}
