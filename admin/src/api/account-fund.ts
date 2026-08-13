import { myRequest } from '@/utils/request'

export interface FundStats {
  balance_total: number
  recharge_month: number
  withdraw_month: number
  pending_count: number
}

export interface BalanceLogInfo {
  id: number
  user_id: number
  user_nickname?: string
  amount: number
  before_balance: number
  after_balance: number
  type: number
  type_text?: string
  source?: string
  remark?: string
  created_at: string
}

export interface RechargeOrderInfo {
  id: number
  order_no: string
  user_id: number
  user_nickname?: string
  amount: number
  gift_amount: number
  pay_type: string
  status: number
  paid_at?: string
  created_at: string
}

export interface WithdrawalInfo {
  id: number
  user_id: number
  user_nickname?: string
  amount: number
  actual_amount: number
  fee: number
  type: 'wechat' | 'alipay' | 'bank'
  status: 'pending' | 'approved' | 'paid' | 'rejected'
  remark?: string
  paid_at?: string
  ledger_review_status?: 'none' | 'pending' | 'resolved'
  ledger_review_resolution?: 'debited_not_refunded' | 'not_debited' | 'already_refunded' | string
  ledger_review_reason?: string
  ledger_review_operator_id?: number | null
  ledger_reviewed_at?: string | null
  created_at: string
}

export const accountFundApi = {
  getStats() {
    return myRequest.get('/adminapi/member/fund/stats')
  },
  getBalanceLogs(params: Record<string, any>) {
    return myRequest.get('/adminapi/member/fund/balance-logs', { params })
  },
  getRechargeOrders(params: Record<string, any>) {
    return myRequest.get('/adminapi/member/fund/recharge-orders', { params })
  },
  getWithdrawals(params: Record<string, any>) {
    return myRequest.get('/adminapi/member/fund/withdrawals', { params })
  },
  approveWithdrawal(id: number) {
    return myRequest.post(`/adminapi/member/fund/withdrawal/${id}/approve`)
  },
  rejectWithdrawal(id: number, remark: string) {
    return myRequest.post(`/adminapi/member/fund/withdrawal/${id}/reject`, { remark })
  },
  payWithdrawal(id: number, data: { payout_reference: string; payout_proof?: string }) {
    return myRequest.post(`/adminapi/member/fund/withdrawal/${id}/pay`, data)
  },
}
