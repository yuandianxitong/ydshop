import { myRequest } from '@/utils/request'

export interface DiyTemplateInfo {
    id: number
    name: string
    cover: string
    platform: 'uniapp' | 'pc'
    page_type: 'home' | 'custom'
    components: any[]
    is_system: number
    sort: number
    status: number
    created_at: string
    updated_at: string
}

export const diyTemplateApi = {
    getList(params?: { platform?: string; page_type?: string; page?: number; limit?: number }) {
        return myRequest.get<{ list: DiyTemplateInfo[]; pagination: any }>('/adminapi/diy/template', { params })
    },
    getDetail(id: number) {
        return myRequest.get<DiyTemplateInfo>(`/adminapi/diy/template/${id}`)
    },
    create(data: Partial<DiyTemplateInfo>) {
        return myRequest.post<DiyTemplateInfo>('/adminapi/diy/template', data)
    },
    update(id: number, data: Partial<DiyTemplateInfo>) {
        return myRequest.put<void>(`/adminapi/diy/template/${id}`, data)
    },
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/diy/template/${id}`)
    },
}
