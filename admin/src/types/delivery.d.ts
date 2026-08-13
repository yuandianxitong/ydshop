import type { PageQuery, SelectOption } from './common'

// ========== 物流公司 ==========
export interface ExpressCompanyInfo {
    id: number
    name: string
    code: string
    logo?: string
    contact?: string
    phone?: string
    sort?: number
    status: number
    is_default?: number
    created_at?: string
    updated_at?: string
}

export interface ExpressCompanyReq {
    id?: number
    name: string
    code: string
    logo?: string
    contact?: string
    phone?: string
    sort?: number
    status?: number
    is_default?: number
}

export interface ExpressCompanyQuery extends PageQuery {
    status?: number
}

export type ExpressCompanyOption = SelectOption

// ========== 配送员 ==========
export interface DeliveryStaffInfo {
    id: number
    name: string
    phone: string
    avatar?: string
    region?: string
    vehicle_type?: string
    online_count?: number
    finished_count?: number
    status: number
    last_active_at?: string
    created_at?: string
    updated_at?: string
}

export interface DeliveryStaffReq {
    id?: number
    name: string
    phone: string
    avatar?: string
    region?: string
    vehicle_type?: string
    status?: number
}

export interface DeliveryStaffQuery extends PageQuery {
    status?: number
}

export interface DeliveryStaffOption {
    id: number
    name: string
    phone?: string
}

// ========== 配送记录 ==========
export interface DeliveryOrderInfo {
    id: number
    delivery_no: string
    order_no: string
    user_name?: string
    user_phone?: string
    address?: string
    staff_id?: number
    staff?: DeliveryStaffInfo
    status: string
    status_text?: string
    distance?: number
    estimated_time?: number
    fee?: number | string
    finished_at?: string
    created_at: string
    updated_at?: string
    // 三方同城配送
    platform?: string
    platform_order_id?: string
    platform_status?: string
    rider_name?: string
    rider_phone?: string
    rider_lat?: number | string
    rider_lng?: number | string
    dispatched_at?: string
    dispatch_fail_reason?: string
    order?: Record<string, any>
}

export interface DeliveryOrderQuery extends PageQuery {
    status?: string
    staff_id?: number
    platform?: string
    start_date?: string
    end_date?: string
}

// ========== 三方同城配送 ==========
export interface DeliveryPlatformOption {
    code: string
    label: string
}

export interface DeliveryOrderTrack {
    lat: number | string
    lng: number | string
    platform_status: string
    reported_at: string
}
