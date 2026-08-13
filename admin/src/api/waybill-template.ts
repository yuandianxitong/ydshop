import type { PageResult, StatusReq } from '@/types/api'
import type {
    WaybillCatalog,
    WaybillTemplateInfo,
    WaybillTemplateOption,
    WaybillTemplateQuery,
    WaybillTemplateReq
} from '@/types/waybill'
import { myRequest } from '@/utils/request'

export const waybillTemplateApi = {
    getCatalog() {
        return myRequest.get<WaybillCatalog>('/adminapi/delivery/waybill/catalog')
    },

    getOptions() {
        return myRequest.get<WaybillTemplateOption[]>('/adminapi/delivery/waybill/templates/options')
    },

    getList(params: WaybillTemplateQuery) {
        return myRequest.get<PageResult<WaybillTemplateInfo>>('/adminapi/delivery/waybill/templates', {
            params
        })
    },

    getDetail(id: number) {
        return myRequest.get<WaybillTemplateInfo>(`/adminapi/delivery/waybill/templates/${id}`)
    },

    create(data: WaybillTemplateReq) {
        return myRequest.post<WaybillTemplateInfo>('/adminapi/delivery/waybill/templates', data)
    },

    update(id: number, data: Partial<WaybillTemplateReq>) {
        return myRequest.put<void>(`/adminapi/delivery/waybill/templates/${id}`, data)
    },

    updateStatus(id: number, data: StatusReq) {
        return myRequest.put<void>(`/adminapi/delivery/waybill/templates/${id}/status`, data)
    },

    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/delivery/waybill/templates/${id}`)
    }
}
