import type {
    AdjustBalanceReq,
    AdjustPointsReq,
    MemberQuery,
    PageResult,
    UserItem
} from '@/types/api'
import { myRequest } from '@/utils/request'

export interface UserPreference {
    trend90: Array<{ date: string; orders: number; gmv: number }>
    payments: Array<{ pay_type: string; count: number; amount: number; percent: number }>
    hour_heat: Array<{ hour: number; count: number }>
    categories: Array<{ category_id: number; category_name: string; gmv: number; percent: number }>
    districts: Array<{ name: string; count: number; percent: number }>
    refund_rate: number
    repurchase_rate: number
}

export interface LifecycleStage {
    key: string
    date: string
    desc: string
}
export interface UserLifecycle {
    stages: LifecycleStage[]
    next: { title: string; desc: string }
}

export interface UserOperationLog {
    id: number
    user_id: number
    category: string
    event_code: string
    title: string
    description: string
    icon: string
    tone: string
    ref_type: string
    ref_id: number | null
    created_at: string
}

export interface UserCouponItem {
    id: number
    coupon_id: number
    user_id: number
    status: 'unused' | 'used' | 'expired'
    used_order_id: number
    used_at: string | null
    created_at: string
    name: string
    type: 'fixed' | 'percent' | 'no_threshold' | ''
    value: number
    min_amount: number
    end_at: string | null
}

export interface IssuableCoupon {
    id: number
    name: string
    type: string
    value: number
    min_amount: number
    end_at: string | null
    total_count: number | null
    used_count: number
    status: number
}

export interface SmsTemplate {
    id: number
    code: string
    name: string
    sms_template_id: string
    sms_enabled: number
    variables?: string[]
}

export interface MemberRemarkItem {
    id: number
    user_id: number
    content: string
    operator_id: number | null
    operator_name: string
    created_at: string
}

export interface MemberRewardReviewItem {
    id: number
    order_id: number
    order_no: string
    order_status: string
    user_id: number
    user_nickname: string
    user_mobile: string
    reward_amount: number
    points: number
    growth: number
    consume_amount: number
    order_count: number
    verification_status: 'verified' | 'partial' | 'unverified'
    verified_points: number
    verified_growth: number
    verified_consume_amount: number
    verified_order_count: number
    evidence: Record<string, any> | null
    review_status: 'none' | 'pending' | 'resolved'
    review_resolution: string
    review_reason: string
    review_operator_id: number | null
    reviewed_at: string | null
    awarded_at: string
}

export interface MemberRewardReviewResult {
    list: MemberRewardReviewItem[]
    pagination: { current_page: number; per_page: number; total: number; last_page: number }
    summary: { pending: number; partial: number; unverified: number; resolved: number }
}

export interface MemberRechargeGrowthReviewItem {
    id: number
    order_no: string
    user_id: number
    user_nickname: string
    user_mobile: string
    amount: number
    gift_amount: number
    gift_points: number
    pay_type: string
    paid_at: string | null
    settled_at: string | null
    expected_growth_value: number
    growth_review_status: 'none' | 'pending' | 'resolved'
    growth_review_resolution: '' | 'confirmed_applied' | 'confirmed_missing'
    growth_review_reason: string
    growth_review_operator_id: number | null
    growth_reviewed_at: string | null
}

export interface MemberRechargeGrowthReviewResult {
    list: MemberRechargeGrowthReviewItem[]
    pagination: { current_page: number; per_page: number; total: number; last_page: number }
    summary: { pending: number; resolved: number; expected_growth: number; credited_after_review: number }
}

/**
 * Member 用户管理 API
 */
export const memberApi = {
    /** 获取用户列表 */
    getUserList(params: MemberQuery) {
        return myRequest.get<PageResult<UserItem>>('/adminapi/member/user', { params })
    },

    /** 获取用户详情（含 tags / level_name / 计数） */
    getUserDetail(id: number) {
        return myRequest.get<UserItem>(`/adminapi/member/user/${id}`)
    },

    /** 编辑用户资料 */
    updateProfile(id: number, data: Partial<{
        nickname: string
        mobile: string
        email: string
        gender: number
        birthday: string
        avatar: string
    }>) {
        return myRequest.put<void>(`/adminapi/member/user/${id}`, data)
    },

    /** 单用户 KPI 聚合 */
    getUserStats(id: number) {
        return myRequest.get<{
            gmv: number
            orders: number
            avg_amount: number
            last_order_at: string | null
            balance: number
            points: number
        }>(`/adminapi/member/user/${id}/stats`)
    },

    /** 偏好分析（消费趋势 / 品类 / 支付 / 时段 / 地区 / 退款率 / 复购率） */
    getUserPreference(id: number) {
        return myRequest.get<UserPreference>(`/adminapi/member/user/${id}/preference`)
    },

    /** 生命周期阶段 */
    getUserLifecycle(id: number) {
        return myRequest.get<UserLifecycle>(`/adminapi/member/user/${id}/lifecycle`)
    },

    /** 操作日志 */
    getOperationLogs(id: number, params: { category?: string; page: number; limit: number }) {
        return myRequest.get<{
            categories: Record<string, number>
            list: UserOperationLog[]
            pagination: { current_page: number; per_page: number; total: number; last_page: number }
        }>(`/adminapi/member/user/${id}/operation-logs`, { params })
    },

    /** 调整余额 */
    adjustBalance(id: number, data: AdjustBalanceReq) {
        return myRequest.post<void>(`/adminapi/member/user/${id}/adjust-balance`, data)
    },

    /** 调整积分 */
    adjustPoints(id: number, data: AdjustPointsReq) {
        return myRequest.post<void>(`/adminapi/member/user/${id}/adjust-points`, data)
    },

    /** 备注列表 */
    listRemarks(id: number) {
        return myRequest.get<{ list: MemberRemarkItem[] }>(`/adminapi/member/user/${id}/remarks`)
    },
    addRemark(id: number, content: string) {
        return myRequest.post<MemberRemarkItem>(`/adminapi/member/user/${id}/remarks`, { content })
    },
    deleteRemark(id: number, rid: number) {
        return myRequest.delete<void>(`/adminapi/member/user/${id}/remarks/${rid}`)
    },

    /** 用户领取的优惠券 */
    listUserCoupons(id: number, params: { status?: string; page: number; limit: number }) {
        return myRequest.get<PageResult<UserCouponItem>>(`/adminapi/member/user/${id}/coupons`, { params })
    },
    /** 可发放的优惠券（弹窗下拉） */
    listIssuableCoupons() {
        return myRequest.get<{ list: IssuableCoupon[] }>('/adminapi/member/user-helpers/issuable-coupons')
    },
    issueCoupon(id: number, data: { coupon_id: number; count: number }) {
        return myRequest.post<{ issued: number }>(`/adminapi/member/user/${id}/coupons`, data)
    },

    /** 短信模板 */
    listSmsTemplates() {
        return myRequest.get<{ list: SmsTemplate[] }>('/adminapi/member/user-helpers/sms-templates')
    },
    sendSms(id: number, data: { template_code: string; variables: Record<string, string> }) {
        return myRequest.post<void>(`/adminapi/member/user/${id}/sms`, data)
    },

    /** 历史订单会员权益证据复核 */
    getRewardReviews(params: {
        page: number
        limit: number
        keyword?: string
        review_status?: string
        verification_status?: string
    }) {
        return myRequest.get<MemberRewardReviewResult>('/adminapi/member/reward-review', { params })
    },
    resolveRewardReview(id: number, reason: string) {
        return myRequest.post<{ applied: boolean; reason: string }>(
            `/adminapi/member/reward-review/${id}/resolve`,
            { reason }
        )
    },
    /** 历史充值成长值歧义复核 */
    getRechargeGrowthReviews(params: {
        page: number
        limit: number
        keyword?: string
        review_status?: string
        resolution?: string
    }) {
        return myRequest.get<MemberRechargeGrowthReviewResult>(
            '/adminapi/member/reward-review/recharges',
            { params }
        )
    },
    resolveRechargeGrowthReview(
        id: number,
        data: { resolution: 'confirmed_applied' | 'confirmed_missing'; reason: string }
    ) {
        return myRequest.post<{
            applied: boolean
            growth_added: boolean
            growth_value?: number
            resolution: string
        }>(`/adminapi/member/reward-review/recharges/${id}/resolve`, data)
    },
}
