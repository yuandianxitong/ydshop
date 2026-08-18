import type {
    FlashSaleInfo, FlashSaleItemInfo, FlashSaleItemReq, FlashSaleQuery, FlashSaleReq, PageResult
} from '@/types/api'
import { myRequest } from '@/utils/request'

export const flashSaleApi = {
    getFlashSaleList(params: FlashSaleQuery) {
        return myRequest.get<PageResult<FlashSaleInfo>>('/adminapi/marketing/flash-sale', { params })
    },
    createFlashSale(data: FlashSaleReq) {
        return myRequest.post<void>('/adminapi/marketing/flash-sale', data)
    },
    updateFlashSale(id: number, data: Partial<FlashSaleReq>) {
        return myRequest.put<void>(`/adminapi/marketing/flash-sale/${id}`, data)
    },
    deleteFlashSale(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/flash-sale/${id}`)
    },
    getFlashSaleItems(saleId: number, params?: { page?: number; limit?: number }) {
        return myRequest.get<PageResult<FlashSaleItemInfo>>(`/adminapi/marketing/flash-sale/${saleId}/items`, { params })
    },
    addFlashSaleItem(saleId: number, data: FlashSaleItemReq) {
        return myRequest.post<void>(`/adminapi/marketing/flash-sale/${saleId}/items`, data)
    },
    updateFlashSaleItem(saleId: number, itemId: number, data: Partial<FlashSaleItemReq>) {
        return myRequest.put<void>(`/adminapi/marketing/flash-sale/${saleId}/items/${itemId}`, data)
    },
    deleteFlashSaleItem(saleId: number, itemId: number) {
        return myRequest.delete<void>(`/adminapi/marketing/flash-sale/${saleId}/items/${itemId}`)
    }
}
