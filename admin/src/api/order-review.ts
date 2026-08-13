import { myRequest } from '@/utils/request'

/**
 * OrderReview 管理API
 */
export const orderReviewApi = {
    /**
     * 获取评价列表
     */
    getReviewList(params: Record<string, any>) {
        return myRequest.get('/adminapi/order/review', { params })
    },

    /**
     * 回复评价
     */
    replyReview(id: number, data: Record<string, any>) {
        return myRequest.post(`/adminapi/order/review/${id}/reply`, data)
    },
}
