import http from '@/utils/request'

export const diyApi = {
    getPage: (params: { type: string; platform: string }) =>
        http.get<any>('/api/diy/page', params),
    getCustomPage: (id: number) =>
        http.get<any>(`/api/diy/page/${id}`),
}
