import type {
    AutoDispatchResult,
    BatchDeleteReq,
    DeliveryExceptionStatus,
    DeliveryExceptionTicket,
    DeliveryExceptionTicketQuery,
    DeliveryMapConfig,
    DeliveryMapOrder,
    DeliveryOrderInfo,
    DeliveryOrderQuery,
    DeliveryOrderTrack,
    DeliveryPlatformOption,
    DeliveryShift,
    DeliveryShiftQuery,
    DeliveryStaffInfo,
    DeliveryStaffOption,
    DeliveryStaffQuery,
    DeliveryStaffReq,
    PageResult,
    StatusReq
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * Delivery 配送员管理 API
 */
export const deliveryStaffApi = {
    /** 获取配送员列表 */
    getList(params: DeliveryStaffQuery) {
        return myRequest.get<PageResult<DeliveryStaffInfo>>('/adminapi/delivery/staff', { params })
    },

    /** 获取配送员下拉选项 */
    getOptions() {
        return myRequest.get<DeliveryStaffOption[]>('/adminapi/delivery/staff/options')
    },

    /** 获取配送员详情 */
    getDetail(id: number) {
        return myRequest.get<DeliveryStaffInfo>(`/adminapi/delivery/staff/${id}`)
    },

    /** 创建配送员 */
    create(data: DeliveryStaffReq) {
        return myRequest.post<void>('/adminapi/delivery/staff', data)
    },

    /** 更新配送员 */
    update(id: number, data: Partial<DeliveryStaffReq>) {
        return myRequest.put<void>(`/adminapi/delivery/staff/${id}`, data)
    },

    /** 更新配送员状态 */
    updateStatus(id: number, data: StatusReq) {
        return myRequest.put<void>(`/adminapi/delivery/staff/${id}/status`, data)
    },

    /** 删除配送员 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/delivery/staff/${id}`)
    },

    /** 批量删除配送员 */
    batchDelete(data: BatchDeleteReq) {
        return myRequest.post<void>('/adminapi/delivery/staff/batch-delete', data)
    }
}

/**
 * Delivery 配送记录 API
 */
export const deliveryOrderApi = {
    /** 获取配送记录列表 */
    getList(params: DeliveryOrderQuery) {
        return myRequest.get<PageResult<DeliveryOrderInfo>>('/adminapi/delivery/order', { params })
    },

    /** 分配配送员 */
    assign(id: number, data: { staff_id: number }) {
        return myRequest.post<void>(`/adminapi/delivery/order/${id}/assign`, data)
    },

    /** 更新配送状态 */
    updateStatus(id: number, data: { status: string }) {
        return myRequest.post<void>(`/adminapi/delivery/order/${id}/status`, data)
    },

    /** 一键自动派单（按当前在班 staff 工作量最少分配） */
    autoDispatch(orderIds: number[]) {
        return myRequest.post<AutoDispatchResult>('/adminapi/delivery/order/auto-dispatch', {
            order_ids: orderIds
        })
    },

    /** 获取已启用的三方配送平台选项 */
    getPlatformOptions() {
        return myRequest.get<DeliveryPlatformOption[]>('/adminapi/delivery/order/platform-options')
    },

    /** 向三方平台发单 */
    dispatch(id: number, data: { platform: string }) {
        return myRequest.post<void>(`/adminapi/delivery/order/${id}/dispatch`, data)
    },

    /** 主动同步三方平台配送状态 */
    sync(id: number) {
        return myRequest.post<void>(`/adminapi/delivery/order/${id}/sync`)
    },

    /** 获取配送单详情（含三方平台信息） */
    getDetail(id: number) {
        return myRequest.get<DeliveryOrderInfo>(`/adminapi/delivery/order/${id}`)
    },

    /** 获取三方配送轨迹点 */
    getTracks(id: number) {
        return myRequest.get<DeliveryOrderTrack[]>(`/adminapi/delivery/order/${id}/tracks`)
    }
}

/**
 * Delivery 配送异常工单 API（SP11）
 */
export const deliveryExceptionTicketApi = {
    /** 获取工单列表（分页 + 筛选） */
    getList(params?: DeliveryExceptionTicketQuery) {
        return myRequest.get<PageResult<DeliveryExceptionTicket>>(
            '/adminapi/delivery/exception-tickets',
            { params }
        )
    },

    /** 获取工单详情 */
    getDetail(id: number) {
        return myRequest.get<DeliveryExceptionTicket>(`/adminapi/delivery/exception-tickets/${id}`)
    },

    /** 创建工单 */
    create(data: Partial<DeliveryExceptionTicket>) {
        return myRequest.post<{ id: number }>('/adminapi/delivery/exception-tickets', data)
    },

    /** 编辑工单（不含状态流转） */
    update(id: number, data: Partial<DeliveryExceptionTicket>) {
        return myRequest.put(`/adminapi/delivery/exception-tickets/${id}`, data)
    },

    /** 状态流转：open → in_progress → resolved → closed */
    transition(id: number, to: DeliveryExceptionStatus, resolution_note?: string) {
        return myRequest.put(`/adminapi/delivery/exception-tickets/${id}/transition`, {
            to,
            resolution_note
        })
    },

    /** 软删工单 */
    delete(id: number) {
        return myRequest.delete(`/adminapi/delivery/exception-tickets/${id}`)
    }
}

/**
 * Delivery 实时地图 API（SP12）
 */
export const deliveryMapApi = {
    /** 获取地图配置（JS API Key + 安全码 + 默认城市） */
    getConfig() {
        return myRequest.get<DeliveryMapConfig>('/adminapi/delivery/map/config')
    },

    /** 获取地图订单数据（statuses 用逗号拼接 / since 为 ISO datetime） */
    getOrders(params: { statuses?: string; since?: string }) {
        return myRequest.get<DeliveryMapOrder[]>('/adminapi/delivery/map/orders', { params })
    }
}

/**
 * Delivery 排班管理 API（SP13）
 */
export const deliveryShiftApi = {
    /** 获取班次列表（分页 + 筛选） */
    getList(params?: DeliveryShiftQuery) {
        return myRequest.get<PageResult<DeliveryShift>>('/adminapi/delivery/shifts', { params })
    },

    /** 获取班次详情 */
    getDetail(id: number) {
        return myRequest.get<DeliveryShift>(`/adminapi/delivery/shifts/${id}`)
    },

    /** 创建班次 */
    create(data: Partial<DeliveryShift>) {
        return myRequest.post<{ id: number }>('/adminapi/delivery/shifts', data)
    },

    /** 编辑班次 */
    update(id: number, data: Partial<DeliveryShift>) {
        return myRequest.put(`/adminapi/delivery/shifts/${id}`, data)
    },

    /** 软删班次 */
    delete(id: number) {
        return myRequest.delete(`/adminapi/delivery/shifts/${id}`)
    }
}
