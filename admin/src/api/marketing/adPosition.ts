import { myRequest } from '@/utils/request'

export interface AdPositionInfo {
  id: number
  code: string
  name: string
  description: string
  recommended_width: number
  recommended_height: number
  is_carousel: number
  sort: number
  status: number
  created_at: string
  updated_at: string
}

export interface AdPositionReq {
  code?: string
  name: string
  description?: string
  recommended_width?: number
  recommended_height?: number
  is_carousel: number
  sort?: number
  status: number
}

export const adPositionApi = {
  getList: (params?: { page?: number; limit?: number; page_no?: number; page_size?: number; keyword?: string; status?: number | string }) =>
    myRequest.get<{ list: AdPositionInfo[]; pagination: any }>('/adminapi/ad-positions/list', { params }),

  getAll: () => myRequest.get<AdPositionInfo[]>('/adminapi/ad-positions/all'),

  getDetail: (id: number) => myRequest.get<AdPositionInfo>(`/adminapi/ad-positions/${id}`),

  create: (data: AdPositionReq) => myRequest.post<AdPositionInfo>('/adminapi/ad-positions', data),

  update: (id: number, data: Partial<AdPositionReq>) =>
    myRequest.put<void>(`/adminapi/ad-positions/${id}`, data),

  delete: (id: number) => myRequest.delete<void>(`/adminapi/ad-positions/${id}`),
}
