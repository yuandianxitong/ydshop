import type {
    CommissionListResult,
    CommissionQuery,
    DistributorInfo,
    DistributorQuery,
    PageResult,
    WithdrawalRejectReq,
    WithdrawalRequestInfo,
    WithdrawalRequestQuery
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * Distribution 分销管理 API
 */
export const distributionApi = {
    /** 获取分销商列表 */
    getDistributorList(params: DistributorQuery) {
        return myRequest.get<PageResult<DistributorInfo>>('/adminapi/distribution/distributor', {
            params
        })
    },

    /** 获取分销商详情 */
    getDistributorDetail(id: number) {
        return myRequest.get<DistributorInfo>(`/adminapi/distribution/distributor/${id}`)
    },

    /** 获取分销商直属团队 */
    getDistributorTeam(id: number, params?: { page?: number; limit?: number }) {
        return myRequest.get<PageResult<DistributorInfo>>(
            `/adminapi/distribution/distributor/${id}/team`,
            { params }
        )
    },

    /** 调整分销商等级 */
    updateDistributorLevel(id: number, levelId: number) {
        return myRequest.put<void>(`/adminapi/distribution/distributor/${id}/level`, {
            level_id: levelId
        })
    },

    /** 审核通过分销商申请 */
    approveDistributor(id: number) {
        return myRequest.post<void>(`/adminapi/distribution/distributor/${id}/approve`)
    },

    /** 获取佣金记录列表 */
    getCommissionList(params: CommissionQuery) {
        return myRequest.get<CommissionListResult>('/adminapi/distribution/commission', {
            params
        })
    },

    /** 批量结算佣金（按勾选 ID） */
    settleCommissions(data: { ids: number[] }) {
        return myRequest.post<{
            success: number
            skipped: number
            failed: Array<{ id: number; reason: string }>
        }>('/adminapi/distribution/commission/settle', data)
    },

    /** 提交已核实的历史退款/结算证据，并由服务端原子执行佣金冲正 */
    resolveCommissionReview(id: number, data: {
        line_pay_amount: string
        cumulative_refund_amount: string
        credited_amount: string
        settlement_state: 'applied' | 'not_applied'
        withdrawal_state?: 'debited_not_refunded' | 'not_debited' | 'already_refunded'
        reason: string
    }) {
        return myRequest.post<{ applied: boolean; reason: string }>(
            `/adminapi/distribution/commission/${id}/review/resolve`,
            data
        )
    },

    /** 获取提现申请列表 */
    getWithdrawalList(params: WithdrawalRequestQuery) {
        return myRequest.get<PageResult<WithdrawalRequestInfo>>(
            '/adminapi/distribution/withdrawal',
            { params }
        )
    },

    /** 审核通过提现申请 */
    approveWithdrawal(id: number) {
        return myRequest.post<void>(`/adminapi/distribution/withdrawal/${id}/approve`)
    },

    /** 登记已完成的线下打款 */
    payWithdrawal(id: number, data: { payout_reference: string; payout_proof?: string }) {
        return myRequest.post<void>(`/adminapi/distribution/withdrawal/${id}/pay`, data)
    },

    /** 提交已核实的提现冻结/退回事实，并由服务端原子修复余额或佣金债务 */
    resolveWithdrawalLedgerReview(id: number, data: {
        resolution: 'debited_not_refunded' | 'not_debited' | 'already_refunded'
        reason: string
    }) {
        return myRequest.post<{ applied: boolean; reason: string }>(
            `/adminapi/distribution/withdrawal/${id}/review/resolve`,
            data
        )
    },

    /** 拒绝提现申请 */
    rejectWithdrawal(id: number, data: WithdrawalRejectReq) {
        return myRequest.post<void>(`/adminapi/distribution/withdrawal/${id}/reject`, data)
    }
}
