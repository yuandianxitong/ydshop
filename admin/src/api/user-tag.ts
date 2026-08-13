import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'
import type { Rules } from '@/components/RuleConditionEditor/index.vue'

export type TagGroupType = 'consume' | 'behavior' | 'lifecycle' | 'social'

export interface UserTagInfo {
  id: number
  name: string
  description: string
  color: string
  group_type: TagGroupType
  group_type_text: string
  rules: Rules | null
  auto_update: number
  user_count: number
  status: number
  sort: number
  created_at: string
  updated_at: string
}

export interface UserTagReq {
  name: string
  description?: string
  color?: string
  group_type?: TagGroupType
  rules?: Rules | null
  auto_update?: number
  sort?: number
  status?: number
}

export const userTagApi = {
  /** 分页列表（含搜索/分组/状态过滤） */
  getList(params: PageQuery & { keyword?: string; group_type?: TagGroupType; status?: number; auto_update?: number }) {
    return myRequest.get<PageResult<UserTagInfo>>('/adminapi/member/user-tag', { params })
  },

  /** 全部启用标签（无分页，4 列分组卡片用） */
  getAll() {
    return myRequest.get<UserTagInfo[]>('/adminapi/member/user-tag/all')
  },

  getDetail(id: number) {
    return myRequest.get<UserTagInfo>(`/adminapi/member/user-tag/${id}`)
  },

  create(data: UserTagReq) {
    return myRequest.post<void>('/adminapi/member/user-tag', data)
  },

  update(id: number, data: Partial<UserTagReq>) {
    return myRequest.put<void>(`/adminapi/member/user-tag/${id}`, data)
  },

  delete(id: number) {
    return myRequest.delete(`/adminapi/member/user-tag/${id}`)
  },

  /** 立即按规则刷新单个标签 */
  refresh(id: number) {
    return myRequest.post<{ count: number }>(`/adminapi/member/user-tag/${id}/refresh`)
  },

  assign(data: { user_ids: number[]; tag_ids: number[] }) {
    return myRequest.post('/adminapi/member/user-tag/assign', data)
  },

  remove(data: { user_ids: number[]; tag_ids: number[] }) {
    return myRequest.post('/adminapi/member/user-tag/remove', data)
  },
}
