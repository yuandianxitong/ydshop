import { myRequest } from '@/utils/request'

export interface AddressInfo {
  id: number
  user_id: number
  user_nickname?: string
  user_mobile?: string
  name: string
  phone: string
  province: string
  city: string
  district: string
  detail: string
  region_code?: string
  lng?: number | null
  lat?: number | null
  is_default: 0 | 1
  created_at: string
}

export interface AddressStats {
  total: number
  default: number
  users: number
  avg: number
}

export type AddressInput = Partial<Omit<AddressInfo, 'id' | 'created_at' | 'user_nickname' | 'user_mobile'>>

export const addressBookApi = {
  getList(params: Record<string, any>) {
    return myRequest.get('/adminapi/member/address', { params })
  },
  getStats() {
    return myRequest.get('/adminapi/member/address/stats')
  },
  create(data: AddressInput) {
    return myRequest.post<AddressInfo>('/adminapi/member/address', data)
  },
  update(id: number, data: AddressInput) {
    return myRequest.put<void>(`/adminapi/member/address/${id}`, data)
  },
  setDefault(id: number) {
    return myRequest.post<void>(`/adminapi/member/address/${id}/default`)
  },
  delete(id: number) {
    return myRequest.delete(`/adminapi/member/address/${id}`)
  },
}
