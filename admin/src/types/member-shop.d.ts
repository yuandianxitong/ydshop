import type { PageQuery } from './common'

// ========== C 端会员（管理后台视角，与 user.d.ts UserItem 互补） ==========
export interface MemberQuery extends PageQuery {
    status?: number
    level_id?: number
    group_id?: number
    tag_id?: number
    start_date?: string
    end_date?: string
}

export interface AdjustBalanceReq {
    amount?: number
    type: number | string
    remark?: string
    [key: string]: any
}

export interface AdjustPointsReq {
    points?: number
    type: number | string
    remark?: string
    [key: string]: any
}

// ========== 会员等级 ==========
export interface MemberLevelInfo {
    id: number
    name: string
    icon?: string
    upgrade_amount?: number
    upgrade_growth?: number
    discount?: number
    benefits?: string[]
    sort?: number
    status: number
    created_at?: string
    updated_at?: string
    [key: string]: any
}

export interface MemberLevelReq {
    id?: number
    name: string
    icon?: string
    upgrade_amount?: number
    upgrade_growth?: number
    discount?: number
    benefits?: string[]
    sort?: number
    status?: number
    [key: string]: any
}

export interface MemberLevelQuery extends PageQuery {
    status?: number
}

// ========== 充值套餐 ==========
export interface RechargePackageInfo {
    id: number
    name?: string
    amount?: number
    bonus?: number
    points?: number
    sort?: number
    status?: number | boolean
    sold?: number
    created_at?: string
    updated_at?: string
    [key: string]: any
}

export interface RechargePackageReq {
    id?: number
    name?: string
    amount?: number
    bonus?: number
    points?: number
    sort?: number
    status?: number | boolean
    [key: string]: any
}

export interface RechargePackageQuery extends PageQuery {
    status?: number
}

// ========== 用户分群 ==========
export interface UserGroupInfo {
    id: number
    name?: string
    description?: string
    rules?: any
    user_count?: number
    matched?: number
    last_refreshed_at?: string
    created_at?: string
    updated_at?: string
    [key: string]: any
}

export interface UserGroupReq {
    id?: number
    name: string
    description?: string
    rules?: any
    [key: string]: any
}

export interface UserGroupQuery extends PageQuery {
    [key: string]: any
}
