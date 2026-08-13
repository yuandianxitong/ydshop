import type {
    MemberLevelInfo,
    MemberLevelQuery,
    MemberLevelReq,
    PageResult
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * MemberLevel 会员等级管理 API
 */
export const memberLevelApi = {
    /** 获取列表 */
    getList(params: MemberLevelQuery) {
        return myRequest.get<PageResult<MemberLevelInfo>>('/adminapi/member/member-level', {
            params
        })
    },

    /** 创建 */
    create(data: MemberLevelReq) {
        return myRequest.post<void>('/adminapi/member/member-level', data)
    },

    /** 更新 */
    update(id: number, data: Partial<MemberLevelReq>) {
        return myRequest.put<void>(`/adminapi/member/member-level/${id}`, data)
    },

    /** 删除 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/member/member-level/${id}`)
    }
}
