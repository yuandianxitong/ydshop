import { get, post } from '~/composables/useRequest'
import type { PageResult } from '~/composables/useRequest'

export interface UserNotificationItem {
  id: number
  user_id: number
  title: string
  content: string
  type: 'system' | 'order' | 'payment' | 'feedback' | string
  type_text?: string
  biz_id?: number
  extra?: Record<string, any>
  is_read: boolean | number
  created_at: string
}

export const notificationApi = {
  getList: (params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<UserNotificationItem>>('/api/message/list', params),

  getUnreadCount: () =>
    get<{ count: number }>('/api/message/unread-count'),

  markRead: (ids: number[] = []) =>
    post('/api/message/read', { ids }),
}
