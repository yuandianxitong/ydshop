import { myRequest } from '@/utils/request'
import type { AdPositionInfo } from './adPosition'

export interface AdInfo {
  id: number
  position_id: number
  position?: AdPositionInfo | null
  title: string
  image: string
  link: string
  start_at: string | null
  end_at: string | null
  sort: number
  status: number
  is_active: number
  created_at: string
  updated_at: string
}

export interface AdReq {
  position_id: number
  title: string
  image: string
  link?: string
  start_at?: string | null
  end_at?: string | null
  sort?: number
  status: number
}

export const adApi = {
  getList: (params?: { page?: number; limit?: number; page_no?: number; page_size?: number; position_id?: number; status?: number | string; keyword?: string }) =>
    myRequest.get<{ list: AdInfo[]; pagination: any }>('/adminapi/ads/list', { params }),

  getDetail: (id: number) => myRequest.get<AdInfo>(`/adminapi/ads/${id}`),

  create: (data: AdReq) => myRequest.post<AdInfo>('/adminapi/ads', data),

  update: (id: number, data: Partial<AdReq>) =>
    myRequest.put<void>(`/adminapi/ads/${id}`, data),

  delete: (id: number) => myRequest.delete<void>(`/adminapi/ads/${id}`),

  batchStatus: (ids: number[], status: 0 | 1) =>
    myRequest.post<void>('/adminapi/ads/batch-status', { ids, status }),
}
