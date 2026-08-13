import type {
    PageResult,
    RechargePackageInfo,
    RechargePackageQuery,
    RechargePackageReq
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * RechargePackage 充值套餐管理 API
 */
export const rechargePackageApi = {
    /** 获取列表 */
    getList(params: RechargePackageQuery) {
        return myRequest.get<PageResult<RechargePackageInfo>>(
            '/adminapi/member/recharge-package',
            { params }
        )
    },

    /** 创建 */
    create(data: RechargePackageReq) {
        return myRequest.post<void>('/adminapi/member/recharge-package', data)
    },

    /** 更新 */
    update(id: number, data: Partial<RechargePackageReq>) {
        return myRequest.put<void>(`/adminapi/member/recharge-package/${id}`, data)
    },

    /** 删除 */
    delete(id: number) {
        return myRequest.delete<void>(`/adminapi/member/recharge-package/${id}`)
    }
}
