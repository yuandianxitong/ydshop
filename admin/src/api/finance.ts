import type {
    FinanceIncomeCompositionItem,
    FinanceOverviewInfo,
    FinanceTransactionInfo,
    FinanceTransactionQuery,
    FinanceTrendItem,
    FinanceWithdrawalStats,
    PageResult,
    PointsRulesOverview
} from '@/types/api'
import { myRequest } from '@/utils/request'

/**
 * Finance 财务管理 API
 */
export const financeApi = {
    /** 获取财务概览数据（统计卡片） */
    getOverview() {
        return myRequest.get<FinanceOverviewInfo>('/adminapi/finance/overview')
    },

    /** 获取收支趋势（折线图数据） */
    getTrend(days: number = 30) {
        return myRequest.get<FinanceTrendItem[]>('/adminapi/finance/trend', { params: { days } })
    },

    /** 获取收入构成（饼图数据） */
    getIncomeComposition() {
        return myRequest.get<FinanceIncomeCompositionItem[]>('/adminapi/finance/income-composition')
    },

    /** 获取提现统计数据 */
    getWithdrawalStats() {
        return myRequest.get<FinanceWithdrawalStats>('/adminapi/finance/withdrawal-stats')
    },

    /** 获取交易流水列表 */
    getTransactionList(params: FinanceTransactionQuery) {
        return myRequest.get<PageResult<FinanceTransactionInfo>>('/adminapi/finance/transactions', {
            params
        })
    },

    /**
     * 获取积分规则总览
     */
    getPointsRules() {
        return myRequest.get<PointsRulesOverview>('/adminapi/finance/points-rules')
    }
}
