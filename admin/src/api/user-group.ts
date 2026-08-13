import type {
    PageResult,
    UserGroupInfo,
    UserGroupQuery,
    UserGroupReq
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * UserGroup 用户分群管理 API
 */
export const userGroupApi = {
    /** 获取分群列表 */
    getList(params: UserGroupQuery) {
        return myRequest.get<PageResult<UserGroupInfo>>('/adminapi/member/user-group', { params })
    },

    /** 创建分群 */
    create(data: UserGroupReq) {
        return myRequest.post<void>('/adminapi/member/user-group', data)
    },

    /** 更新分群 */
    update(id: number, data: Partial<UserGroupReq>) {
        return myRequest.put<void>(`/adminapi/member/user-group/${id}`, data)
    },

    /** 删除分群 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/member/user-group/${id}`)
    },

    /** 获取分群详情（含用户） */
    show(id: number, params?: { page?: number; limit?: number }) {
        return myRequest.get<UserGroupInfo>(`/adminapi/member/user-group/${id}`, { params })
    },

    /** 触发分群刷新，返回匹配的用户数 */
    refresh(id: number) {
        return myRequest.post<{ matched: number }>(
            `/adminapi/member/user-group/${id}/refresh`,
            {}
        )
    },

    /** 添加用户 */
    addUsers(id: number, userIds: number[]) {
        return myRequest.post<void>(`/adminapi/member/user-group/${id}/add-users`, {
            user_ids: userIds
        })
    },

    /** 移除用户 */
    removeUsers(id: number, userIds: number[]) {
        return myRequest.post<void>(`/adminapi/member/user-group/${id}/remove-users`, {
            user_ids: userIds
        })
    }
}
