import type { PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

// ===== New User Gift =====

export interface NewUserGift {
    id: number
    name: string
    description: string
    status: number
    sort_order: number
    conditions: string[]
    points: number
    balance: number
    coupon_ids: number[]
    valid_start: string | null
    valid_end: string | null
    created_at: string
    updated_at: string
    claimed_count: number
}

export interface NewUserGiftQuery {
    page?: number
    limit?: number
    keyword?: string
    status?: number | string
}

export interface NewUserGiftRules {
    'new_user_gift.rules.limit_count': number
    'new_user_gift.rules.scenes': string[]
    'new_user_gift.rules.delivery_mode': 'immediate' | 'claim' | 'order'
    'new_user_gift.rules.risk_controls': string[]
}

export interface NewUserGiftStats {
    new_users: number
    recipients: number
    conversion_rate: number   // 0-100，一位小数
    gmv: number
}

export interface NewUserGiftLog {
    id: number
    user_id: number
    user_nickname: string
    user_avatar: string
    gift_id: number
    gift_name: string
    points_awarded: number
    balance_awarded: number
    coupon_ids: number[]
    created_at: string
}

export interface NewUserGiftLogQuery {
    page?: number
    limit?: number
    user_id?: number | string
    gift_id?: number | string
    date_start?: string
    date_end?: string
}

export const newUserGiftApi = {
    getList(params: NewUserGiftQuery) {
        return myRequest.get<PageResult<NewUserGift>>('/adminapi/marketing/new-user-gift', { params })
    },
    create(data: Partial<NewUserGift>) {
        return myRequest.post<{ id: number }>('/adminapi/marketing/new-user-gift', data)
    },
    update(id: number, data: Partial<NewUserGift>) {
        return myRequest.put<void>(`/adminapi/marketing/new-user-gift/${id}`, data)
    },
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/new-user-gift/${id}`)
    },
    getRules() {
        return myRequest.get<NewUserGiftRules>('/adminapi/marketing/new-user-gift/rules')
    },
    updateRules(data: Partial<NewUserGiftRules>) {
        return myRequest.put<void>('/adminapi/marketing/new-user-gift/rules', data)
    },
    getStats() {
        return myRequest.get<NewUserGiftStats>('/adminapi/marketing/new-user-gift/stats')
    },
    getLogs(params: NewUserGiftLogQuery) {
        return myRequest.get<PageResult<NewUserGiftLog>>('/adminapi/marketing/new-user-gift/logs', { params })
    },
}

