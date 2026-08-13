import { myRequest } from '@/utils/request'

/**
 * 用户统计 API
 */
export const memberStatisticsApi = {
    /**
     * 获取用户概览统计（总用户 / 今日新增 / 昨日新增 / 本月新增）
     */
    getOverview() {
        return myRequest.get('/adminapi/member/statistics/overview')
    },

    /**
     * 获取注册趋势（近 N 天每天新增用户数）
     * @param days 天数，默认 30
     */
    getRegisterTrend(days: number = 30) {
        return myRequest.get('/adminapi/member/statistics/register-trend', { params: { days } })
    },

    /**
     * 获取活跃用户分析（日活 / 周活 / 月活）
     */
    getActiveAnalysis() {
        return myRequest.get('/adminapi/member/statistics/active')
    },

    /**
     * 获取会员等级分布
     */
    getLevelDistribution() {
        return myRequest.get('/adminapi/member/statistics/level-distribution')
    },

    /**
     * 获取消费区间分布
     */
    getConsumeDistribution() {
        return myRequest.get('/adminapi/member/statistics/consume-distribution')
    },

    /**
     * 获取注册来源分布（基于 user_auths.platform）
     */
    getSourceDistribution() {
        return myRequest.get('/adminapi/member/statistics/source-distribution')
    },

    /**
     * 获取留存矩阵（默认 8 周 cohort × D1/D3/D7/D14/D30）
     */
    getRetentionMatrix(cohorts: number = 8) {
        return myRequest.get('/adminapi/member/statistics/retention-matrix', { params: { cohorts } })
    },
}
