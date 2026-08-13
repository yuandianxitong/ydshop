<script setup lang="ts" name="DeliveryExceptionList">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, ref } from 'vue'
import { deliveryExceptionTicketApi } from '@/api/delivery'
import { useListPage } from '@/hooks/useListPage'
import type {
    DeliveryExceptionTicket,
    DeliveryExceptionTicketQuery,
    DeliveryExceptionStatus,
    DeliveryExceptionType,
} from '@/types/api'

// ─── 映射字典 ──────────────────────────────────────
const typeMap: Record<DeliveryExceptionType, { label: string; tone: string }> = {
    delay:          { label: '延迟超时', tone: 'amber' },
    wrong_delivery: { label: '错送/丢件', tone: 'rose' },
    complaint:      { label: '客户投诉', tone: 'amber' },
    reassign:       { label: '改派请求', tone: 'blue' },
    weather:        { label: '天气/路况', tone: 'gray' },
}

const statusMap: Record<DeliveryExceptionStatus, { label: string; tone: string }> = {
    open:        { label: '待处理', tone: 'amber' },
    in_progress: { label: '处理中', tone: 'blue' },
    resolved:    { label: '已解决', tone: 'green' },
    closed:      { label: '已关闭', tone: 'gray' },
}

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const tabs: Array<{ key: string; label: string }> = [
    { key: '',            label: '全部' },
    { key: 'open',        label: '待处理' },
    { key: 'in_progress', label: '处理中' },
    { key: 'resolved',    label: '已解决' },
    { key: 'closed',      label: '已关闭' },
]

const typeOptions = (Object.keys(typeMap) as DeliveryExceptionType[]).map(
    k => ({ value: k, label: typeMap[k].label }),
)

interface Query extends DeliveryExceptionTicketQuery {
    status?: DeliveryExceptionStatus | ''
    type?: DeliveryExceptionType | ''
    keyword?: string
}

// ─── 列表 ──────────────────────────────────────────
const {
    list: tableData,
    loading,
    pagination,
    searchForm,
    getList: fetchList,
    handleSearch,
    handlePageChange,
    handleSizeChange,
    handleDelete,
} = useListPage<DeliveryExceptionTicket, Query>({
    fetchFn: (params) => deliveryExceptionTicketApi.getList(params),
    deleteFn: (id) => deliveryExceptionTicketApi.delete(id),
    defaultSearchForm: { status: '', type: '', keyword: '' },
})

const tab = computed({
    get: () => searchForm.status || '',
    set: (v) => (searchForm.status = v as DeliveryExceptionStatus | ''),
})

const switchTab = (k: string | number) => {
    searchForm.status = String(k) as DeliveryExceptionStatus | ''
    handleSearch()
}

// ─── 创建 dialog ───────────────────────────────────
const createDialogVisible = ref(false)
const createForm = ref({
    type: 'delay' as DeliveryExceptionType,
    title: '',
    description: '',
    delivery_order_id: undefined as number | undefined,
    reporter: '',
    contact: '',
    evidence: [] as string[],
})
const createLoading = ref(false)

const resetCreateForm = () => {
    createForm.value = {
        type: 'delay',
        title: '',
        description: '',
        delivery_order_id: undefined,
        reporter: '',
        contact: '',
        evidence: [],
    }
}

const openCreate = () => {
    resetCreateForm()
    createDialogVisible.value = true
}

const submitCreate = async () => {
    if (!createForm.value.title.trim()) {
        ElMessage.warning('请填写工单简述')
        return
    }
    createLoading.value = true
    try {
        await deliveryExceptionTicketApi.create({
            type: createForm.value.type,
            title: createForm.value.title.trim(),
            description: createForm.value.description.trim() || null,
            delivery_order_id: createForm.value.delivery_order_id ?? null,
            reporter: createForm.value.reporter.trim() || null,
            contact: createForm.value.contact.trim() || null,
            evidence: createForm.value.evidence.length ? createForm.value.evidence : null,
        })
        ElMessage.success('创建成功')
        createDialogVisible.value = false
        fetchList()
    } finally {
        createLoading.value = false
    }
}

// ─── 详情 drawer ───────────────────────────────────
const drawerVisible = ref(false)
const detail = ref<DeliveryExceptionTicket | null>(null)
const detailLoading = ref(false)

const openDetail = async (row: any) => {
    drawerVisible.value = true
    detailLoading.value = true
    try {
        const res = await deliveryExceptionTicketApi.getDetail(row.id)
        detail.value = res.data as DeliveryExceptionTicket
    } finally {
        detailLoading.value = false
    }
}

// ─── 状态流转 ──────────────────────────────────────
const transitionLoading = ref(false)

const reloadDetail = async (id: number) => {
    const res = await deliveryExceptionTicketApi.getDetail(id)
    detail.value = res.data as DeliveryExceptionTicket
}

const startProgress = async () => {
    if (!detail.value) return
    transitionLoading.value = true
    try {
        await deliveryExceptionTicketApi.transition(detail.value.id, 'in_progress')
        ElMessage.success('已开始处理')
        await reloadDetail(detail.value.id)
        fetchList()
    } finally {
        transitionLoading.value = false
    }
}

const markResolved = async () => {
    if (!detail.value) return
    try {
        const { value: note } = await ElMessageBox.prompt(
            '请填写处理说明（至少 5 字）',
            '标记解决',
            {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                inputType: 'textarea',
                inputValidator: (v) => (v && v.trim().length >= 5) || '请至少填 5 字',
            },
        )
        transitionLoading.value = true
        await deliveryExceptionTicketApi.transition(detail.value.id, 'resolved', (note as string).trim())
        ElMessage.success('已标记解决')
        await reloadDetail(detail.value.id)
        fetchList()
    } catch {
        // 用户取消
    } finally {
        transitionLoading.value = false
    }
}

const closeTicket = async () => {
    if (!detail.value) return
    transitionLoading.value = true
    try {
        await deliveryExceptionTicketApi.transition(detail.value.id, 'closed')
        ElMessage.success('已关闭')
        await reloadDetail(detail.value.id)
        fetchList()
    } finally {
        transitionLoading.value = false
    }
}

const formatTime = (s: string | null) => (s ? s.replace('T', ' ').slice(0, 19) : '-')
</script>

<template>
    <div class="delivery-exception">
        <!-- 页头 -->
        <div class="page-head">
            <div>
                <h2 class="page-title">异常工单</h2>
                <p class="page-desc">配送异常处理：延迟 / 错送 / 投诉 / 改派 / 路况</p>
            </div>
            <div class="page-actions">
                <el-button type="primary" @click="openCreate">+ 新建工单</el-button>
            </div>
        </div>

        <!-- filter-bar with seg + 搜索 -->
        <div class="filter-bar exception-filter">
            <div class="seg exception-seg">
                <button v-for="t in tabs" :key="t.key" :class="{ on: tab === t.key }" @click="switchTab(t.key)">
                    {{ t.label }}
                </button>
            </div>
            <span class="filter-sp" />
            <el-select
                v-model="searchForm.type"
                clearable
                placeholder="工单类型"
                style="width: 160px"
                @change="handleSearch"
            >
                <el-option v-for="o in typeOptions" :key="o.value" :label="o.label" :value="o.value" />
            </el-select>
            <el-input
                v-model="searchForm.keyword"
                placeholder="搜索单号 / 标题 / 详情"
                clearable
                style="width: 280px"
                @keyup.enter="handleSearch"
            />
            <el-button type="primary" @click="handleSearch">查询</el-button>
        </div>

        <!-- 列表 -->
        <el-card class="table-card" shadow="never">
            <div class="table-header">
                <span class="table-title">异常工单 <span class="table-count">共 {{ fmtCount(pagination.total) }} 条</span></span>
            </div>

            <el-table v-loading="loading" :data="tableData">
                <el-table-column label="单号" width="160">
                    <template #default="{ row }"><span class="num text-brand">{{ row.ticket_no }}</span></template>
                </el-table-column>
                <el-table-column label="类型" width="120">
                    <template #default="{ row }">
                        <el-tag :class="['tag-tone-' + (typeMap[row.type as DeliveryExceptionType]?.tone ?? 'gray')]" size="small" effect="light">{{ typeMap[row.type as DeliveryExceptionType]?.label ?? row.type }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="状态" width="110">
                    <template #default="{ row }">
                        <el-tag :class="['tag-tone-' + (statusMap[row.status as DeliveryExceptionStatus]?.tone ?? 'gray')]" size="small" effect="light">{{ statusMap[row.status as DeliveryExceptionStatus]?.label ?? row.status }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="title" label="标题" min-width="180" show-overflow-tooltip />
                <el-table-column label="关联配送单" width="120">
                    <template #default="{ row }">
                        <span v-if="row.delivery_order_id" class="num text-brand">DL-{{ row.delivery_order_id }}</span>
                        <span v-else class="text-secondary">-</span>
                    </template>
                </el-table-column>
                <el-table-column label="报告人" width="100">
                    <template #default="{ row }">{{ row.reporter || '-' }}</template>
                </el-table-column>
                <el-table-column label="处理人" width="100">
                    <template #default="{ row }">{{ row.handled_by_name || '-' }}</template>
                </el-table-column>
                <el-table-column label="创建时间" width="200">
                    <template #default="{ row }"><span class="num text-secondary">{{ formatTime(row.created_at) }}</span></template>
                </el-table-column>
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button text type="primary" size="small" @click="openDetail(row)">详情</el-button>
                        <el-button text type="danger" size="small" @click="handleDelete(row.id)">删除</el-button>
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

        <!-- 创建 dialog -->
        <el-dialog v-model="createDialogVisible" title="新建异常工单" width="540px">
            <el-form label-width="100px">
                <el-form-item label="类型" required>
                    <el-select v-model="createForm.type" style="width: 100%">
                        <el-option v-for="o in typeOptions" :key="o.value" :label="o.label" :value="o.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="简述" required>
                    <el-input v-model="createForm.title" maxlength="100" show-word-limit />
                </el-form-item>
                <el-form-item label="详情">
                    <el-input v-model="createForm.description" type="textarea" :rows="3" maxlength="5000" show-word-limit />
                </el-form-item>
                <el-form-item label="关联配送单">
                    <el-input-number v-model="createForm.delivery_order_id" :min="1" :precision="0" controls-position="right" placeholder="可选" style="width: 100%" />
                </el-form-item>
                <el-form-item label="报告人">
                    <el-input v-model="createForm.reporter" maxlength="50" />
                </el-form-item>
                <el-form-item label="联系方式">
                    <el-input v-model="createForm.contact" maxlength="100" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="createDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="createLoading" @click="submitCreate">提交</el-button>
            </template>
        </el-dialog>

        <!-- 详情 drawer -->
        <el-drawer v-model="drawerVisible" size="540px" title="工单详情">
            <div v-if="detail" v-loading="detailLoading" class="detail-pane">
                <div class="detail-row"><span class="lb">单号</span><span>{{ detail.ticket_no }}</span></div>
                <div class="detail-row"><span class="lb">类型</span>
                    <el-tag size="small">{{ typeMap[detail.type]?.label ?? detail.type }}</el-tag>
                </div>
                <div class="detail-row"><span class="lb">状态</span>
                    <el-tag size="small" :type="(statusMap[detail.status]?.tone ?? 'info') as any">
                        {{ statusMap[detail.status]?.label ?? detail.status }}
                    </el-tag>
                </div>
                <div class="detail-row"><span class="lb">关联配送单</span><span>{{ detail.delivery_order_id || '-' }}</span></div>
                <div class="detail-row"><span class="lb">标题</span><span>{{ detail.title }}</span></div>
                <div class="detail-row" v-if="detail.description"><span class="lb">详情</span><span class="multiline">{{ detail.description }}</span></div>
                <div class="detail-row" v-if="detail.reporter"><span class="lb">报告人</span><span>{{ detail.reporter }}</span></div>
                <div class="detail-row" v-if="detail.contact"><span class="lb">联系方式</span><span>{{ detail.contact }}</span></div>
                <div class="detail-row" v-if="detail.evidence?.length">
                    <span class="lb">附件</span>
                    <div class="evidence-list">
                        <el-image
                            v-for="(url, i) in detail.evidence"
                            :key="i"
                            :src="url"
                            :preview-src-list="detail.evidence"
                            fit="cover"
                            class="evidence-img"
                        />
                    </div>
                </div>
                <div class="detail-row" v-if="detail.resolution_note">
                    <span class="lb">处理说明</span>
                    <span class="multiline">{{ detail.resolution_note }}</span>
                </div>
                <div class="detail-row" v-if="detail.handled_by_name">
                    <span class="lb">处理人</span>
                    <span>{{ detail.handled_by_name }} · {{ formatTime(detail.handled_at) }}</span>
                </div>
                <div class="detail-row"><span class="lb">创建时间</span><span>{{ formatTime(detail.created_at) }}</span></div>
            </div>

            <template #footer v-if="detail">
                <el-button
                    v-if="detail.status === 'open'"
                    type="primary"
                    :loading="transitionLoading"
                    @click="startProgress"
                >
                    开始处理
                </el-button>
                <el-button
                    v-if="detail.status === 'in_progress'"
                    type="success"
                    :loading="transitionLoading"
                    @click="markResolved"
                >
                    标记解决
                </el-button>
                <el-button
                    v-if="detail.status === 'resolved'"
                    type="info"
                    :loading="transitionLoading"
                    @click="closeTicket"
                >
                    关闭工单
                </el-button>
                <el-text v-if="detail.status === 'closed'" type="info">
                    工单已关闭。如需重开请新建工单并在详情中引用本单号。
                </el-text>
            </template>
        </el-drawer>
    </div>
</template>

<style scoped lang="scss">
.delivery-exception {
    padding: 0;
}

.exception-filter {
    flex-wrap: wrap;
}

.exception-seg {
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
            font-weight: 600;
        }

        &:hover:not(.on) { color: var(--brand-500); }
    }
}

.detail-pane {
    padding: 0 4px;
}

.detail-row {
    display: flex;
    margin-bottom: 14px;
    font-size: 13px;

    .lb {
        flex: 0 0 90px;
        color: var(--ink-500, #8b95a7);
    }
    .multiline {
        white-space: pre-wrap;
        word-break: break-all;
    }
}

.evidence-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.evidence-img {
    width: 80px;
    height: 80px;
    border-radius: 4px;
    overflow: hidden;
    cursor: zoom-in;
}
</style>
