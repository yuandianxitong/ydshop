import { get } from '~/composables/useRequest'
import type { PageResult } from '~/composables/useRequest'

export interface AnnouncementItem {
  id: number
  title: string
  content: string
  type: number
  type_text?: string
  publish_at?: string
  created_at: string
}

export const announcementApi = {
  getList: (params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<AnnouncementItem>>('/api/announcement/list', params),

  getDetail: (id: number | string) =>
    get<AnnouncementItem>(`/api/announcement/detail/${id}`),
}
