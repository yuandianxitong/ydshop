import type { PageQuery } from './common'

// ========== 财务概览 ==========
export interface FinanceOverviewInfo {
    total_income: string | number
    total_expense: string | number
    net_amount: string | number
    today_income?: string | number
    today_expense?: string | number
    pending_withdrawal?: string | number
    [key: string]: any
}

export interface FinanceTrendItem {
    date: string
    income: string | number
    expense: string | number
}

export interface FinanceIncomeCompositionItem {
    name: string
    value: string | number
    ratio?: number
}

export interface FinanceWithdrawalStats {
    pending_count?: number
    pending_amount?: string | number
    paid_count?: number
    paid_amount?: string | number
    rejected_count?: number
    [key: string]: any
}

// ========== 资金流水 ==========
export interface FinanceTransactionInfo {
    id: number
    transaction_no: string
    type: 'income' | 'expense' | 'refund'
    biz_type: string
    biz_no?: string
    amount: string | number
    payment_channel?: string
    user_id?: number
    operator_id?: number
    remark?: string
    created_at: string
}

export interface FinanceTransactionQuery extends PageQuery {
    type?: string
    biz_type?: string
    start_date?: string
    end_date?: string
}

// ========== 提现 ==========
export interface WithdrawalUser {
    id: number
    nickname: string
    avatar?: string
}

export interface WithdrawalInfo {
    id: number
    user_id: number
    user?: WithdrawalUser
    amount: string | number
    fee: string | number
    actual_amount: string | number
    type: 'wechat' | 'alipay' | 'bank' | string
    status: 'pending' | 'approved' | 'paid' | 'rejected' | string
    source?: string
    remark?: string
    ledger_review_status?: 'none' | 'pending' | 'resolved'
    ledger_review_resolution?: 'debited_not_refunded' | 'not_debited' | 'already_refunded' | string
    ledger_review_reason?: string
    ledger_review_operator_id?: number | null
    ledger_reviewed_at?: string | null
    created_at: string
    updated_at?: string
}

export interface WithdrawalQuery extends PageQuery {
    status?: string
    status_in?: string
}

// ========== 余额 / 积分日志查询 ==========
export interface BalanceLogQuery extends PageQuery {
    type?: number
    start_date?: string
    end_date?: string
}

export interface PointsLogQuery extends PageQuery {
    type?: number
    start_date?: string
    end_date?: string
}

// ========== 提现规则（system_configs withdrawal 分组） ==========
export interface WithdrawalRuleConfig {
    withdrawal_min_amount?: number
    withdrawal_max_amount?: number
    withdrawal_daily_count?: number
    withdrawal_fee_rate?: number
    withdrawal_fee_min?: number
    withdrawal_fee_max?: number
    withdrawal_channels?: string[]
}

// ========== 积分规则总览（点击直达 system_configs / member-level） ==========
export interface PointsRulesOverview {
    register_gift: {
        enabled: boolean
        points: number
        config_url: string
    }
    sign_in: {
        base: number
        increment: number
        max: number
        continuous_bonus_days: number
        continuous_bonus_points: number
        config_url: string
    }
    member_levels: {
        levels: Array<{ id: number; name: string; points_rate: number }>
        config_url: string
    }
}
