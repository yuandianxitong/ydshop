import { get, post } from '~/composables/useRequest'
import type { PageResult } from '~/composables/useRequest'

export interface FeedbackItem {
  id: number
  type: 'suggestion' | 'bug' | 'complaint' | 'other' | string
  content: string
  images?: string[]
  contact?: string
  status: number
  status_text?: string
  reply?: string
  replied_at?: string
  created_at: string
}

export interface FeedbackSubmitData {
  type: string
  content: string
  images?: string[]
  contact?: string
}

export const feedbackApi = {
  submit: (data: FeedbackSubmitData) =>
    post<FeedbackItem>('/api/feedback/submit', data),

  getList: (params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<FeedbackItem>>('/api/feedback/list', params),

  getDetail: (id: number | string) =>
    get<FeedbackItem>(`/api/feedback/detail/${id}`),
}
