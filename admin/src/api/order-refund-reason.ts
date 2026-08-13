import { myRequest } from '@/utils/request'

/** 退款原因详情 */
export interface OrderRefundReasonInfo {
  id: number
  name: string
  sort: number
  status: number
  created_at: string
  updated_at: string
}

/** 退款原因创建/更新请求 */
export interface OrderRefundReasonReq {
  name: string
  sort?: number
  status?: number
}

/**
 * 退款原因模板管理 API
 */
export const orderRefundReasonApi = {
  /**
   * 获取所有退款原因（管理端）
   */
  getList() {
    return myRequest.get('/adminapi/order/refund-reason')
  },

  /**
   * 创建退款原因
   */
  create(data: OrderRefundReasonReq) {
    return myRequest.post('/adminapi/order/refund-reason', data)
  },

  /**
   * 更新退款原因
   */
  update(id: number, data: OrderRefundReasonReq) {
    return myRequest.put(`/adminapi/order/refund-reason/${id}`, data)
  },

  /**
   * 删除退款原因
   */
  delete(id: number) {
    return myRequest.delete(`/adminapi/order/refund-reason/${id}`)
  },
}
