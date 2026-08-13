import { get } from '~/composables/useRequest'

export const diyApi = {
    getPage: (params: { type: string; platform: string }) =>
        get<any>('/api/diy/page', params),
    getCustomPage: (id: number) =>
        get<any>(`/api/diy/page/${id}`),
}
