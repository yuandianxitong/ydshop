import type {
    BatchDeleteReq,
    ExpressCompanyInfo,
    ExpressCompanyOption,
    ExpressCompanyQuery,
    ExpressCompanyReq,
    PageResult,
    StatusReq
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * 物流公司 API
 */
export const expressCompanyApi = {
    /** 获取物流公司列表 */
    getList(params: ExpressCompanyQuery) {
        return myRequest.get<PageResult<ExpressCompanyInfo>>('/adminapi/delivery/express-company', {
            params
        })
    },

    /** 获取物流公司下拉选项 */
    getOptions() {
        return myRequest.get<ExpressCompanyOption[]>('/adminapi/delivery/express-company/options')
    },

    /** 获取物流公司详情 */
    getDetail(id: number) {
        return myRequest.get<ExpressCompanyInfo>(`/adminapi/delivery/express-company/${id}`)
    },

    /** 创建物流公司 */
    create(data: ExpressCompanyReq) {
        return myRequest.post<void>('/adminapi/delivery/express-company', data)
    },

    /** 更新物流公司 */
    update(id: number, data: Partial<ExpressCompanyReq>) {
        return myRequest.put<void>(`/adminapi/delivery/express-company/${id}`, data)
    },

    /** 更新物流公司状态 */
    updateStatus(id: number, data: StatusReq) {
        return myRequest.put<void>(`/adminapi/delivery/express-company/${id}/status`, data)
    },

    /** 删除物流公司 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/delivery/express-company/${id}`)
    },

    /** 批量删除物流公司 */
    batchDelete(data: BatchDeleteReq) {
        return myRequest.post<void>('/adminapi/delivery/express-company/batch-delete', data)
    }
}
