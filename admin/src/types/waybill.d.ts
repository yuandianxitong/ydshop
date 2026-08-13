export interface WaybillCatalogItem {
    name: string
    exp_types: Array<{ value: string; label: string }>
    template_sizes: Array<{ value: string; label: string }>
}

export type WaybillCatalog = Record<string, WaybillCatalogItem>

export interface WaybillTemplateInfo {
    id: number
    name: string
    express_code: string
    express_name: string
    exp_type: string
    exp_type_name: string
    template_size: string
    template_size_label: string
    /** 1现付 2到付 3月结 */
    pay_type: number
    /** 1是 0否 — 快递员上门揽件 */
    need_pickup: number
    /** 1是 0否 — 默认模版 */
    is_default: number
    status: number
    sort: number
    created_at?: string
    updated_at?: string
}

export interface WaybillTemplateOption {
    id: number
    name: string
    express_code: string
    express_name: string
    exp_type: string
    exp_type_name: string
    template_size: string
    template_size_label: string
    pay_type: number
    need_pickup?: number
    is_default?: number
}

export interface WaybillTemplateQuery {
    keyword?: string
    status?: number | ''
    express_code?: string
    page?: number
    limit?: number
    [key: string]: any
}

export interface WaybillTemplateReq {
    name: string
    express_code: string
    express_name?: string
    exp_type: string
    exp_type_name?: string
    template_size?: string
    template_size_label?: string
    pay_type?: number
    need_pickup?: number
    is_default?: number
    status?: number
    sort?: number
}
