import type {
    CouponInfo,
    CouponQuery,
    CouponReq,
    FlashSaleInfo,
    FlashSaleItemInfo,
    FlashSaleItemReq,
    FlashSaleQuery,
    FlashSaleReq,
    FullDiscountInfo,
    FullDiscountQuery,
    FullDiscountReq,
    GroupBuyInfo,
    GroupBuyQuery,
    GroupBuyReq,
    GroupListQuery,
    PageResult,
    PointsOrderInfo,
    PointsOrderQuery,
    PointsOrderShipReq,
    PointsProductInfo,
    PointsProductQuery,
    PointsProductReq
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * Marketing 营销活动 API
 */

// ─── Coupon 优惠券 ────────────────────────────────────────────────────────────

export const couponApi = {
    /** 获取优惠券列表 */
    getCouponList(params: CouponQuery) {
        return myRequest.get<PageResult<CouponInfo>>('/adminapi/marketing/coupon', { params })
    },

    /** 创建优惠券 */
    createCoupon(data: CouponReq) {
        return myRequest.post<void>('/adminapi/marketing/coupon', data)
    },

    /** 更新优惠券 */
    updateCoupon(id: number, data: Partial<CouponReq>) {
        return myRequest.put<void>(`/adminapi/marketing/coupon/${id}`, data)
    },

    /** 删除优惠券 */
    deleteCoupon(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/coupon/${id}`)
    }
}

// ─── Full Discount 满减活动 ────────────────────────────────────────────────────

export const fullDiscountApi = {
    /** 获取满减活动列表 */
    getFullDiscountList(params: FullDiscountQuery) {
        return myRequest.get<PageResult<FullDiscountInfo>>('/adminapi/marketing/full-discount', {
            params
        })
    },

    /** 创建满减活动 */
    createFullDiscount(data: FullDiscountReq) {
        return myRequest.post<void>('/adminapi/marketing/full-discount', data)
    },

    /** 更新满减活动 */
    updateFullDiscount(id: number, data: Partial<FullDiscountReq>) {
        return myRequest.put<void>(`/adminapi/marketing/full-discount/${id}`, data)
    },

    /** 删除满减活动 */
    deleteFullDiscount(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/full-discount/${id}`)
    }
}

// ─── Flash Sale 限时秒杀 ───────────────────────────────────────────────────────

export const flashSaleApi = {
    /** 获取秒杀活动列表 */
    getFlashSaleList(params: FlashSaleQuery) {
        return myRequest.get<PageResult<FlashSaleInfo>>('/adminapi/marketing/flash-sale', {
            params
        })
    },

    /** 创建秒杀活动 */
    createFlashSale(data: FlashSaleReq) {
        return myRequest.post<void>('/adminapi/marketing/flash-sale', data)
    },

    /** 更新秒杀活动 */
    updateFlashSale(id: number, data: Partial<FlashSaleReq>) {
        return myRequest.put<void>(`/adminapi/marketing/flash-sale/${id}`, data)
    },

    /** 删除秒杀活动 */
    deleteFlashSale(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/flash-sale/${id}`)
    },

    /** 获取秒杀商品列表 */
    getFlashSaleItems(saleId: number, params?: { page?: number; limit?: number }) {
        return myRequest.get<PageResult<FlashSaleItemInfo>>(
            `/adminapi/marketing/flash-sale/${saleId}/items`,
            { params }
        )
    },

    /** 添加秒杀商品 */
    addFlashSaleItem(saleId: number, data: FlashSaleItemReq) {
        return myRequest.post<void>(`/adminapi/marketing/flash-sale/${saleId}/items`, data)
    },

    /** 更新秒杀商品 */
    updateFlashSaleItem(saleId: number, itemId: number, data: Partial<FlashSaleItemReq>) {
        return myRequest.put<void>(
            `/adminapi/marketing/flash-sale/${saleId}/items/${itemId}`,
            data
        )
    },

    /** 删除秒杀商品 */
    deleteFlashSaleItem(saleId: number, itemId: number) {
        return myRequest.delete<void>(`/adminapi/marketing/flash-sale/${saleId}/items/${itemId}`)
    }
}

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

// ─── Group Buy 拼团活动 ────────────────────────────────────────────────────────

export const groupBuyApi = {
    /** 获取拼团活动列表 */
    getGroupBuyList(params: GroupBuyQuery) {
        return myRequest.get<PageResult<GroupBuyInfo>>('/adminapi/marketing/group-buy', { params })
    },

    /** 创建拼团活动 */
    createGroupBuy(data: GroupBuyReq) {
        return myRequest.post<void>('/adminapi/marketing/group-buy', data)
    },

    /** 更新拼团活动 */
    updateGroupBuy(id: number, data: Partial<GroupBuyReq>) {
        return myRequest.put<void>(`/adminapi/marketing/group-buy/${id}`, data)
    },

    /** 删除拼团活动 */
    deleteGroupBuy(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/group-buy/${id}`)
    },

    /** 获取团列表（某活动下的拼团记录） */
    getGroupList(params: GroupListQuery) {
        return myRequest.get<PageResult<any>>('/adminapi/marketing/group-buy/groups', { params })
    }
}

// ─── Points Product 积分商品 ───────────────────────────────────────────────────

export const pointsProductApi = {
    /** 获取积分商品列表 */
    getList(params: PointsProductQuery) {
        return myRequest.get<PageResult<PointsProductInfo>>('/adminapi/marketing/points-product', {
            params
        })
    },

    /** 创建积分商品 */
    create(data: PointsProductReq) {
        return myRequest.post<void>('/adminapi/marketing/points-product', data)
    },

    /** 更新积分商品 */
    update(id: number, data: Partial<PointsProductReq>) {
        return myRequest.put<void>(`/adminapi/marketing/points-product/${id}`, data)
    },

    /** 删除积分商品 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/points-product/${id}`)
    }
}

// ─── Points Order 积分订单 ─────────────────────────────────────────────────────

export const pointsOrderApi = {
    /** 获取积分订单列表（管理端） */
    getList(params: PointsOrderQuery) {
        return myRequest.get<PageResult<PointsOrderInfo>>('/adminapi/marketing/points-order', {
            params
        })
    },

    /** 发货 */
    ship(id: number, data: PointsOrderShipReq) {
        return myRequest.post<void>(`/adminapi/marketing/points-order/${id}/ship`, data)
    },

    /** 取消订单（管理端） */
    cancel(id: number) {
        return myRequest.post<void>(`/adminapi/marketing/points-order/${id}/cancel`, {})
    }
}

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
