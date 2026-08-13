import type {
    OrderAddressUpdateReq,
    OrderAdjustLog,
    OrderCancelReq,
    OrderInfo,
    OrderLogisticsReq,
    OrderMergeReq,
    OrderMergeResult,
    OrderPriceAdjustReq,
    OrderQuery,
    OrderRemarkReq,
    OrderSettings,
    OrderShipReq,
    OrderShipResult,
    OrderSplitReq,
    OrderSplitResult,
    OrderTrackingItem,
    PageResult,
    WaybillBatchResult
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * Order 订单管理 API
 */
export const orderApi = {
    /** 获取订单列表 */
    getOrderList(params: OrderQuery) {
        return myRequest.get<PageResult<OrderInfo>>('/adminapi/order/order', { params })
    },

    /** 获取订单详情 */
    getOrderDetail(id: number) {
        return myRequest.get<OrderInfo>(`/adminapi/order/order/${id}`)
    },

    /** 取消订单 */
    cancelOrder(id: number, data: OrderCancelReq) {
        return myRequest.post<void>(`/adminapi/order/order/${id}/cancel`, data)
    },

    /** 添加备注 */
    addRemark(id: number, data: OrderRemarkReq) {
        return myRequest.post<void>(`/adminapi/order/order/${id}/remark`, data)
    },

    /** 修改收货地址（pending/paid，非自提） */
    updateOrderAddress(id: number, data: OrderAddressUpdateReq) {
        return myRequest.put<void>(`/adminapi/order/order/${id}/address`, data)
    },

    /** 软删除订单（cancelled/closed） */
    deleteOrder(id: number) {
        return myRequest.delete<void>(`/adminapi/order/order/${id}`)
    },

    /** 发货 */
    shipOrder(data: OrderShipReq) {
        return myRequest.post<OrderShipResult>('/adminapi/order/ship', data)
    },

    /** 更新物流信息 */
    updateLogistics(id: number, data: OrderLogisticsReq) {
        return myRequest.put<void>(`/adminapi/order/ship/${id}/logistics`, data)
    },

    /** 获取物流轨迹 */
    getTracking(orderId: number) {
        return myRequest.get<{ traces: OrderTrackingItem[]; [key: string]: any }>(
            `/adminapi/order/ship/${orderId}/tracking`
        )
    },

    /** 获取交易设置 */
    getSettings() {
        return myRequest.get<OrderSettings>('/adminapi/order/settings')
    },

    /** 保存交易设置 */
    updateSettings(data: Partial<OrderSettings>) {
        return myRequest.put<void>('/adminapi/order/settings', data)
    },

    /** 订单改价（仅 pending，金额单位：元） */
    priceAdjust(id: number, data: OrderPriceAdjustReq) {
        return myRequest.post<OrderInfo>(`/adminapi/order/order/${id}/price-adjust`, data)
    },

    /** 订单拆单（仅 paid 未发货） */
    splitOrder(id: number, data: OrderSplitReq) {
        return myRequest.post<OrderSplitResult>(`/adminapi/order/order/${id}/split`, data)
    },

    /** 订单合并（同用户全 pending） */
    mergeOrders(data: OrderMergeReq) {
        return myRequest.post<OrderMergeResult>('/adminapi/order/order/merge', data)
    },

    /** 获取订单调整日志（拆单 / 合单 / 改价） */
    getAdjustLogs(id: number) {
        return myRequest.get<OrderAdjustLog[]>(`/adminapi/order/order/${id}/adjust-logs`)
    },

    /** 批量生成电子面单（快递鸟） */
    batchGenerateWaybill(orderIds: number[]) {
        return myRequest.post<WaybillBatchResult>('/adminapi/order/waybill/batch-generate', {
            order_ids: orderIds
        })
    }
}
