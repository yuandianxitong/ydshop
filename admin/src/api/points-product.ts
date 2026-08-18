import type {
    PageResult, PointsOrderInfo, PointsOrderQuery, PointsOrderShipReq,
    PointsProductInfo, PointsProductQuery, PointsProductReq
} from '@/types/api'
import { myRequest } from '@/utils/request'

export const pointsProductApi = {
    getList(params: PointsProductQuery) {
        return myRequest.get<PageResult<PointsProductInfo>>('/adminapi/marketing/points-product', { params })
    },
    create(data: PointsProductReq) {
        return myRequest.post<void>('/adminapi/marketing/points-product', data)
    },
    update(id: number, data: Partial<PointsProductReq>) {
        return myRequest.put<void>(`/adminapi/marketing/points-product/${id}`, data)
    },
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/points-product/${id}`)
    }
}

export const pointsOrderApi = {
    getList(params: PointsOrderQuery) {
        return myRequest.get<PageResult<PointsOrderInfo>>('/adminapi/marketing/points-order', { params })
    },
    ship(id: number, data: PointsOrderShipReq) {
        return myRequest.post<void>(`/adminapi/marketing/points-order/${id}/ship`, data)
    },
    cancel(id: number) {
        return myRequest.post<void>(`/adminapi/marketing/points-order/${id}/cancel`, {})
    }
}
