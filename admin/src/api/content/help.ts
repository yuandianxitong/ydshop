import { myRequest } from '@/utils/request'
import type { HelpCategoryInfo } from './helpCategory'

export interface HelpInfo {
  id: number
  category_id: number
  category?: HelpCategoryInfo | null
  title: string
  summary: string
  content: string
  view_count: number
  status: number
  admin_id: number
  admin_name?: string
  created_at: string
  updated_at: string
}

export interface HelpReq {
  category_id: number
  title: string
  summary?: string
  content: string
  status: number
}

export const helpApi = {
  getList: (params?: { page?: number; limit?: number; page_no?: number; page_size?: number; category_id?: number; status?: number | string; keyword?: string }) =>
    myRequest.get<{ list: HelpInfo[]; pagination: any }>('/adminapi/helps/list', { params }),

  getDetail: (id: number) => myRequest.get<HelpInfo>(`/adminapi/helps/${id}`),

  create: (data: HelpReq) => myRequest.post<HelpInfo>('/adminapi/helps', data),

  update: (id: number, data: Partial<HelpReq>) =>
    myRequest.put<void>(`/adminapi/helps/${id}`, data),

  delete: (id: number) => myRequest.delete<void>(`/adminapi/helps/${id}`),

  batchStatus: (ids: number[], status: 0 | 1) =>
    myRequest.post<void>('/adminapi/helps/batch-status', { ids, status }),
}
