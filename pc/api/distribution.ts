import { get, post } from '~/composables/useRequest'
import type { PageResult } from '~/composables/useRequest'

export type CommissionStatus = 'pending' | 'settled' | 'cancelled' | 'reversed'
export type WithdrawalStatus = 'pending' | 'approved' | 'paid' | 'rejected'

export interface CommissionItem {
  id: number
  user_id: number
  order_id: number
  order_no: string
  amount: number | string
  credited_amount: number | string
  reversed_amount: number | string
  reversal_reason?: string
  /** 1=一级 / 2=二级 / 3=三级 */
  level: 1 | 2 | 3
  /** 1=待结算 / 2=已结算 / 3=已取消 / 4=已冲正 */
  status: 1 | 2 | 3 | 4
  status_text: string
  created_at: string
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
  /** 申请金额 */
  amount: number | string
  /** 手续费 */
  fee?: number | string
  /** 实际到账金额 */
  actual_amount?: number | string
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

export const distributionApi = {
  applyDistributor: () =>
    post('/api/distribution/apply'),

  bindInviter: (data: { invite_code: string }) =>
    post('/api/distribution/bind-inviter', data),

  getMyCommissions: (params?: { page_no?: number; page_size?: number; status?: CommissionStatus }) =>
    get<PageResult<CommissionItem>>('/api/distribution/my-commissions', params),

  getDistributionStats: () =>
    get<DistributionStats>('/api/distribution/stats'),

  getMyWithdrawals: (params?: { page_no?: number; page_size?: number; status?: WithdrawalStatus }) =>
    get<PageResult<WithdrawalItem>>('/api/distribution/my-withdrawals', params),

  applyWithdrawal: (data: WithdrawalData) =>
    post<WithdrawalItem>('/api/distribution/apply-withdrawal', data),
}
