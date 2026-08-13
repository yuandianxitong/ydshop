import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

export interface DistributionLevelUpgradeCondition {
  field: 'team_count' | 'gmv' | 'order_count'
  op: '>=' | '>'
  value: number
}

export interface DistributionLevelInfo {
  id: number
  name: string
  first_rate: number
  second_rate: number
  third_rate: number
  upgrade_condition: DistributionLevelUpgradeCondition | null
  sort: number
  status: number
  created_at: string
  updated_at: string
}

export interface DistributionLevelReq {
  name: string
  first_rate?: number
  second_rate?: number
  third_rate?: number
  upgrade_condition?: DistributionLevelUpgradeCondition | null
  sort?: number
  status?: number
}

export const distributionLevelApi = {
  getList(params: PageQuery & { keyword?: string; status?: number }) {
    return myRequest.get<PageResult<DistributionLevelInfo>>('/adminapi/distribution/distribution-level', { params })
  },
  /** 启用中的全部等级（无分页），用于佣金规则卡片 / 分销商列表 join */
  getOptions() {
    return myRequest.get<DistributionLevelInfo[]>('/adminapi/distribution/distribution-level/options')
  },
  getDetail(id: number) {
    return myRequest.get<DistributionLevelInfo>(`/adminapi/distribution/distribution-level/${id}`)
  },
  create(data: DistributionLevelReq) {
    return myRequest.post<void>('/adminapi/distribution/distribution-level', data)
  },
  update(id: number, data: Partial<DistributionLevelReq>) {
    return myRequest.put<void>(`/adminapi/distribution/distribution-level/${id}`, data)
  },
  delete(id: number) {
    return myRequest.delete(`/adminapi/distribution/distribution-level/${id}`)
  },
}
