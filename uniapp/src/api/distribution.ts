import http from '@/utils/request'

export type CommissionStatus = 'pending' | 'settled' | 'cancelled' | 'reversed'
export type WithdrawalStatus = 'pending' | 'approved' | 'paid' | 'rejected'

export interface CommissionItem {
  id: number
  user_id: number
  order_id: number
  order_no: string
  amount: number
  credited_amount: number
  reversed_amount: number
  reversal_reason?: string
  level: 1 | 2 | 3
  /** 1=待结算 / 2=已结算 / 3=已取消 / 4=已冲正 */
  status: 1 | 2 | 3 | 4
  status_text: string
  created_at: string
}

export interface TeamMember {
  id: number
  nickname: string
  avatar: string
  mobile?: string
  is_distributor: boolean
  sub_team_count: number
  commission_to_me: number
  created_at: string
}

export interface TeamResult {
  list: TeamMember[]
  total: number
  summary: {
    total_count: number
    distributor_count: number
    total_contribution: number
  }
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export interface DistributionStats {
  is_distributor: boolean
  invite_code: string
  total_commission: number
  pending_commission: number
  month_commission: number
  settled_commission: number
  withdrawable_amount: number
  commission_entitlement_amount: number
  commission_debt: number
  withdrawn_amount: number
  team_count: number
  invite_count: number
  invite_order_count: number
}

export interface WithdrawalItem {
  id: number
  user_id: number
  amount: number
  status: number
  status_text: string
  channel: string
  account: string
  remark?: string
  created_at: string
}

export interface WithdrawalData {
  amount: number
  channel: string
  account: string
  real_name: string
  bank_name?: string
}

export interface PageResult<T> {
  list: T[]
  total: number
  page_no: number
  page_size: number
}

export const distributionApi = {
  applyDistributor: () =>
    http.post('/api/distribution/apply'),

  bindInviter: (data: { invite_code: string }) =>
    http.post('/api/distribution/bind-inviter', data),

  getMyCommissions: (params?: { page_no?: number; page_size?: number; status?: CommissionStatus }) =>
    http.get<PageResult<CommissionItem>>('/api/distribution/my-commissions', params),

  getDistributionStats: () =>
    http.get<DistributionStats>('/api/distribution/stats'),

  getMyTeam: (params?: { page_no?: number; page_size?: number; only_distributor?: 0 | 1 }) =>
    http.get<TeamResult>('/api/distribution/team', params),

  getMyWithdrawals: (params?: { page_no?: number; page_size?: number; status?: WithdrawalStatus }) =>
    http.get<PageResult<WithdrawalItem>>('/api/distribution/my-withdrawals', params),

  applyWithdrawal: (data: WithdrawalData) =>
    http.post<WithdrawalItem>('/api/distribution/apply-withdrawal', data),
}
