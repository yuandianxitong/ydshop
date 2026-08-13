import type { PageQuery, PageResult } from './common'

// ========== 分销商 ==========
export interface DistributorUser {
    id: number
    nickname?: string
    mobile?: string
    avatar?: string
}

export interface DistributorInfo {
    id: number
    user_id: number
    user?: DistributorUser
    level?: number
    parent_id?: number
    parent?: DistributorUser
    team_count?: number
    direct_count?: number
    gmv_total?: number
    commission_total?: number
    withdrawn?: number
    available?: number
    status: 'pending' | 'active' | 'paused' | 'rejected' | string
    remark?: string
    created_at: string
    updated_at?: string
    [key: string]: any
}

export interface DistributorQuery extends PageQuery {
    status?: string
    level?: number
}

// ========== 佣金记录 ==========
export interface CommissionInfo {
    id: number
    user_id: number
    from_user_id: number
    order_id: number
    order_item_id: number
    level: 1 | 2 | 3
    user?: DistributorUser
    from_user?: DistributorUser
    order_no?: string
    order_amount?: number
    base_amount: number
    amount: number
    rate: number
    credited_amount: number
    reversed_amount: number
    status: 'pending' | 'settled' | 'cancelled' | 'reversed'
    status_text?: string
    settled_at?: string
    reversed_at?: string
    reversal_reason?: string
    review_status: 'none' | 'pending' | 'resolved'
    review_kind?: 'refund_evidence' | 'settlement_evidence' | 'entitlement_evidence' | 'withdrawal_evidence' | string
    review_context?: Record<string, any> | null
    review_resolution?: string
    review_reason?: string
    review_verified_credited_amount?: number
    review_operator_id?: number | null
    reviewed_at?: string | null
    created_at: string
    [key: string]: any
}

export interface CommissionQuery extends PageQuery {
    distributor_id?: number
    status?: string
    start_date?: string
    end_date?: string
    review_status?: string
}

export interface CommissionSummary {
    distributor_count: number
    distinct_order_gmv: number
    credited_amount: number
    pending_amount: number
    pending_count: number
    review_pending_count: number
}

export interface CommissionListResult extends PageResult<CommissionInfo> {
    summary: CommissionSummary
}

// ========== 提现申请 ==========
export interface WithdrawalRequestInfo {
    id: number
    user_id: number
    user?: DistributorUser
    user_nickname?: string
    user_mobile?: string
    user_avatar?: string
    amount: number
    fee: number
    actual_amount: number
    type: 'wechat' | 'alipay' | 'bank' | string
    account_info?: {
        account?: string
        real_name?: string
        bank_name?: string
    }
    status: 'pending' | 'approved' | 'paid' | 'rejected' | string
    remark?: string
    paid_at?: string
    ledger_review_status: 'none' | 'pending' | 'resolved'
    ledger_review_resolution?: 'debited_not_refunded' | 'not_debited' | 'already_refunded' | string
    ledger_review_reason?: string
    ledger_review_context?: Record<string, any> | null
    ledger_review_operator_id?: number | null
    ledger_reviewed_at?: string | null
    created_at: string
    updated_at?: string
    [key: string]: any
}

export interface WithdrawalRequestQuery extends PageQuery {
    status?: string
    status_in?: string
}

export interface WithdrawalRejectReq {
    remark: string
}
