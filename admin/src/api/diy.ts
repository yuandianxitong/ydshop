import { myRequest } from '@/utils/request'

export interface DiyPopupAd {
    enabled: boolean
    display_type: 'first' | 'every'
    image: string
    link: string
}

export interface DiyPageSettings {
    background_color: string
    background_image: string
    show_header?: boolean
    popup_ad?: DiyPopupAd
}

export interface DiyPageInfo {
    id: number
    page_type: string
    platform: string
    title: string
    components: DiyComponent[]
    page_settings: DiyPageSettings | null
    is_published: number
    is_default: number
    sort: number
    status: number
    page_type_text: string
    platform_text: string
    created_at: string
    updated_at: string
}

export interface DiyComponent {
    id: string
    type: string
    hidden?: boolean
    props: Record<string, any>
}

export interface DiyPageVersion {
    id: number
    page_id: number
    version_no: number
    title: string
    components: DiyComponent[]
    page_settings: DiyPageSettings | null
    note: string | null
    created_by: number | null
    created_by_name?: string
    created_at: string
}

export interface DiyCatalogLinkItem {
    label: string
    path: string
    need_select?: boolean
    select_type?: string | null
}

export interface DiyCatalogLinkGroup {
    key: string
    label: string
    items: DiyCatalogLinkItem[]
}

export interface DiyCatalogWidget {
    type: string
    label: string
    icon: string
    group: string
    default_props: Record<string, any>
}

export interface DiyCatalogEntry {
    slot: string
    label: string
    path: string
    platform: string
}

export interface DiyCatalog {
    platform: string
    links: DiyCatalogLinkGroup[]
    widgets: DiyCatalogWidget[]
    entries: DiyCatalogEntry[]
    public_paths: string[]
}

export const diyApi = {
    getPageList(params?: { platform?: string; page_type?: string; keyword?: string; page?: number; limit?: number }) {
        return myRequest.get<{ list: DiyPageInfo[]; pagination: any }>('/adminapi/diy/page', { params })
    },
    getPageDetail(id: number) {
        return myRequest.get<DiyPageInfo>(`/adminapi/diy/page/${id}`)
    },
    createPage(data: { page_type: string; platform: string; title: string; template_id?: number | null }) {
        return myRequest.post<DiyPageInfo>('/adminapi/diy/page', data)
    },
    updatePage(id: number, data: { title?: string; components?: DiyComponent[]; page_settings?: DiyPageSettings }) {
        return myRequest.put<void>(`/adminapi/diy/page/${id}`, data)
    },
    publishPage(id: number, isPublished: boolean, note?: string) {
        return myRequest.put<void>(`/adminapi/diy/page/${id}/publish`, {
            is_published: isPublished ? 1 : 0,
            note: note ?? '',
        })
    },
    setDefault(id: number) {
        return myRequest.put<void>(`/adminapi/diy/page/${id}/set-default`)
    },
    deletePage(id: number) {
        return myRequest.delete<void>(`/adminapi/diy/page/${id}`)
    },
    previewData(components: Array<{ type: string; props: Record<string, any> }>) {
        return myRequest.post<{ components: Array<{ type: string; props: Record<string, any> }> }>('/adminapi/diy/page/preview-data', { components })
    },
    listVersions(pageId: number) {
        return myRequest.get<DiyPageVersion[]>(`/adminapi/diy/page/${pageId}/versions`)
    },
    getVersion(pageId: number, vid: number) {
        return myRequest.get<DiyPageVersion>(`/adminapi/diy/page/${pageId}/versions/${vid}`)
    },
    restoreVersion(pageId: number, vid: number) {
        return myRequest.post<void>(`/adminapi/diy/page/${pageId}/versions/${vid}/restore`)
    },
    getCatalog(platform: 'uniapp' | 'pc' = 'uniapp') {
        return myRequest.get<DiyCatalog>('/adminapi/diy/catalog', { params: { platform } })
    },
}
