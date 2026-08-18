import type { PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

// ===== Sign Config & Sign Log =====

export interface SignConfig {
    'sign.points_base': number
    'sign.points_increment': number
    'sign.points_max': number
    'sign.continuous_bonus_days': number
    'sign.continuous_bonus_points': number
    'sign.makeup_enabled': string         // '0' | '1'
    'sign.makeup_currency': string        // 'points' | 'balance'
    'sign.makeup_price': number
    'sign.makeup_days_limit': number
}

export interface SignLogStats {
    today_count: number
    continuous_7_users: number
    month_count: number
    month_points: number
}

export interface SignLogItem {
    id: number
    user_id: number
    user_nickname: string
    user_avatar: string
    sign_date: string
    continuous_days: number
    points_awarded: number
    is_makeup: number
    source: string
    created_at: string
}

export interface SignLogQuery {
    page?: number
    limit?: number
    user_id?: number | string
    sign_date_start?: string
    sign_date_end?: string
    is_makeup?: number | string
    source?: string
}

// ─── Sign Config 签到配置 ──────────────────────────────────────────────────────

export const signConfigApi = {
    /** 获取签到配置 */
    getConfig() {
        return myRequest.get<SignConfig>('/adminapi/marketing/sign-config')
    },

    /** 更新签到配置 */
    updateConfig(data: Partial<SignConfig>) {
        return myRequest.put<void>('/adminapi/marketing/sign-config', data)
    }
}

// ─── Sign Log 签到记录 ─────────────────────────────────────────────────────────

export const signLogApi = {
    /** 获取签到记录列表 */
    getList(params: SignLogQuery) {
        return myRequest.get<PageResult<SignLogItem>>('/adminapi/marketing/sign-config/logs', { params })
    },

    /** 获取签到统计 */
    getStats() {
        return myRequest.get<SignLogStats>('/adminapi/marketing/sign-config/stats')
    }
}
