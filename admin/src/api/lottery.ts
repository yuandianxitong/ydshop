import type { PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

// ─── Lottery 抽奖活动 ──────────────────────────────────────────────────────────

export interface LotteryPrizeReq {
    id?: number
    position: number              // 1-8
    name: string
    image: string
    type: number                  // 1=优惠券 2=积分 3=谢谢参与
    reference_id: number          // 优惠券 id（type=1）
    value: number                 // 积分数（type=2）
    weight: number
    stock: number
}

export interface LotteryActivityReq {
    id?: number
    title: string
    cover: string
    description: string
    start_at: string
    end_at: string
    status: number
    daily_free_count: number
    points_per_draw: number
    address_expire_days: number
    prizes?: LotteryPrizeReq[]
}

export interface LotteryActivityInfo extends LotteryActivityReq {
    id: number
    created_at: string
    updated_at: string
}

export interface LotteryActivityQuery {
    page?: number
    limit?: number
    keyword?: string
    status?: number | string
}

export interface LotteryRecordItem {
    id: number
    user_id: number
    user_nickname: string
    user_avatar: string
    activity_id: number
    prize_id: number
    prize_type: number
    prize_name: string
    prize_value: number
    is_free: number
    cost_points: number
    created_at: string
}

export interface LotteryRecordQuery {
    page?: number
    limit?: number
    prize_type?: number | string
    user_id?: number | string
}

export interface LotteryCouponOption {
    id: number
    name: string
    type: string
    value: number
    end_at: string | null
}

export interface LotteryShipmentItem {
    id: number
    order_no: string
    user_id: number
    user_nickname?: string
    user_avatar?: string
    activity_id: number
    activity_title?: string
    record_id: number
    prize_id: number
    prize_name: string
    prize_image: string
    address_snapshot: {
        name: string
        phone: string
        province: string
        city: string
        district: string
        detail: string
        region_code?: string
    } | null
    express_company: string
    express_no: string
    status: 'pending' | 'shipped' | 'completed' | 'cancelled' | 'expired'
    status_text: string
    expire_at: string | null
    shipped_at: string | null
    completed_at: string | null
    cancelled_at: string | null
    cancel_reason: string
    created_at: string
}

export interface LotteryShipmentQuery {
    page?: number
    limit?: number
    status?: string
    activity_id?: number
    order_no?: string
    user_id?: number
}

export interface LotteryShipReq {
    express_company: string
    express_no: string
}

export const lotteryShipmentApi = {
    getList(params: LotteryShipmentQuery) {
        return myRequest.get<PageResult<LotteryShipmentItem>>('/adminapi/marketing/lottery/shipments', { params })
    },
    getDetail(id: number) {
        return myRequest.get<LotteryShipmentItem>(`/adminapi/marketing/lottery/shipments/${id}`)
    },
    ship(id: number, data: LotteryShipReq) {
        return myRequest.post<LotteryShipmentItem>(`/adminapi/marketing/lottery/shipments/${id}/ship`, data)
    },
    cancel(id: number, reason: string = '') {
        return myRequest.post<LotteryShipmentItem>(`/adminapi/marketing/lottery/shipments/${id}/cancel`, { reason })
    },
}

export const lotteryApi = {
    /** 抽奖活动列表 */
    getList(params: LotteryActivityQuery) {
        return myRequest.get<PageResult<LotteryActivityInfo>>('/adminapi/marketing/lottery', { params })
    },
    /** 抽奖活动详情（含 8 个奖品） */
    getDetail(id: number) {
        return myRequest.get<LotteryActivityInfo>(`/adminapi/marketing/lottery/${id}`)
    },
    /** 创建（含 8 个 prizes） */
    create(data: LotteryActivityReq) {
        return myRequest.post<LotteryActivityInfo>('/adminapi/marketing/lottery', data)
    },
    /** 更新 */
    update(id: number, data: Partial<LotteryActivityReq>) {
        return myRequest.put<LotteryActivityInfo>(`/adminapi/marketing/lottery/${id}`, data)
    },
    /** 删除 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/lottery/${id}`)
    },
    /** 抽奖记录（按活动） */
    getRecords(activityId: number, params: LotteryRecordQuery) {
        return myRequest.get<PageResult<LotteryRecordItem>>(`/adminapi/marketing/lottery/${activityId}/records`, { params })
    },
    /** 表单页可选优惠券下拉 */
    getCouponOptions() {
        return myRequest.get<LotteryCouponOption[]>('/adminapi/marketing/lottery/coupons')
    },
}

