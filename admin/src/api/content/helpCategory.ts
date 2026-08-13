import { myRequest } from '@/utils/request'

export interface HelpCategoryInfo {
  id: number
  name: string
  icon: string
  sort: number
  status: number
  help_count?: number
  created_at: string
  updated_at: string
}

export interface HelpCategoryReq {
  name: string
  icon?: string
  sort?: number
  status: number
}

export const helpCategoryApi = {
  getList: (params?: { page?: number; limit?: number; page_no?: number; page_size?: number; keyword?: string; status?: number | string }) =>
    myRequest.get<{ list: HelpCategoryInfo[]; pagination: any }>('/adminapi/help-categories/list', { params }),

  getAll: () => myRequest.get<HelpCategoryInfo[]>('/adminapi/help-categories/all'),

  getDetail: (id: number) => myRequest.get<HelpCategoryInfo>(`/adminapi/help-categories/${id}`),

  create: (data: HelpCategoryReq) => myRequest.post<HelpCategoryInfo>('/adminapi/help-categories', data),

  update: (id: number, data: Partial<HelpCategoryReq>) =>
    myRequest.put<void>(`/adminapi/help-categories/${id}`, data),

  delete: (id: number) => myRequest.delete<void>(`/adminapi/help-categories/${id}`),
}
