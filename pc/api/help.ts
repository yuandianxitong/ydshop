import { get } from '~/composables/useRequest'

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

export interface HelpListResponse {
  list: HelpItem[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export const helpApi = {
  getCategories: () => get<HelpCategoryInfo[]>('/api/help/categories'),

  getList: (params?: { page_no?: number; page_size?: number; category_id?: number; keyword?: string }) =>
    get<HelpListResponse>('/api/help/list', params),

  getDetail: (id: number | string) => get<HelpDetail>(`/api/help/${id}`),
}
