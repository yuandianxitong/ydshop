<template>
    <div class="page-container">
        <!-- 页头 -->
        <div class="page-head">
            <div>
                <h2 class="page-title">新人礼包</h2>
                <p class="page-desc">拉新转化的统一面板，覆盖券包 / 实物 / 立减 / 节日多形态</p>
            </div>
            <div class="page-actions">
                <el-button @click="handleViewAllLogs">领取记录</el-button>
                <el-button @click="tab = 'strategy'">分发策略</el-button>
                <el-button type="primary" @click="handleCreate">+ 新建礼包</el-button>
            </div>
        </div>

        <!-- KPI 迷你卡 -->
        <div class="row-14">
            <div v-for="(k, i) in kpiList" :key="i" class="kpi-mini" :class="k.tone">
                <div class="lb">{{ k.label }}</div>
                <div class="nm num">{{ k.value }}</div>
                <div class="tr"><span>{{ k.note }}</span></div>
            </div>
        </div>

        <!-- 转化路径 -->
        <div class="card flow-card">
            <div class="card-head">
                <div class="card-title">领取转化路径</div>
                <div class="card-meta">注册 → 领取 → 到账 → 首单</div>
            </div>
            <div class="card-body">
                <div class="flow-grid">
                    <div v-for="(s, i) in flowSteps" :key="i" class="flow-step">
                        <div class="flow-step-head">
                            <div class="flow-step-num num">{{ s.step }}</div>
                            <div class="flow-step-name">{{ s.name }}</div>
                        </div>
                        <div class="flow-step-desc">{{ s.desc }}</div>
                        <div v-if="i < flowSteps.length - 1" class="flow-arrow">›</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 筛选栏 + Tab -->
        <div class="filter-bar tabsel-bar">
            <div class="seg">
                <button :class="{ on: tab === 'pkg' }" @click="tab = 'pkg'">
                    礼包列表 ({{ pkgCount }})
                </button>
                <button :class="{ on: tab === 'rule' }" @click="tab = 'rule'">领取规则</button>
                <button :class="{ on: tab === 'logs' }" @click="tab = 'logs'">领取记录</button>
                <button :class="{ on: tab === 'strategy' }" @click="tab = 'strategy'">分发策略</button>
            </div>
            <div class="filter-bar-spacer" />
            <el-input
                v-model="searchForm.keyword"
                placeholder="搜索礼包"
                clearable
                style="width: 240px"
                @keyup.enter="handleSearch"
                @clear="handleSearch"
            />
        </div>

        <!-- 礼包列表 -->
        <div v-if="tab === 'pkg'" class="row-12" v-loading="loading">
            <div v-if="!giftList.length" class="empty-tip">暂无礼包，点击右上角「+ 新建礼包」开始</div>
            <div v-for="gift in giftList" :key="gift.id" class="card pkg-card">
                <div class="card-body">
                    <div class="pkg-head">
                        <div class="pkg-head-left">
                            <div class="pkg-icon">
                                <span class="pkg-icon-text">礼</span>
                            </div>
                            <div>
                                <div class="pkg-name">{{ gift.name }}</div>
                                <div class="pkg-meta num">
                                    NB-{{ String(gift.id).padStart(3, '0') }} ·
                                    {{ gift.conditions.map(conditionLabel).join(' / ') || '不限受众' }} ·
                                    {{ validityLabel(gift) }}
                                </div>
                            </div>
                        </div>
                        <el-tag
                            :class="gift.status === 1 ? 'tag-tone-green' : 'tag-tone-gray'"
                            size="small"
                            disable-transitions
                        >
                            {{ gift.status === 1 ? '进行中' : '未启用' }}
                        </el-tag>
                    </div>

                    <div v-if="gift.description" class="pkg-desc">{{ gift.description }}</div>

                    <div class="pkg-content">
                        <span class="pkg-content-label">礼包内容：</span>
                        <span>{{ giftSummary(gift) }}</span>
                    </div>

                    <div class="pkg-stats">
                        <div>
                            <div class="pkg-stat-lb">已领取</div>
                            <div class="pkg-stat-nm num">{{ formatCount(gift.claimed_count) }}</div>
                        </div>
                    </div>

                    <div class="tbl-acts pkg-acts">
                        <el-button text type="primary" size="small" @click="handleEdit(gift)">编辑</el-button>
                        <el-button text size="small" @click="handleViewLogs(gift)">领取明细</el-button>
                        <el-button text type="danger" size="small" @click="handleDelete(gift)">删除</el-button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 领取规则表单 -->
        <div v-if="tab === 'rule'" class="card rule-card">
            <div class="card-body">
                <div class="rule-grid">
                    <div class="rule-label">领取上限</div>
                    <div class="rule-control">每个注册账号 1 次，由用户发放标记保证幂等</div>

                    <div class="rule-label">触发场景</div>
                    <div class="rule-control">用户完成注册并创建账号后自动触发</div>

                    <div class="rule-label">到账方式</div>
                    <div class="rule-control">积分、余额和优惠券在注册事件中立即发放</div>

                    <div class="rule-label">失败处理</div>
                    <div class="rule-control">单项奖励失败只记录日志，不阻断其他奖励发放</div>
                </div>
            </div>
        </div>

        <!-- 领取记录列表 -->
        <div v-if="tab === 'logs'" class="card logs-card">
            <div class="filter-bar logs-filter">
                <el-input
                    v-model="logSearchForm.user_id"
                    placeholder="用户ID"
                    clearable
                    style="width: 130px"
                    @keyup.enter="handleLogSearch"
                />
                <el-select
                    v-model="logSearchForm.gift_id"
                    placeholder="礼包"
                    clearable
                    style="width: 200px"
                    @change="handleLogSearch"
                >
                    <el-option v-for="g in giftList" :key="g.id" :label="g.name" :value="g.id" />
                </el-select>
                <el-date-picker
                    v-model="logDateRange"
                    type="daterange"
                    range-separator="至"
                    start-placeholder="开始日期"
                    end-placeholder="结束日期"
                    value-format="YYYY-MM-DD"
                    style="width: 240px"
                />
                <el-button @click="resetLogSearch">重置</el-button>
                <el-button type="primary" @click="handleLogSearch">查询</el-button>
            </div>

            <ProTable
                title="领取记录"
                storage-key="new-user-gift-logs"
                :columns="logColumns"
                :data="logList"
                :loading="logLoading"
                :pagination="logPagination"
                :show-column-config="false"
                @page-change="handleLogPageChange"
                @size-change="handleLogSizeChange"
            >
                <template #user="{ row }">
                    <div class="user-cell">
                        <el-avatar v-if="row.user_avatar" :src="row.user_avatar" :size="28" />
                        <span>{{ row.user_nickname || `用户${row.user_id}` }}</span>
                    </div>
                </template>
                <template #coupon_ids="{ row }">
                    {{ (row.coupon_ids ?? []).length }} 张
                </template>
                <template #created_at="{ row }">
                    <span class="num text-ink-500">{{ row.created_at }}</span>
                </template>
            </ProTable>
        </div>

        <!-- 分发策略 -->
        <template v-if="tab === 'strategy'">
            <div class="card strategy-card">
                <div class="card-head">
                    <div class="card-title">发放逻辑</div>
                </div>
                <div class="card-body">
                    <ol class="strategy-steps">
                        <li>用户完成手机号注册时触发新人礼包发放</li>
                        <li>从启用且在有效期内的所有礼包中筛选 — 礼包的"受众标签"必须 <b>全部匹配</b> 当前用户</li>
                        <li>匹配的礼包逐张发放（积分/余额/优惠券），单张失败仅记 warn 日志，不影响其他礼包</li>
                        <li>发放后标记 <code>users.new_user_gift_claimed_at</code> — 同一用户不会重复触发，即使 register 事件重放</li>
                        <li>每个注册账号只发放 1 次；事件重放不会重复发奖</li>
                    </ol>
                </div>
            </div>

            <div class="card strategy-card">
                <div class="card-head">
                    <div class="card-title">受众标签判断规则</div>
                    <div class="card-meta">register 事件触发时的判断口径</div>
                </div>
                <div class="card-body">
                    <table class="strategy-table">
                        <thead>
                            <tr><th>标签</th><th>判断依据</th><th>注册时刻表现</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>新注册用户</td>
                                <td>—</td>
                                <td><el-tag type="success" size="small">恒为真</el-tag></td>
                            </tr>
                            <tr>
                                <td>7 天内未下单</td>
                                <td>register 时无订单</td>
                                <td>
                                    <el-tag type="success" size="small">恒为真</el-tag>
                                    <span class="hint">未来事件触发后才有差别</span>
                                </td>
                            </tr>
                            <tr>
                                <td>邀请好友 ≥ 1</td>
                                <td><code>users.inviter_id &gt; 0</code></td>
                                <td><el-tag type="info" size="small">条件判断</el-tag></td>
                            </tr>
                            <tr>
                                <td>完善资料</td>
                                <td>nickname 和 avatar 都非空</td>
                                <td><el-tag type="warning" size="small">大部分新用户为假</el-tag></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- 编辑 / 新建 Dialog -->
        <el-dialog
            v-model="drawerVisible"
            :title="drawerTitle"
            width="640px"
            destroy-on-close
            :close-on-click-modal="false"
        >
            <div class="dialog-content">
                <el-form
                    ref="formRef"
                    :model="giftForm"
                    :rules="giftFormRules"
                    label-width="120px"
                    class="settings-form"
                >
                    <el-divider content-position="left">基本信息</el-divider>

                    <el-form-item label="礼包名称" prop="name">
                        <el-input v-model="giftForm.name" placeholder="请输入礼包名称" maxlength="60" />
                    </el-form-item>

                    <el-form-item label="运营备注">
                        <el-input
                            v-model="giftForm.description"
                            type="textarea"
                            :rows="2"
                            maxlength="255"
                            placeholder="可选，用于运营记录"
                        />
                    </el-form-item>

                    <el-form-item label="是否启用">
                        <el-switch
                            :model-value="giftForm.status === 1"
                            @update:model-value="(v: string | number | boolean) => giftForm.status = v ? 1 : 0"
                        />
                    </el-form-item>

                    <el-form-item label="排序">
                        <el-input-number
                            v-model="giftForm.sort_order"
                            :min="0"
                            :max="999"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="field-unit">小在前</span>
                    </el-form-item>

                    <el-divider content-position="left">受众与有效期</el-divider>

                    <el-form-item label="受众标签" prop="conditions">
                        <el-checkbox-group v-model="giftForm.conditions">
                            <el-checkbox
                                v-for="opt in CONDITION_OPTIONS"
                                :key="opt.value"
                                :value="opt.value"
                                class="rule-chip-check"
                            >{{ opt.label }}</el-checkbox>
                        </el-checkbox-group>
                    </el-form-item>

                    <el-form-item label="有效期">
                        <el-date-picker
                            v-model="giftForm.valid_range"
                            type="datetimerange"
                            range-separator="至"
                            start-placeholder="开始时间"
                            end-placeholder="结束时间"
                            value-format="YYYY-MM-DD HH:mm:ss"
                            style="width: 100%"
                        />
                        <div class="field-desc">留空表示长期有效。</div>
                    </el-form-item>

                    <el-divider content-position="left">奖励内容</el-divider>

                    <el-form-item label="赠送积分">
                        <el-input-number
                            v-model="giftForm.points"
                            :min="0"
                            :max="999999"
                            :step="10"
                            :precision="0"
                            controls-position="right"
                        />
                        <span class="field-unit">积分</span>
                    </el-form-item>

                    <el-form-item label="赠送余额">
                        <el-input-number
                            v-model="giftForm.balance"
                            :min="0"
                            :max="99999"
                            :step="1"
                            :precision="2"
                            controls-position="right"
                        />
                        <span class="field-unit">元</span>
                    </el-form-item>

                    <el-form-item label="赠送优惠券">
                        <el-select
                            v-model="giftForm.coupon_ids"
                            multiple
                            filterable
                            placeholder="请选择优惠券"
                            style="width: 100%"
                            :loading="couponLoading"
                        >
                            <el-option
                                v-for="coupon in couponOptions"
                                :key="coupon.id"
                                :value="coupon.id"
                                :label="couponLabel(coupon)"
                            >
                                <span>{{ coupon.name }}</span>
                                <span class="coupon-tag">{{ couponTypeText(coupon.type) }}</span>
                                <span class="coupon-value">{{ couponValueText(coupon) }}</span>
                            </el-option>
                        </el-select>
                        <div class="field-desc">仅显示已启用的优惠券。</div>
                    </el-form-item>
                </el-form>
            </div>
            <template #footer>
                <div class="dialog-footer">
                    <el-button @click="drawerVisible = false">取消</el-button>
                    <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import type { FormInstance } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, reactive, ref, watch } from 'vue'

import { couponApi, type NewUserGift, newUserGiftApi, type NewUserGiftLog, type NewUserGiftLogQuery,type NewUserGiftQuery, type NewUserGiftStats } from '@/api/marketing'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'

import { CONDITION_OPTIONS } from './options'

interface CouponOption {
    id: number
    name: string
    type: 'fixed' | 'percent' | 'no_threshold'
    value: number
    min_amount: number
    status: number
}

const tab = ref<'pkg' | 'rule' | 'logs' | 'strategy'>('pkg')
const couponOptions = ref<CouponOption[]>([])
const couponLoading = ref(false)

const drawerVisible = ref(false)
const drawerMode = ref<'create' | 'edit'>('create')
const editingId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const saving = ref(false)

// 礼包列表
const {
    list: giftList,
    loading,
    pagination,
    searchForm,
    handleSearch,
    handlePageChange,
    handleSizeChange,
} = useListPage<NewUserGift, NewUserGiftQuery>({
    fetchFn: (params) => newUserGiftApi.getList(params),
    defaultSearchForm: { keyword: '' },
    pageSize: 20,
    immediate: false,
})

const pkgCount = computed(() => pagination.total)

// KPI 数据
const stats = reactive<NewUserGiftStats>({ new_users: 0, recipients: 0, conversion_rate: 0, gmv: 0 })

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const fetchStats = async () => {
    try {
        const res = await newUserGiftApi.getStats()
        if (res.data) Object.assign(stats, res.data)
    } catch (e) {
        console.warn('获取 KPI 失败', e)
    }
}

const kpiList = computed(() => [
    { label: '本月新人',   value: formatCount(stats.new_users),                tone: 'tone-blue',   note: '本月注册用户' },
    { label: '礼包领取',   value: formatCount(stats.recipients),               tone: 'tone-purple', note: '本月去重用户' },
    { label: '首单转化率', value: stats.conversion_rate.toFixed(1) + '%',      tone: 'tone-teal',   note: '本月领过礼包' },
    { label: '新客 GMV',   value: '¥' + formatCount(stats.gmv),                tone: 'tone-amber',  note: '本月首单金额' },
])

const flowSteps = [
    { step: '1', name: '注册',     desc: '用户完成手机号验证' },
    { step: '2', name: '条件匹配', desc: '校验礼包有效期与受众标签' },
    { step: '3', name: '自动到账', desc: '发放积分、余额和优惠券' },
    { step: '4', name: '首单转化', desc: '引导首单使用并埋点' },
]

const couponTypeText = (type: string): string => {
    const map: Record<string, string> = { fixed: '满减', percent: '折扣', no_threshold: '无门槛' }
    return map[type] ?? type
}

const couponValueText = (coupon: CouponOption): string => {
    if (coupon.type === 'fixed')   return coupon.min_amount > 0 ? `满 ${coupon.min_amount} 减 ${coupon.value} 元` : `减 ${coupon.value} 元`
    if (coupon.type === 'percent') return `${coupon.value} 折`
    return `减 ${coupon.value} 元`
}

const couponLabel = (coupon: CouponOption): string =>
    `${coupon.name} (${couponTypeText(coupon.type)} · ${couponValueText(coupon)})`

const conditionLabel = (key: string) =>
    CONDITION_OPTIONS.find(o => o.value === key)?.label ?? key

const giftSummary = (gift: NewUserGift): string => {
    const parts: string[] = []
    if (gift.points > 0)  parts.push(`积分 ${gift.points}`)
    if (gift.balance > 0) parts.push(`余额 ¥${gift.balance}`)
    if ((gift.coupon_ids ?? []).length) parts.push(`优惠券 ${gift.coupon_ids.length} 张`)
    return parts.length ? parts.join(' / ') : '尚未配置任何礼品'
}

const validityLabel = (gift: NewUserGift): string => {
    if (!gift.valid_start && !gift.valid_end) return '长期有效'
    return `${gift.valid_start ?? '不限'} ~ ${gift.valid_end ?? '不限'}`
}

const handleViewLogs = (gift: NewUserGift) => {
    tab.value = 'logs'
    logSearchForm.user_id = ''
    logSearchForm.gift_id = gift.id
    logDateRange.value = null
    handleLogSearch()
}

const handleViewAllLogs = () => {
    tab.value = 'logs'
    resetLogSearch()
    logDateRange.value = null
}

const loadCoupons = async () => {
    couponLoading.value = true
    try {
        const res = await couponApi.getCouponList({ status: 1, page_size: 200 })
        if (res.code === 200 && res.data) {
            couponOptions.value = (res.data.list ?? []) as any
        }
    } catch {
        // ignore
    } finally {
        couponLoading.value = false
    }
}

const handleCreate = () => {
    drawerMode.value = 'create'
    editingId.value = null
    giftForm.value = defaultGiftForm()
    drawerVisible.value = true
}

const handleEdit = (gift: NewUserGift) => {
    drawerMode.value = 'edit'
    editingId.value = gift.id
    giftForm.value = {
        name:        gift.name,
        description: gift.description ?? '',
        status:      gift.status,
        sort_order:  gift.sort_order,
        conditions:  [...(gift.conditions ?? [])],
        points:      gift.points,
        balance:     gift.balance,
        coupon_ids:  [...(gift.coupon_ids ?? [])],
        valid_range: gift.valid_start && gift.valid_end ? [gift.valid_start, gift.valid_end] : null,
    }
    drawerVisible.value = true
}

const handleDelete = async (gift: NewUserGift) => {
    try {
        await ElMessageBox.confirm(
            `确定删除礼包「${gift.name}」吗？`,
            '删除确认',
            { confirmButtonText: '确定删除', cancelButtonText: '取消', type: 'warning' }
        )
        await newUserGiftApi.delete(gift.id)
        ElMessage.success('删除成功')
        handleSearch()
    } catch (error) {
        if (error !== 'cancel') {
            console.error('删除失败:', error)
        }
    }
}

const drawerTitle = computed(() => drawerMode.value === 'create' ? '新建新人礼包' : '编辑新人礼包')

interface GiftFormState {
    name: string
    description: string
    status: number
    sort_order: number
    conditions: string[]
    points: number
    balance: number
    coupon_ids: number[]
    valid_range: [string, string] | null
}

const defaultGiftForm = (): GiftFormState => ({
    name: '',
    description: '',
    status: 1,
    sort_order: 0,
    conditions: ['new_register'],
    points: 0,
    balance: 0,
    coupon_ids: [],
    valid_range: null,
})

const giftForm = ref<GiftFormState>(defaultGiftForm())

const giftFormRules = {
    name: [{ required: true, message: '请输入礼包名称', trigger: 'blur' }],
    conditions: [
        {
            required: true,
            type: 'array' as const,
            min: 1,
            message: '至少选择 1 个受众标签',
            trigger: 'change',
        },
    ],
}

const handleSave = async () => {
    if (!formRef.value) return
    try {
        await formRef.value.validate()
    } catch {
        return
    }
    saving.value = true
    try {
        const payload: Partial<NewUserGift> = {
            name:        giftForm.value.name,
            description: giftForm.value.description,
            status:      giftForm.value.status,
            sort_order:  giftForm.value.sort_order,
            conditions:  giftForm.value.conditions,
            points:      giftForm.value.points,
            balance:     giftForm.value.balance,
            coupon_ids:  giftForm.value.coupon_ids,
            valid_start: giftForm.value.valid_range?.[0] ?? null,
            valid_end:   giftForm.value.valid_range?.[1] ?? null,
        }
        if (drawerMode.value === 'create') {
            await newUserGiftApi.create(payload)
            ElMessage.success('创建成功')
        } else if (editingId.value !== null) {
            await newUserGiftApi.update(editingId.value, payload)
            ElMessage.success('更新成功')
        }
        drawerVisible.value = false
        handleSearch()
        fetchStats()
    } catch (e) {
        // myRequest 拦截器已处理错误提示
        console.error('保存礼包失败:', e)
    } finally {
        saving.value = false
    }
}

// Logs tab state
const {
    list: logList,
    loading: logLoading,
    pagination: logPagination,
    searchForm: logSearchForm,
    handleSearch: handleLogSearch,
    resetSearch: resetLogSearch,
    handlePageChange: handleLogPageChange,
    handleSizeChange: handleLogSizeChange,
} = useListPage<NewUserGiftLog, NewUserGiftLogQuery>({
    fetchFn: (params) => newUserGiftApi.getLogs(params),
    defaultSearchForm: { user_id: '', gift_id: '', date_start: '', date_end: '' },
    pageSize: 20,
})

const logDateRange = ref<[string, string] | null>(null)
watch(logDateRange, (v) => {
    if (Array.isArray(v) && v.length === 2) {
        logSearchForm.date_start = v[0]
        logSearchForm.date_end = v[1]
    } else {
        logSearchForm.date_start = ''
        logSearchForm.date_end = ''
    }
})

const logColumns: ProColumn[] = [
    { key: 'user',            label: '会员',       minWidth: 180 },
    { key: 'gift_name',       label: '礼包名称',   minWidth: 140 },
    { key: 'points_awarded',  label: '赠送积分',   width: 100, align: 'right' },
    { key: 'balance_awarded', label: '赠送余额',   width: 100, align: 'right' },
    { key: 'coupon_ids',      label: '优惠券',     width: 90,  align: 'right' },
    { key: 'created_at',      label: '时间',       width: 160 },
]

// Suppress unused-variable warnings for vars used only in future tasks
void [handlePageChange, handleSizeChange]

onMounted(() => {
    loadCoupons()
    handleSearch()
    fetchStats()
})
</script>

<style scoped lang="scss">
.page-container {
    padding: 16px;
}

/* 转化路径 */
.flow-card {
    margin-bottom: 14px;
}

.flow-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    position: relative;

    @media (max-width: 1024px) {
        grid-template-columns: repeat(2, 1fr);
    }
}

.flow-step {
    position: relative;
    padding: 14px 16px;
    background: var(--ink-50);
    border-radius: 8px;
    border: 1px solid var(--ink-100);
}

.flow-step-head {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flow-step-num {
    width: 28px;
    height: 28px;
    border-radius: 14px;
    background: var(--brand-500);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
}

.flow-step-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-900);
}

.flow-step-desc {
    font-size: 12px;
    color: var(--ink-500);
    margin-top: 8px;
    padding-left: 38px;
}

.flow-arrow {
    position: absolute;
    right: -12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ink-300);
    font-size: 18px;
    z-index: 1;

    @media (max-width: 1024px) {
        display: none;
    }
}

.card-title {
    font-size: 16px;
    font-weight: 600;
}

/* tab + 搜索 行 */
.tabsel-bar {
    justify-content: space-between;
}

.filter-bar-spacer {
    flex: 1;
}

/* 礼包卡片 */
.pkg-card {
    .card-body {
        padding: 16px 18px 14px;
    }
}

.pkg-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.pkg-head-left {
    display: flex;
    gap: 12px;
    align-items: center;
}

.pkg-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    background: linear-gradient(135deg, #f43f5e, #ec4899);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.pkg-icon-text {
    font-size: 20px;
    font-weight: 700;
}

.pkg-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--ink-900);
}

.pkg-meta {
    font-size: 11px;
    color: var(--ink-400);
    margin-top: 3px;
}

.pkg-desc {
    margin-top: 8px;
    color: var(--ink-500);
    font-size: 12.5px;
}

.pkg-content {
    margin-top: 12px;
    padding: 10px 12px;
    background: var(--ink-50);
    border-radius: 6px;
    font-size: 12.5px;
    color: var(--ink-700);
    line-height: 1.6;
}

.pkg-content-label {
    color: var(--ink-400);
}

.pkg-stats {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    margin-top: 12px;
}

.pkg-stat-lb {
    font-size: 11px;
    color: var(--ink-400);
}

.pkg-stat-nm {
    font-size: 18px;
    font-weight: 600;
    color: var(--ink-900);
    margin-top: 2px;

    &.pkg-stat-rate {
        color: var(--success);
    }
}

.pkg-progbar-wrap {
    margin-top: 10px;
}

.pkg-acts {
    margin-top: 12px;
    justify-content: flex-end;
}

.empty-tip {
    grid-column: 1 / -1;
    text-align: center;
    padding: 32px 16px;
    color: var(--ink-400);
    font-size: 13px;
}

/* 领取规则表单 */
.rule-card .card-body {
    padding: 22px 24px 18px;
}

.rule-grid {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 18px 24px;
    font-size: 13px;
    align-items: start;
}

.rule-label {
    color: var(--ink-500);
    padding-top: 8px;
    font-size: 13px;
}

.rule-control {
    color: var(--ink-800);
}

.rule-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.rule-unit {
    color: var(--ink-700);
    font-size: 13px;
}

.rule-hint {
    color: var(--ink-500);
    font-size: 12px;
    margin-left: 4px;
}

.rule-chip-check {
    margin-right: 12px;
}

.scene-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.scene-chip {
    padding: 8px 12px;
    border: 1px solid var(--ink-200);
    background: #fff;
    color: var(--ink-500);
    border-radius: 6px;
    font-size: 12.5px;
    cursor: pointer;
    transition: all 0.15s;

    &:hover {
        border-color: var(--brand-300);
        color: var(--brand-500);
    }

    &.on {
        border-color: var(--brand-500);
        background: var(--brand-50, #eff6ff);
        color: var(--brand-500);
        font-weight: 500;
    }
}

.risk-stack {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
}

.rule-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid var(--ink-100);
}

/* Dialog */
.dialog-content {
    padding: 4px 4px 0;
    max-height: 65vh;
    overflow-y: auto;
}

.settings-form {
    margin-top: 4px;
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.field-unit {
    margin-left: 8px;
    color: var(--el-text-color-secondary);
    font-size: 13px;
}

.field-desc {
    margin-top: 4px;
    color: var(--el-text-color-placeholder);
    font-size: 12px;
    line-height: 1.5;
}

.coupon-tag {
    display: inline-block;
    margin-left: 8px;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 11px;
    background: var(--el-color-primary-light-9);
    color: var(--el-color-primary);
}

.coupon-value {
    margin-left: 8px;
    font-size: 12px;
    color: var(--el-color-danger);
}

.logs-card {
    margin-top: 14px;
}

.logs-filter {
    margin-bottom: 12px;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.strategy-card {
    margin-top: 14px;
}

.strategy-steps {
    padding-left: 20px;
    line-height: 2;
    font-size: 14px;
    color: var(--ink-700);

    code {
        background: var(--ink-50);
        padding: 1px 6px;
        border-radius: 3px;
        font-size: 12px;
    }
}

.strategy-table {
    width: 100%;
    font-size: 13px;
    border-collapse: collapse;

    th, td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid var(--ink-100);
    }

    th {
        color: var(--ink-500);
        font-weight: 500;
        background: var(--ink-50);
    }

    .hint {
        margin-left: 8px;
        color: var(--ink-400);
        font-size: 12px;
    }
}
</style>
