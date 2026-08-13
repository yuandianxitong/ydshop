<script setup lang="ts" name="DeliveryOrderList">
import { WarningFilled } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { deliveryOrderApi, deliveryStaffApi } from '@/api/delivery'
import { useExport } from '@/composables/useExport'
import { useListPage } from '@/hooks/useListPage'
import type {
    DeliveryOrderInfo,
    DeliveryOrderQuery,
    DeliveryPlatformOption,
    DeliveryStaffOption
} from '@/types/api'

import PlatformDetailDrawer from './components/PlatformDetailDrawer.vue'

const statusMap: Record<string, { label: string; tone: string }> = {
    pending: { label: '待派单', tone: 'gray' },
    assigned: { label: '已分配', tone: 'amber' },
    picking: { label: '取货中', tone: 'amber' },
    delivering: { label: '配送中', tone: 'blue' },
    completed: { label: '已完成', tone: 'green' },
    cancelled: { label: '已取消', tone: 'rose' }
}

const platformMap: Record<string, string> = {
    merchant: '商家配送',
    dada: '达达配送',
    fengniao: '蜂鸟配送',
    uupt: 'UU跑腿',
    shansong: '闪送',
    sfsc: '顺丰同城'
}

const platformFilterOptions = Object.entries(platformMap).map(([value, label]) => ({
    value,
    label
}))

const tabs = [
    { key: '', label: '全部' },
    { key: 'pending', label: '待派单' },
    { key: 'assigned', label: '已分配' },
    { key: 'picking', label: '取货中' },
    { key: 'delivering', label: '配送中' },
    { key: 'completed', label: '已完成' },
    { key: 'cancelled', label: '已取消' }
]

interface OrderQuery extends DeliveryOrderQuery {
    status?: string
    keyword?: string
    platform?: string
}

const {
    list: tableData,
    loading,
    pagination,
    searchForm,
    getList: fetchList,
    handleSearch,
    handlePageChange,
    handleSizeChange
} = useListPage<DeliveryOrderInfo, OrderQuery>({
    fetchFn: (params) => deliveryOrderApi.getList(params),
    defaultSearchForm: { status: '', keyword: '', platform: '' }
})

const tab = computed({ get: () => searchForm.status || '', set: (v) => (searchForm.status = v) })
const keyword = computed({
    get: () => searchForm.keyword || '',
    set: (v) => (searchForm.keyword = v)
})

const stats = computed(() => {
    const counters = {
        pending: 0,
        assigned: 0,
        picking: 0,
        delivering: 0,
        completed: 0,
        cancelled: 0
    }
    let totalFee = 0
    for (const row of tableData.value as any[]) {
        const s = row.status as keyof typeof counters
        if (s in counters) counters[s] += 1
        totalFee += Number(row.fee ?? 0)
    }
    return { ...counters, totalFee }
})

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')
const fmt = (v: any) =>
    Number(v ?? 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

// 收件人信息从订单 address_snapshot 取（自提单无收件地址）
const getReceiverName = (row: any) => {
    const snap = row.order?.address_snapshot
    return snap?.name || '-'
}
const getReceiverPhone = (row: any) => {
    const snap = row.order?.address_snapshot
    return snap?.phone || snap?.mobile || ''
}
const getReceiverAddress = (row: any) => {
    const snap = row.order?.address_snapshot
    if (!snap) return ''
    return [snap.province, snap.city, snap.district, snap.detail].filter(Boolean).join(' ')
}

const staffOptions = ref<DeliveryStaffOption[]>([])
const assignDialogVisible = ref(false)
const assignStaffId = ref<number | undefined>(undefined)
const assignLoading = ref(false)
const currentAssignId = ref(0)

const switchTab = (k: string) => {
    searchForm.status = k
    handleSearch()
}

const router = useRouter()
const goExceptionTickets = () => router.push('/delivery/exception')
const goRealtimeMap = () => router.push('/delivery/map')

const { exporting: exportingOrder, doExport: doExportOrder } = useExport()
const handleExport = () => {
    doExportOrder('/adminapi/delivery/order/export', searchForm, '配送记录')
}

const loadStaffOptions = async () => {
    try {
        const res = await deliveryStaffApi.getOptions()
        staffOptions.value = res.data || []
    } catch (e) {
        console.error('加载配送员失败:', e)
    }
}

const openAssignDialog = (row: any) => {
    currentAssignId.value = row.id
    assignStaffId.value = undefined
    assignDialogVisible.value = true
}

const confirmAssign = async () => {
    if (!assignStaffId.value) {
        ElMessage.warning('请选择配送员')
        return
    }
    try {
        assignLoading.value = true
        await deliveryOrderApi.assign(currentAssignId.value, { staff_id: assignStaffId.value })
        ElMessage.success('分配成功')
        assignDialogVisible.value = false
        fetchList()
    } finally {
        assignLoading.value = false
    }
}

const autoDispatching = ref(false)
const handleAutoDispatch = async () => {
    const pendingRows = (tableData.value as any[]).filter((r) => r.status === 'pending')
    if (pendingRows.length === 0) {
        ElMessage.warning('当前页无待派单订单')
        return
    }
    try {
        await ElMessageBox.confirm(
            `本页有 ${pendingRows.length} 单待派单，将按当前在班 staff 工作量最少分配。继续？`,
            '一键自动派单',
            { confirmButtonText: '派单', cancelButtonText: '取消' }
        )
    } catch {
        return
    }
    autoDispatching.value = true
    try {
        const res = await deliveryOrderApi.autoDispatch(pendingRows.map((r) => r.id))
        const data = (res.data ?? { assigned: [], skipped: [] }) as {
            assigned: any[]
            skipped: any[]
        }
        ElMessage.success(
            `派单完成：成功 ${data.assigned.length} 单，跳过 ${data.skipped.length} 单`
        )
        fetchList()
    } finally {
        autoDispatching.value = false
    }
}

const handleUpdateStatus = async (id: number, status: string) => {
    try {
        await deliveryOrderApi.updateStatus(id, { status })
        ElMessage.success('状态更新成功')
        fetchList()
    } catch (e) {
        console.error('状态更新失败:', e)
    }
}

// ── 三方平台发单 ──
const platformOptions = ref<DeliveryPlatformOption[]>([])
const dispatchDialogVisible = ref(false)
const dispatchPlatform = ref('')
const dispatchLoading = ref(false)
const currentDispatchId = ref(0)

const loadPlatformOptions = async () => {
    try {
        const res = await deliveryOrderApi.getPlatformOptions()
        platformOptions.value = res.data || []
    } catch (e) {
        console.error('加载三方平台失败:', e)
    }
}

const openDispatchDialog = (row: any) => {
    currentDispatchId.value = row.id
    dispatchPlatform.value = platformOptions.value[0]?.code || ''
    dispatchDialogVisible.value = true
}

const confirmDispatch = async () => {
    if (!dispatchPlatform.value) {
        ElMessage.warning('请选择配送平台')
        return
    }
    try {
        dispatchLoading.value = true
        await deliveryOrderApi.dispatch(currentDispatchId.value, {
            platform: dispatchPlatform.value
        })
        ElMessage.success('发单成功')
        dispatchDialogVisible.value = false
        fetchList()
    } finally {
        // 接口报错时响应拦截器已用 ElMessage.error 展示后端 message
        dispatchLoading.value = false
    }
}

// ── 三方平台状态同步 ──
const canSync = (row: any) =>
    row.platform && row.platform !== 'merchant' && !['completed', 'cancelled'].includes(row.status)

const syncingId = ref(0)
const handleSync = async (row: any) => {
    try {
        syncingId.value = row.id
        await deliveryOrderApi.sync(row.id)
        ElMessage.success('同步成功')
        fetchList()
    } finally {
        syncingId.value = 0
    }
}

// ── 详情抽屉 ──
const detailDrawerRef = ref<InstanceType<typeof PlatformDetailDrawer>>()
const openDetail = (row: any) => {
    detailDrawerRef.value?.open(row.id)
}

onMounted(() => {
    loadStaffOptions()
    loadPlatformOptions()
})
</script>

<template>
    <div class="delivery-order-container">
        <!-- 页头 -->
        <div class="page-head">
            <div>
                <h2 class="page-title">配送记录</h2>
                <p class="page-desc">所有配送方式的实时与历史记录</p>
            </div>
            <div class="page-actions">
                <el-button :loading="exportingOrder" @click="handleExport">导出</el-button>
                <el-button :loading="autoDispatching" type="primary" @click="handleAutoDispatch"
                    >一键自动派单</el-button
                >
                <el-button @click="goExceptionTickets">异常工单</el-button>
                <el-button type="primary" @click="goRealtimeMap">实时地图</el-button>
            </div>
        </div>

        <!-- KPI -->
        <div class="row-14">
            <div class="kpi-mini">
                <div class="lb">配送总数</div>
                <div class="nm num">{{ fmtCount(pagination.total) }}</div>
                <div class="tr"><span style="color: var(--ink-500)">分页统计</span></div>
            </div>
            <div class="kpi-mini">
                <div class="lb">配送中</div>
                <div class="nm num">
                    {{ fmtCount(stats.delivering + stats.assigned + stats.picking) }}
                </div>
                <div class="tr"><span style="color: var(--brand-500)">本页统计</span></div>
            </div>
            <div class="kpi-mini">
                <div class="lb">已完成</div>
                <div class="nm num">{{ fmtCount(stats.completed) }}</div>
                <div class="tr"><span style="color: var(--success)">本页统计</span></div>
            </div>
            <div class="kpi-mini">
                <div class="lb">本页运费</div>
                <div class="nm num">¥ {{ fmt(stats.totalFee) }}</div>
                <div class="tr"><span style="color: var(--rose-500)">已计入</span></div>
            </div>
        </div>

        <!-- filter-bar with seg + 搜索 -->
        <div class="filter-bar order-filter">
            <div class="seg order-seg">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    :class="{ on: tab === t.key }"
                    @click="switchTab(t.key)"
                >
                    {{ t.label }}
                </button>
            </div>
            <span class="filter-sp" />
            <el-select
                v-model="searchForm.platform"
                placeholder="全部平台"
                clearable
                style="width: 150px"
                @change="handleSearch"
            >
                <el-option label="全部平台" value="" />
                <el-option
                    v-for="p in platformFilterOptions"
                    :key="p.value"
                    :label="p.label"
                    :value="p.value"
                />
            </el-select>
            <el-input
                v-model="keyword"
                placeholder="搜索配送单号 / 订单号 / 收件人"
                clearable
                style="width: 280px"
                @keyup.enter="handleSearch"
            />
            <el-button type="primary" @click="handleSearch">查询</el-button>
        </div>

        <!-- 表格 -->
        <el-card class="table-card" shadow="never">
            <div class="table-header">
                <span class="table-title"
                    >配送记录
                    <span class="table-count">共 {{ fmtCount(pagination.total) }} 条</span></span
                >
            </div>

            <el-table v-loading="loading" :data="tableData">
                <el-table-column label="配送单号" width="120">
                    <template #default="{ row }"
                        ><span class="num text-brand">DL-{{ row.id }}</span></template
                    >
                </el-table-column>

                <el-table-column label="关联订单" width="180" show-overflow-tooltip>
                    <template #default="{ row }">
                        <span class="num text-secondary">{{ row.order?.order_no || '-' }}</span>
                    </template>
                </el-table-column>

                <el-table-column label="平台" width="130">
                    <template #default="{ row }">
                        <div class="flex items-center gap-1">
                            <el-tag class="tag-tone-blue" size="small" effect="light">{{
                                platformMap[row.platform] || row.platform || '-'
                            }}</el-tag>
                            <el-tooltip
                                v-if="row.dispatch_fail_reason"
                                :content="`发单失败：${row.dispatch_fail_reason}`"
                                placement="top"
                            >
                                <el-icon class="dispatch-fail-icon"><WarningFilled /></el-icon>
                            </el-tooltip>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column label="配送员" width="140">
                    <template #default="{ row }">
                        <span v-if="row.staff?.name" class="staff-cell">
                            <span class="staff-name">{{ row.staff.name }}</span>
                        </span>
                        <span v-else class="text-secondary">未分配</span>
                    </template>
                </el-table-column>

                <el-table-column label="收件人" min-width="220">
                    <template #default="{ row }">
                        <div class="receiver">
                            <div class="receiver-name">
                                {{ getReceiverName(row)
                                }}<span v-if="getReceiverPhone(row)" class="num receiver-phone">{{
                                    getReceiverPhone(row)
                                }}</span>
                            </div>
                            <div v-if="getReceiverAddress(row)" class="receiver-addr">
                                {{ getReceiverAddress(row) }}
                            </div>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column label="运费" width="110" align="right">
                    <template #default="{ row }">
                        <span class="num fee">¥ {{ fmt(row.fee) }}</span>
                    </template>
                </el-table-column>

                <el-table-column label="更新时间" prop="updated_at" width="200">
                    <template #default="{ row }"
                        ><span class="num text-secondary">{{
                            row.updated_at || row.created_at
                        }}</span></template
                    >
                </el-table-column>

                <el-table-column label="状态" width="110">
                    <template #default="{ row }">
                        <el-tag
                            :class="['tag-tone-' + (statusMap[row.status]?.tone || 'gray')]"
                            size="small"
                            effect="light"
                            >{{ statusMap[row.status]?.label || row.status }}</el-tag
                        >
                    </template>
                </el-table-column>

                <el-table-column label="操作" width="240" fixed="right">
                    <template #default="{ row }">
                        <div class="flex flex-wrap gap-1">
                            <el-button
                                v-if="row.status === 'pending'"
                                text
                                type="primary"
                                size="small"
                                @click="openAssignDialog(row)"
                                >分配</el-button
                            >
                            <el-button
                                v-if="row.status === 'pending'"
                                text
                                type="primary"
                                size="small"
                                @click="openDispatchDialog(row)"
                                >平台发单</el-button
                            >
                            <el-button
                                v-if="row.status === 'assigned'"
                                text
                                type="primary"
                                size="small"
                                @click="handleUpdateStatus(row.id, 'picking')"
                                >取货</el-button
                            >
                            <el-button
                                v-if="row.status === 'picking'"
                                text
                                type="primary"
                                size="small"
                                @click="handleUpdateStatus(row.id, 'delivering')"
                                >配送</el-button
                            >
                            <el-button
                                v-if="row.status === 'delivering'"
                                text
                                type="success"
                                size="small"
                                @click="handleUpdateStatus(row.id, 'completed')"
                                >完成</el-button
                            >
                            <el-button
                                v-if="canSync(row)"
                                text
                                type="primary"
                                size="small"
                                :loading="syncingId === row.id"
                                @click="handleSync(row)"
                                >同步状态</el-button
                            >
                            <el-button
                                v-if="['pending', 'assigned', 'picking'].includes(row.status)"
                                text
                                type="danger"
                                size="small"
                                @click="handleUpdateStatus(row.id, 'cancelled')"
                                >取消</el-button
                            >
                            <el-button text size="small" @click="openDetail(row)">详情</el-button>
                        </div>
                    </template>
                </el-table-column>
            </el-table>

            <el-pagination
                v-model:current-page="pagination.page"
                v-model:page-size="pagination.limit"
                :total="pagination.total"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, sizes, prev, pager, next, jumper"
                class="pagination"
                @size-change="handleSizeChange"
                @current-change="handlePageChange"
            />
        </el-card>

        <!-- 分配配送员对话框 -->
        <el-dialog v-model="assignDialogVisible" title="分配配送员" width="420px">
            <el-form label-width="80px">
                <el-form-item label="配送员">
                    <el-select
                        v-model="assignStaffId"
                        placeholder="请选择配送员"
                        style="width: 100%"
                    >
                        <el-option
                            v-for="s in staffOptions"
                            :key="s.id"
                            :label="`${s.name} (${s.phone})`"
                            :value="s.id"
                        />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="assignDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="assignLoading" @click="confirmAssign"
                    >确定</el-button
                >
            </template>
        </el-dialog>

        <!-- 三方平台发单对话框 -->
        <el-dialog v-model="dispatchDialogVisible" title="平台发单" width="420px">
            <el-form label-width="80px">
                <el-form-item label="配送平台">
                    <el-select
                        v-model="dispatchPlatform"
                        placeholder="请选择配送平台"
                        style="width: 100%"
                    >
                        <el-option
                            v-for="p in platformOptions"
                            :key="p.code"
                            :label="p.label"
                            :value="p.code"
                        />
                    </el-select>
                </el-form-item>
            </el-form>
            <el-empty
                v-if="platformOptions.length === 0"
                description="暂无已启用的三方平台，请先在「配送设置」中启用"
                :image-size="64"
            />
            <template #footer>
                <el-button @click="dispatchDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="dispatchLoading" @click="confirmDispatch"
                    >发单</el-button
                >
            </template>
        </el-dialog>

        <!-- 配送单详情抽屉 -->
        <PlatformDetailDrawer
            ref="detailDrawerRef"
            :status-map="statusMap"
            :platform-map="platformMap"
        />
    </div>
</template>

<style lang="scss" scoped>
.delivery-order-container {
    padding: 0;
}

.order-filter {
    flex-wrap: wrap;
}

.order-seg {
    display: inline-flex;
    background: var(--ink-50);
    padding: 3px;
    border-radius: 8px;

    button {
        border: 0;
        background: transparent;
        padding: 6px 14px;
        font-size: 13px;
        color: var(--ink-600);
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;

        &.on {
            background: #fff;
            color: var(--brand-600);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            font-weight: 600;
        }

        &:hover:not(.on) {
            color: var(--brand-500);
        }
    }
}

.staff-cell {
    display: flex;
    align-items: center;
    gap: 6px;
}

.staff-name {
    font-weight: 500;
    color: var(--ink-900);
    font-size: 13px;
}

.receiver {
    line-height: 1.4;
}
.receiver-name {
    font-size: 13px;
    color: var(--ink-900);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.receiver-phone {
    font-size: 12px;
    color: var(--ink-500);
}
.receiver-addr {
    font-size: 11.5px;
    color: var(--ink-500);
    margin-top: 2px;
}

.fee {
    font-weight: 600;
    color: var(--ink-900);
}

.dispatch-fail-icon {
    color: var(--el-color-warning);
    font-size: 14px;
    cursor: help;
    flex-shrink: 0;
}
</style>
