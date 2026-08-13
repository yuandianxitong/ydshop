import type { PageResult } from '@/types/common'
import { myRequest } from '@/utils/request'

export interface BusinessHourSlot {
    open: string
    close: string
}

export type BusinessHours = {
    mon?: BusinessHourSlot[] | 'closed'
    tue?: BusinessHourSlot[] | 'closed'
    wed?: BusinessHourSlot[] | 'closed'
    thu?: BusinessHourSlot[] | 'closed'
    fri?: BusinessHourSlot[] | 'closed'
    sat?: BusinessHourSlot[] | 'closed'
    sun?: BusinessHourSlot[] | 'closed'
}

export interface Store {
    id?: number
    name: string
    code: string
    address: string
    province?: string
    city?: string
    district?: string
    detail?: string
    region_code?: string
    lng: number
    lat: number
    phone?: string
    business_hours?: BusinessHours
    status: 0 | 1
    sort?: number
    remark?: string
    created_at?: string
    updated_at?: string
    is_open_now?: boolean
    status_text?: string
}

export interface StoreListQuery {
    page?: number
    limit?: number
    keyword?: string
    status?: 0 | 1
}

/**
 * Store 门店管理 API
 */
export const storeApi = {
    /** 列表 */
    list(params?: StoreListQuery) {
        return myRequest.get<PageResult<Store>>('/adminapi/store', { params })
    },

    /** 详情 */
    detail(id: number) {
        return myRequest.get<Store>(`/adminapi/store/${id}`)
    },

    /** 创建 */
    create(data: Store) {
        return myRequest.post<{ id: number }>('/adminapi/store', data)
    },

    /** 更新 */
    update(id: number, data: Store) {
        return myRequest.put<void>(`/adminapi/store/${id}`, data)
    },

    /** 删除 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/store/${id}`)
    },

    /** 订单自提核销 */
    pickupVerify(orderId: number, pickupCode: string) {
        return myRequest.put<void>(`/adminapi/order/order/${orderId}/pickup-verify`, { pickup_code: pickupCode })
    },
}
