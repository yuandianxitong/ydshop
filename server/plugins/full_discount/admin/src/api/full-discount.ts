import type { FullDiscountInfo, FullDiscountQuery, FullDiscountReq, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

export const fullDiscountApi = {
    getFullDiscountList(params: FullDiscountQuery) {
        return myRequest.get<PageResult<FullDiscountInfo>>('/adminapi/marketing/full-discount', { params })
    },
    createFullDiscount(data: FullDiscountReq) {
        return myRequest.post<void>('/adminapi/marketing/full-discount', data)
    },
    updateFullDiscount(id: number, data: Partial<FullDiscountReq>) {
        return myRequest.put<void>(`/adminapi/marketing/full-discount/${id}`, data)
    },
    deleteFullDiscount(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/full-discount/${id}`)
    }
}
