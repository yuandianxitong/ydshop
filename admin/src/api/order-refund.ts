import type {
    OrderRefundApproveReq,
    OrderRefundInfo,
    OrderRefundQuery,
    OrderRefundRejectReq,
    PageResult
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * OrderRefund 退款管理 API
 */
export const orderRefundApi = {
    /** 获取退款列表 */
    getRefundList(params: OrderRefundQuery) {
        return myRequest.get<PageResult<OrderRefundInfo>>('/adminapi/order/refund', { params })
    },

    /** 获取退款详情 */
    getRefundDetail(id: number) {
        return myRequest.get<OrderRefundInfo>(`/adminapi/order/refund/${id}`)
    },

    /** 同意退款 */
    approveRefund(id: number, data: OrderRefundApproveReq) {
        return myRequest.post<void>(`/adminapi/order/refund/${id}/approve`, data)
    },

    /** 拒绝退款 */
    rejectRefund(id: number, data: OrderRefundRejectReq) {
        return myRequest.post<void>(`/adminapi/order/refund/${id}/reject`, data)
    },

    /** 确认收货 */
    confirmReceived(id: number) {
        return myRequest.post<void>(`/adminapi/order/refund/${id}/confirm-received`)
    },

    /** 重试/同步支付渠道处理中的退款 */
    retryRefund(id: number) {
        return myRequest.post<void>(`/adminapi/order/refund/${id}/retry`)
    }
}
