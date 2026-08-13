<script setup lang="ts" name="MemberRewardReview">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

import type { MemberRechargeGrowthReviewItem, MemberRewardReviewItem } from '@/api/member'
import { memberApi } from '@/api/member'

const reviewKind = ref<'order' | 'recharge'>('order')
const loading = ref(false)
const list = ref<MemberRewardReviewItem[]>([])
const pagination = reactive({ current_page: 1, per_page: 20, total: 0, last_page: 1 })
const summary = reactive({ pending: 0, partial: 0, unverified: 0, resolved: 0 })
const filters = reactive({
    keyword: '',
    review_status: 'pending',
    verification_status: '',
})

const rechargeLoading = ref(false)
const rechargeList = ref<MemberRechargeGrowthReviewItem[]>([])
const rechargePagination = reactive({ current_page: 1, per_page: 20, total: 0, last_page: 1 })
const rechargeSummary = reactive({
    pending: 0,
    resolved: 0,
    expected_growth: 0,
    credited_after_review: 0,
})
const rechargeFilters = reactive({ keyword: '', review_status: 'pending', resolution: '' })

const fetchList = async () => {
    loading.value = true
    try {
        const response = await memberApi.getRewardReviews({
            page: pagination.current_page,
            limit: pagination.per_page,
            keyword: filters.keyword || undefined,
            review_status: filters.review_status || undefined,
            verification_status: filters.verification_status || undefined,
        })
        list.value = response.data.list || []
        Object.assign(pagination, response.data.pagination)
        Object.assign(summary, response.data.summary)
    } finally {
        loading.value = false
    }
}

const handleSearch = () => {
    pagination.current_page = 1
    fetchList()
}

const handleReset = () => {
    Object.assign(filters, { keyword: '', review_status: 'pending', verification_status: '' })
    handleSearch()
}

const fetchRechargeList = async () => {
    rechargeLoading.value = true
    try {
        const response = await memberApi.getRechargeGrowthReviews({
            page: rechargePagination.current_page,
            limit: rechargePagination.per_page,
            keyword: rechargeFilters.keyword || undefined,
            review_status: rechargeFilters.review_status || undefined,
            resolution: rechargeFilters.resolution || undefined,
        })
        rechargeList.value = response.data.list || []
        Object.assign(rechargePagination, response.data.pagination)
        Object.assign(rechargeSummary, response.data.summary)
    } finally {
        rechargeLoading.value = false
    }
}

const switchKind = (kind: 'order' | 'recharge') => {
    reviewKind.value = kind
    if (kind === 'recharge') fetchRechargeList()
    else fetchList()
}

const handleRechargeSearch = () => {
    rechargePagination.current_page = 1
    fetchRechargeList()
}

const handleRechargeReset = () => {
    Object.assign(rechargeFilters, { keyword: '', review_status: 'pending', resolution: '' })
    handleRechargeSearch()
}

const switchRechargeStatus = (status: string) => {
    rechargeFilters.review_status = status
    handleRechargeSearch()
}

const switchReviewStatus = (status: string) => {
    filters.review_status = status
    handleSearch()
}

const formatMoney = (value: unknown) => Number(value || 0).toFixed(2)
const formatCount = (value: unknown) => Number(value || 0).toLocaleString('zh-CN')

const verificationMeta: Record<string, { label: string; type: 'warning' | 'danger' | 'success' | 'info' }> = {
    partial: { label: '部分可验证', type: 'warning' },
    unverified: { label: '无法验证', type: 'danger' },
    verified: { label: '已验证', type: 'success' },
}

const evidenceVisible = ref(false)
const selected = ref<MemberRewardReviewItem | null>(null)
const evidenceJson = computed(() => JSON.stringify(selected.value?.evidence || {}, null, 2))

const openEvidence = (row: any) => {
    selected.value = row as MemberRewardReviewItem
    evidenceVisible.value = true
}

const resolveVisible = ref(false)
const resolveLoading = ref(false)
const resolveFormRef = ref<FormInstance>()
const resolveForm = reactive({ id: 0, reason: '', confirmed: false })
const resolveRules: FormRules = {
    reason: [
        { required: true, message: '请填写核对渠道、账本或历史数据后的复核依据', trigger: 'blur' },
        { min: 5, max: 255, message: '复核依据需为 5~255 个字符', trigger: 'blur' },
    ],
}

const rechargeResolveVisible = ref(false)
const rechargeResolveLoading = ref(false)
const rechargeResolveFormRef = ref<FormInstance>()
const selectedRecharge = ref<MemberRechargeGrowthReviewItem | null>(null)
const rechargeResolveForm = reactive<{
    id: number
    resolution: '' | 'confirmed_applied' | 'confirmed_missing'
    reason: string
    confirmed: boolean
}>({ id: 0, resolution: '', reason: '', confirmed: false })
const rechargeResolveRules: FormRules = {
    resolution: [{ required: true, message: '请选择有证据支持的复核结论', trigger: 'change' }],
    reason: [
        { required: true, message: '请填写核对渠道、账本或历史数据后的复核依据', trigger: 'blur' },
        { min: 5, max: 255, message: '复核依据需为 5~255 个字符', trigger: 'blur' },
    ],
}

const openRechargeResolve = (row: any) => {
    const recharge = row as MemberRechargeGrowthReviewItem
    selectedRecharge.value = recharge
    Object.assign(rechargeResolveForm, { id: recharge.id, resolution: '', reason: '', confirmed: false })
    rechargeResolveVisible.value = true
}

const submitRechargeResolve = async () => {
    if (!rechargeResolveFormRef.value) return
    await rechargeResolveFormRef.value.validate()
    if (!rechargeResolveForm.confirmed) {
        ElMessage.warning('请确认复核结论及对应的资产影响')
        return
    }
    if (!rechargeResolveForm.resolution) return
    rechargeResolveLoading.value = true
    try {
        const response = await memberApi.resolveRechargeGrowthReview(rechargeResolveForm.id, {
            resolution: rechargeResolveForm.resolution,
            reason: rechargeResolveForm.reason.trim(),
        })
        ElMessage.success(
            response.data.growth_added
                ? `已补发 ${response.data.growth_value || 0} 成长值并结案`
                : '已确认历史成长值发放事实并结案'
        )
        rechargeResolveVisible.value = false
        await fetchRechargeList()
    } finally {
        rechargeResolveLoading.value = false
    }
}

const openResolve = (row: any) => {
    resolveForm.id = row.id
    resolveForm.reason = ''
    resolveForm.confirmed = false
    selected.value = row as MemberRewardReviewItem
    resolveVisible.value = true
}

const submitResolve = async () => {
    if (!resolveFormRef.value) return
    await resolveFormRef.value.validate()
    if (!resolveForm.confirmed) {
        ElMessage.warning('请确认未验证聚合权益不归属于该订单')
        return
    }
    resolveLoading.value = true
    try {
        await memberApi.resolveRewardReview(resolveForm.id, resolveForm.reason.trim())
        ElMessage.success('权益复核已结案')
        resolveVisible.value = false
        await fetchList()
    } finally {
        resolveLoading.value = false
    }
}

onMounted(fetchList)
</script>

<template>
    <div class="reward-review-container">
        <div class="page-head">
            <div>
                <h2 class="page-title">权益复核</h2>
                <p class="page-desc">
                    {{
                        reviewKind === 'order'
                            ? '复核缺少订单级证据的历史权益，结案仅留痕，不自动增减资产'
                            : '复核缺少成长值证据的历史充值，补发须人工确认并防重复执行'
                    }}
                </p>
            </div>
        </div>

        <!-- KPI -->
        <div v-if="reviewKind === 'order'" class="row-14">
            <button type="button" class="kpi-mini kpi-click" @click="switchReviewStatus('pending')">
                <div class="lb">待复核</div>
                <div class="nm num">{{ formatCount(summary.pending) }}</div>
                <div class="tr"><span>需账本或渠道证据</span></div>
            </button>
            <div class="kpi-mini">
                <div class="lb">部分可验证</div>
                <div class="nm num">{{ formatCount(summary.partial) }}</div>
                <div class="tr"><span>已验证部分可冲正</span></div>
            </div>
            <div class="kpi-mini">
                <div class="lb">无法验证</div>
                <div class="nm num">{{ formatCount(summary.unverified) }}</div>
                <div class="tr"><span class="text-warning">禁止按理论值扣减</span></div>
            </div>
            <button type="button" class="kpi-mini kpi-click" @click="switchReviewStatus('resolved')">
                <div class="lb">已结案</div>
                <div class="nm num">{{ formatCount(summary.resolved) }}</div>
                <div class="tr"><span>已留操作人与依据</span></div>
            </button>
        </div>
        <div v-else class="row-14">
            <button type="button" class="kpi-mini kpi-click" @click="switchRechargeStatus('pending')">
                <div class="lb">待复核充值</div>
                <div class="nm num">{{ formatCount(rechargeSummary.pending) }}</div>
                <div class="tr"><span>禁止系统猜测补发</span></div>
            </button>
            <div class="kpi-mini">
                <div class="lb">待确认成长值</div>
                <div class="nm num">{{ formatCount(rechargeSummary.expected_growth) }}</div>
                <div class="tr"><span>仅为理论值</span></div>
            </div>
            <div class="kpi-mini">
                <div class="lb">复核后补发</div>
                <div class="nm num">{{ formatCount(rechargeSummary.credited_after_review) }}</div>
                <div class="tr"><span>唯一事件键防重</span></div>
            </div>
            <button type="button" class="kpi-mini kpi-click" @click="switchRechargeStatus('resolved')">
                <div class="lb">已结案</div>
                <div class="nm num">{{ formatCount(rechargeSummary.resolved) }}</div>
                <div class="tr"><span>已留结论与依据</span></div>
            </button>
        </div>

        <!-- 过滤栏：类型切换 + 状态 + 搜索 -->
        <div class="filter-bar">
            <div class="seg">
                <button type="button" :class="{ on: reviewKind === 'order' }" @click="switchKind('order')">
                    订单聚合权益
                </button>
                <button type="button" :class="{ on: reviewKind === 'recharge' }" @click="switchKind('recharge')">
                    充值成长值
                    <span v-if="rechargeSummary.pending" class="seg-badge">{{ rechargeSummary.pending }}</span>
                </button>
            </div>

            <template v-if="reviewKind === 'order'">
                <span class="filter-label">状态：</span>
                <el-select
                    v-model="filters.review_status"
                    placeholder="全部"
                    clearable
                    style="width: 120px"
                    @change="handleSearch"
                >
                    <el-option label="待复核" value="pending" />
                    <el-option label="已结案" value="resolved" />
                </el-select>
                <span class="filter-label">证据：</span>
                <el-select
                    v-model="filters.verification_status"
                    placeholder="全部"
                    clearable
                    style="width: 140px"
                    @change="handleSearch"
                >
                    <el-option label="部分可验证" value="partial" />
                    <el-option label="无法验证" value="unverified" />
                    <el-option label="已验证" value="verified" />
                </el-select>
                <span class="filter-sp" />
                <el-input
                    v-model="filters.keyword"
                    clearable
                    placeholder="订单号 / 昵称 / 手机号"
                    style="width: 240px"
                    @keyup.enter="handleSearch"
                />
                <el-button @click="handleReset">重置</el-button>
                <el-button type="primary" @click="handleSearch">查询</el-button>
            </template>
            <template v-else>
                <span class="filter-label">状态：</span>
                <el-select
                    v-model="rechargeFilters.review_status"
                    placeholder="全部"
                    clearable
                    style="width: 120px"
                    @change="handleRechargeSearch"
                >
                    <el-option label="待复核" value="pending" />
                    <el-option label="已结案" value="resolved" />
                </el-select>
                <span class="filter-label">结论：</span>
                <el-select
                    v-model="rechargeFilters.resolution"
                    placeholder="全部"
                    clearable
                    style="width: 160px"
                    @change="handleRechargeSearch"
                >
                    <el-option label="确认历史已发放" value="confirmed_applied" />
                    <el-option label="确认历史未发放" value="confirmed_missing" />
                </el-select>
                <span class="filter-sp" />
                <el-input
                    v-model="rechargeFilters.keyword"
                    clearable
                    placeholder="充值单号 / 昵称 / 手机号"
                    style="width: 240px"
                    @keyup.enter="handleRechargeSearch"
                />
                <el-button @click="handleRechargeReset">重置</el-button>
                <el-button type="primary" @click="handleRechargeSearch">查询</el-button>
            </template>
        </div>

        <!-- 订单表格 -->
        <el-card v-if="reviewKind === 'order'" class="table-card" shadow="never">
            <div class="table-header">
                <div class="table-title">
                    订单权益列表
                    <span class="table-count">共 {{ pagination.total }} 条</span>
                </div>
            </div>

            <el-table v-loading="loading" :data="list" row-key="id">
                <el-table-column label="订单 / 会员" min-width="200">
                    <template #default="{ row }">
                        <div class="identity-cell">
                            <div class="identity-main num">{{ row.order_no || `#${row.order_id}` }}</div>
                            <div class="identity-sub">
                                {{ row.user_nickname || '未命名会员' }} ·
                                {{ row.user_mobile || `ID ${row.user_id}` }}
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="证据状态" width="120">
                    <template #default="{ row }">
                        <el-tag
                            :type="verificationMeta[row.verification_status]?.type || 'info'"
                            size="small"
                        >
                            {{ verificationMeta[row.verification_status]?.label || row.verification_status }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="理论权益" min-width="180">
                    <template #default="{ row }">
                        <div class="asset-stack">
                            <span>积分 <b class="num">{{ formatCount(row.points) }}</b></span>
                            <span>成长 <b class="num">{{ formatCount(row.growth) }}</b></span>
                            <span>消费 <b class="num">¥{{ formatMoney(row.consume_amount) }}</b></span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="可验证权益" min-width="180">
                    <template #default="{ row }">
                        <div class="asset-stack asset-stack--ok">
                            <span>积分 <b class="num">{{ formatCount(row.verified_points) }}</b></span>
                            <span>成长 <b class="num">{{ formatCount(row.verified_growth) }}</b></span>
                            <span>消费 <b class="num">¥{{ formatMoney(row.verified_consume_amount) }}</b></span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column prop="awarded_at" label="完成时间" width="170">
                    <template #default="{ row }">
                        <span class="num">{{ row.awarded_at || '—' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="复核状态" width="100">
                    <template #default="{ row }">
                        <el-tag
                            :type="row.review_status === 'resolved' ? 'success' : 'warning'"
                            size="small"
                            effect="plain"
                        >
                            {{ row.review_status === 'resolved' ? '已结案' : '待复核' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="170" fixed="right">
                    <template #default="{ row }">
                        <el-button link type="primary" @click="openEvidence(row)">查看证据</el-button>
                        <el-button
                            v-if="row.review_status === 'pending'"
                            v-has-perm="['member.reward_review.resolve']"
                            link
                            type="primary"
                            @click="openResolve(row)"
                        >
                            复核结案
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination">
                <el-pagination
                    v-model:current-page="pagination.current_page"
                    v-model:page-size="pagination.per_page"
                    :total="pagination.total"
                    :page-sizes="[10, 20, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @current-change="fetchList"
                    @size-change="
                        () => {
                            pagination.current_page = 1
                            fetchList()
                        }
                    "
                />
            </div>
        </el-card>

        <!-- 充值表格 -->
        <el-card v-else class="table-card" shadow="never">
            <div class="table-header">
                <div class="table-title">
                    充值成长值列表
                    <span class="table-count">共 {{ rechargePagination.total }} 条</span>
                </div>
            </div>

            <el-table v-loading="rechargeLoading" :data="rechargeList" row-key="id">
                <el-table-column label="充值单 / 会员" min-width="210">
                    <template #default="{ row }">
                        <div class="identity-cell">
                            <div class="identity-main num">{{ row.order_no }}</div>
                            <div class="identity-sub">
                                {{ row.user_nickname || '未命名会员' }} ·
                                {{ row.user_mobile || `ID ${row.user_id}` }}
                            </div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="充值构成" min-width="180">
                    <template #default="{ row }">
                        <div class="asset-stack">
                            <span>实充 <b class="num">¥{{ formatMoney(row.amount) }}</b></span>
                            <span>赠送 <b class="num">¥{{ formatMoney(row.gift_amount) }}</b></span>
                            <span>积分 <b class="num">{{ formatCount(row.gift_points) }}</b></span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="理论成长值" width="120">
                    <template #default="{ row }">
                        <span class="growth-value num">+{{ formatCount(row.expected_growth_value) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="paid_at" label="支付时间" width="170">
                    <template #default="{ row }">
                        <span class="num">{{ row.paid_at || '—' }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="复核结论" min-width="200">
                    <template #default="{ row }">
                        <el-tag
                            v-if="row.growth_review_status === 'pending'"
                            type="warning"
                            size="small"
                            effect="plain"
                        >
                            待复核
                        </el-tag>
                        <div v-else class="resolution-cell">
                            <div class="resolution-title">
                                {{
                                    row.growth_review_resolution === 'confirmed_missing'
                                        ? '确认未发放 · 已补发'
                                        : '确认历史已发放'
                                }}
                            </div>
                            <div class="resolution-reason">{{ row.growth_review_reason || '—' }}</div>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="130" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-if="row.growth_review_status === 'pending'"
                            v-has-perm="['member.reward_review.resolve']"
                            link
                            type="primary"
                            @click="openRechargeResolve(row)"
                        >
                            证据复核
                        </el-button>
                        <div v-else class="operator-note">
                            <div>操作人 ID {{ row.growth_review_operator_id || '—' }}</div>
                            <div class="num">{{ row.growth_reviewed_at || '—' }}</div>
                        </div>
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination">
                <el-pagination
                    v-model:current-page="rechargePagination.current_page"
                    v-model:page-size="rechargePagination.per_page"
                    :total="rechargePagination.total"
                    :page-sizes="[10, 20, 50, 100]"
                    layout="total, sizes, prev, pager, next, jumper"
                    @current-change="fetchRechargeList"
                    @size-change="
                        () => {
                            rechargePagination.current_page = 1
                            fetchRechargeList()
                        }
                    "
                />
            </div>
        </el-card>

        <el-drawer v-model="evidenceVisible" title="订单级证据" size="520px">
            <template v-if="selected">
                <div class="evidence-summary">
                    <div>
                        <span>业务订单</span>
                        <strong>{{ selected.order_no || selected.order_id }}</strong>
                    </div>
                    <div>
                        <span>奖励基数</span>
                        <strong>¥{{ formatMoney(selected.reward_amount) }}</strong>
                    </div>
                    <div>
                        <span>证据状态</span>
                        <strong>{{ verificationMeta[selected.verification_status]?.label || '—' }}</strong>
                    </div>
                </div>
                <div v-if="selected.review_status === 'resolved'" class="resolve-note">
                    <strong>结案依据</strong>
                    <p>{{ selected.review_reason }}</p>
                    <small>管理员 ID {{ selected.review_operator_id }} · {{ selected.reviewed_at }}</small>
                </div>
                <h4 class="evidence-title">不可变证据快照</h4>
                <pre class="evidence-code">{{ evidenceJson }}</pre>
            </template>
        </el-drawer>

        <el-dialog v-model="resolveVisible" title="确认权益结案" width="560px" destroy-on-close>
            <el-alert
                type="warning"
                :closable="false"
                show-icon
                title="本操作不会修改会员资产"
                description="只确认除当前「可验证权益」外，没有更多历史成长值、累计消费或订单数可可靠归属于该订单。已验证部分仍会按退款事实自动冲正。"
                class="mb-alert"
            />
            <el-form ref="resolveFormRef" :model="resolveForm" :rules="resolveRules" label-position="top">
                <el-form-item label="复核依据" prop="reason">
                    <el-input
                        v-model="resolveForm.reason"
                        type="textarea"
                        :rows="4"
                        maxlength="255"
                        show-word-limit
                        placeholder="例如：已核对积分流水与等级变更记录，除已识别积分外无其他订单级资产证据"
                    />
                </el-form-item>
                <el-checkbox v-model="resolveForm.confirmed">
                    我已核对证据，并确认未验证聚合权益不归属于该订单
                </el-checkbox>
            </el-form>
            <template #footer>
                <el-button @click="resolveVisible = false">取消</el-button>
                <el-button type="primary" :loading="resolveLoading" @click="submitResolve">留痕并结案</el-button>
            </template>
        </el-dialog>

        <el-dialog v-model="rechargeResolveVisible" title="充值成长值证据复核" width="600px" destroy-on-close>
            <el-alert
                type="warning"
                :closable="false"
                show-icon
                title="这是资产操作，请只选择有证据支持的结论"
                :description="`「历史已发放」只留痕；「历史未发放」会立即补发 ${formatCount(selectedRecharge?.expected_growth_value)} 成长值，并用唯一事件键防止重复。`"
                class="mb-alert"
            />
            <el-form
                ref="rechargeResolveFormRef"
                :model="rechargeResolveForm"
                :rules="rechargeResolveRules"
                label-position="top"
            >
                <el-form-item label="复核结论" prop="resolution">
                    <el-radio-group v-model="rechargeResolveForm.resolution" class="resolution-options">
                        <el-radio value="confirmed_applied" border>历史已发放 · 不改资产</el-radio>
                        <el-radio value="confirmed_missing" border>历史未发放 · 精确补发</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="复核依据" prop="reason">
                    <el-input
                        v-model="rechargeResolveForm.reason"
                        type="textarea"
                        :rows="4"
                        maxlength="255"
                        show-word-limit
                        placeholder="例如：已核对旧系统等级变更记录与备份流水，确认该充值未增加成长值"
                    />
                </el-form-item>
                <el-checkbox v-model="rechargeResolveForm.confirmed">
                    我已核对订单级或历史系统证据，并确认所选结论对应的资产影响
                </el-checkbox>
            </el-form>
            <template #footer>
                <el-button @click="rechargeResolveVisible = false">取消</el-button>
                <el-button
                    type="primary"
                    :loading="rechargeResolveLoading"
                    @click="submitRechargeResolve"
                >
                    执行并留痕
                </el-button>
            </template>
        </el-dialog>
    </div>
</template>

<style scoped lang="scss">
.reward-review-container {
    .filter-label {
        color: var(--el-text-color-regular);
        font-size: 14px;
    }

    .seg {
        display: inline-flex;
        padding: 3px;
        border-radius: 8px;
        background: var(--ink-50, #f4f6f9);

        button {
            display: inline-flex;
            align-items: center;
            border: none;
            background: transparent;
            padding: 6px 14px;
            font-size: 13px;
            color: var(--ink-600, #5b6577);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;

            &.on {
                background: #fff;
                color: var(--brand-600, #2563eb);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
                font-weight: 600;
            }

            &:hover:not(.on) {
                color: var(--brand-500, #3b82f6);
            }
        }
    }

    .kpi-click {
        width: 100%;
        text-align: left;
        cursor: pointer;
        border: 0;
        font: inherit;
        color: inherit;
        transition: box-shadow 0.15s ease, transform 0.15s ease;

        &:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.06));
        }
    }

    .seg-badge {
        margin-left: 4px;
        padding: 0 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        color: var(--el-color-warning-dark-2);
        background: var(--el-color-warning-light-8);
    }

    .identity-cell {
        display: grid;
        gap: 2px;
    }
    .identity-main {
        font-weight: 600;
        font-size: 13px;
        color: var(--el-text-color-primary);
    }
    .identity-sub {
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    .asset-stack {
        display: grid;
        grid-template-columns: repeat(2, max-content);
        gap: 4px 14px;
        font-size: 12px;
        color: var(--el-text-color-secondary);

        b {
            margin-left: 4px;
            color: var(--el-text-color-primary);
            font-weight: 600;
        }

        &--ok b {
            color: var(--el-color-success);
        }
    }

    .growth-value {
        color: var(--el-color-success);
        font-weight: 700;
        font-size: 15px;
    }

    .resolution-cell {
        display: grid;
        gap: 2px;
    }
    .resolution-title {
        font-size: 12px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }
    .resolution-reason {
        overflow: hidden;
        max-width: 240px;
        font-size: 12px;
        color: var(--el-text-color-secondary);
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .operator-note {
        font-size: 12px;
        color: var(--el-text-color-placeholder);
        line-height: 1.5;
    }

    .evidence-summary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;

        div {
            display: grid;
            gap: 4px;
            padding: 12px;
            border: 1px solid var(--el-border-color-lighter);
            border-radius: 4px;
            background: var(--el-fill-color-blank);
        }
        span {
            font-size: 11px;
            color: var(--el-text-color-secondary);
        }
        strong {
            overflow: hidden;
            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }
    .evidence-title {
        margin: 20px 0 8px;
        font-size: 13px;
        font-weight: 600;
    }
    .evidence-code {
        overflow: auto;
        min-height: 240px;
        margin: 0;
        padding: 14px;
        border-radius: 4px;
        background: #1e293b;
        color: #e2e8f0;
        font: 12px/1.65 ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    .resolve-note {
        margin-top: 14px;
        padding: 12px 14px;
        border-left: 3px solid var(--el-color-success);
        border-radius: 4px;
        background: var(--el-color-success-light-9);

        p {
            margin: 6px 0;
            font-size: 13px;
            color: var(--el-text-color-regular);
            line-height: 1.6;
        }
        small {
            color: var(--el-text-color-secondary);
        }
    }
    .mb-alert {
        margin-bottom: 16px;
    }
    .resolution-options {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        width: 100%;

        :deep(.el-radio) {
            margin-right: 0;
        }
    }

    .text-warning {
        color: var(--el-color-warning);
    }
}
</style>
