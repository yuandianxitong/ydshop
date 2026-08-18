import type { CouponInfo, CouponQuery, CouponReq, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

export const couponApi = {
    getCouponList(params: CouponQuery) {
        return myRequest.get<PageResult<CouponInfo>>('/adminapi/marketing/coupon', { params })
    },
    createCoupon(data: CouponReq) {
        return myRequest.post<void>('/adminapi/marketing/coupon', data)
    },
    updateCoupon(id: number, data: Partial<CouponReq>) {
        return myRequest.put<void>(`/adminapi/marketing/coupon/${id}`, data)
    },
    deleteCoupon(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/coupon/${id}`)
    }
}
