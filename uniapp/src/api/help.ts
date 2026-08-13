import { http } from '@/utils/request'
import type { PageResult } from '@/types/api'

export interface HelpCategoryInfo {
  id: number
  name: string
  icon: string
  sort: number
}

export interface HelpItem {
  id: number
  category_id: number
  category_name: string
  title: string
  summary: string
  view_count: number
  created_at: string
}

export interface HelpDetail extends HelpItem {
  content: string
  updated_at: string
}

export const helpApi = {
  getCategories: () => http.get<HelpCategoryInfo[]>('/api/help/categories'),

  getList: (params?: { page_no?: number; page_size?: number; category_id?: number; keyword?: string }) =>
    http.get<PageResult<HelpItem>>('/api/help/list', params),

  getDetail: (id: number | string) => http.get<HelpDetail>(`/api/help/${id}`),
}
