import http from '@/utils/request'

export interface BusinessHourSlot {
  open: string
  close: string
}

export type BusinessHours = {
  mon?: BusinessHourSlot[] | 'closed'
  tue?: BusinessHourSlot[] | 'closed'
  wed?: BusinessHourSlot[] | 'closed'
  thu?: BusinessHourSlot[] | 'closed'
  fri?: BusinessHourSlot[] | 'closed'
  sat?: BusinessHourSlot[] | 'closed'
  sun?: BusinessHourSlot[] | 'closed'
}

export interface Store {
  id: number
  name: string
  code: string
  address: string
  lng: number
  lat: number
  phone?: string
  business_hours?: BusinessHours
  status: 0 | 1
  is_open_now?: boolean
  distance?: number | null   // km，list 接口按位置返回
  created_at?: string
}

export interface StoreListQuery {
  lng?: number
  lat?: number
  goods_id?: number
}

/** 用户端门店 API */
export const storeApi = {
  /** 列表（按距离排序，无 lng/lat 时按 sort 排序） */
  list(params: StoreListQuery) {
    return http.get<{ list: Store[] }>('/api/store/list', params)
  },

  /** 详情 */
  detail(id: number) {
    return http.get<Store>(`/api/store/${id}`)
  },
}
