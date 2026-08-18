import type { GroupBuyInfo, GroupBuyQuery, GroupBuyReq, GroupListQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

export const groupBuyApi = {
    getGroupBuyList(params: GroupBuyQuery) {
        return myRequest.get<PageResult<GroupBuyInfo>>('/adminapi/marketing/group-buy', { params })
    },
    createGroupBuy(data: GroupBuyReq) {
        return myRequest.post<void>('/adminapi/marketing/group-buy', data)
    },
    updateGroupBuy(id: number, data: Partial<GroupBuyReq>) {
        return myRequest.put<void>(`/adminapi/marketing/group-buy/${id}`, data)
    },
    deleteGroupBuy(id: number) {
        return myRequest.delete<void>(`/adminapi/marketing/group-buy/${id}`)
    },
    getGroupList(params: GroupListQuery) {
        return myRequest.get<PageResult<any>>('/adminapi/marketing/group-buy/groups', { params })
    }
}
