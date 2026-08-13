<template>
    <div class="order-list-container">
        <!-- 页头 -->
        <div class="page-head">
            <div>
                <h2 class="page-title">订单管理</h2>
                <p class="page-desc">下单、拆单、合单、改价与备注</p>
            </div>
            <div class="page-actions">
                <el-button @click="handleExport"> <i class="i-svg:download" /> 批量导出 </el-button>
                <el-tooltip :disabled="canMerge" :content="mergeDisabledReason" placement="top">
                    <span class="merge-btn-wrap">
                        <el-button
                            v-has-perm="['order.merge']"
                            :disabled="!canMerge"
                            @click="handleMerge"
                        >
                            合单
                        </el-button>
                    </span>
                </el-tooltip>
                <el-button
                    v-has-perm="['order.ship']"
                    :disabled="!selectedShippableIds.length"
                    type="primary"
                    @click="handleBatchShip"
                >
                    <i class="i-svg:truck" /> 批量发货
                </el-button>
            </div>
        </div>

        <!-- KPI 概览 -->
        <div class="row-14">
            <div v-for="card in kpiCards" :key="card.label" class="kpi-mini">
                <div class="lb">{{ card.label }}</div>
                <div class="nm num">{{ card.value }}</div>
                <div class="tr">
                    <span :class="card.tone">{{ card.suffix }}</span>
                </div>
            </div>
        </div>

        <!-- 单行过滤栏 -->
        <div class="filter-bar">
            <el-input
                v-model="searchForm.order_no"
                placeholder="搜索订单号 / 会员 / 商品"
                clearable
                style="width: 280px"
                @keyup.enter="handleSearch"
            />
            <span class="filter-label">状态：</span>
            <el-select
                v-model="searchForm.status"
                placeholder="全部"
                clearable
                style="width: 130px"
                @change="handleSearch"
            >
                <el-option label="待付款" value="pending" />
                <el-option label="待发货" value="paid" />
                <el-option label="已发货" value="shipped" />
                <el-option label="已完成" value="completed" />
                <el-option label="已取消" value="cancelled" />
                <el-option label="已关闭" value="closed" />
            </el-select>
            <span class="filter-label">支付方式：</span>
            <el-select
                v-model="searchForm.pay_type"
                placeholder="全部"
                clearable
                style="width: 120px"
                @change="handleSearch"
            >
                <el-option label="微信" value="wechat" />
                <el-option label="支付宝" value="alipay" />
                <el-option label="余额" value="balance" />
            </el-select>
            <span class="filter-label">下单时间：</span>
            <el-date-picker
                v-model="dateRange"
                type="daterange"
                range-separator="~"
                start-placeholder="开始"
                end-placeholder="结束"
                value-format="YYYY-MM-DD"
                style="width: 240px"
                @change="handleDateChange"
            />
            <span class="filter-sp" />
            <el-button @click="handleResetAll">重置</el-button>
            <el-button type="primary" @click="handleSearch">查询</el-button>
        </div>

        <!-- 表格 -->
        <ProTable
            title="订单列表"
            storage-key="order-list"
            :columns="columns"
            :data="list"
            :loading="loading"
            :pagination="pagination"
            selectable
            :show-column-config="false"
            @page-change="handlePageChange"
            @size-change="handleSizeChange"
            @selection-change="handleSelectionChange"
        >
            <template #headerExtra>
                <el-button @click="handleExport">导出</el-button>
                <el-button @click="handlePrint">打印配货单</el-button>
            </template>

            <template #order_meta="{ row }">
                <div class="order-no num" @click="handleDetail(row)">{{ row.order_no }}</div>
                <div class="order-time num">{{ row.created_at }}</div>
            </template>

            <template #user="{ row }">
                <div v-if="row.user" class="user-cell">
                    <el-avatar :size="28" :src="row.user.avatar || ''" class="user-cell__avatar">
                        {{ (row.user.nickname || row.user.mobile || '?')[0] }}
                    </el-avatar>
                    <div class="user-cell__info">
                        <div class="user-cell__name">
                            {{ row.user.nickname || `用户${row.user.id}` }}
                        </div>
                        <div v-if="row.user.mobile" class="user-cell__mobile num">
                            {{ row.user.mobile }}
                        </div>
                    </div>
                </div>
                <span v-else class="text-ink-400">—</span>
            </template>

            <template #items_summary="{ row }">
                <div v-if="row.items && row.items.length" class="items-cell">
                    <el-image
                        v-if="row.items[0].goods_image"
                        :src="row.items[0].goods_image"
                        class="items-cell__img"
                        fit="cover"
                    />
                    <div v-else class="items-cell__img items-cell__img--empty">
                        <i class="i-lucide:image" />
                    </div>
                    <div class="items-cell__info">
                        <div class="items-cell__name">{{ row.items[0].goods_name }}</div>
                        <div class="items-cell__meta">
                            <span v-if="row.items[0].spec_text" class="items-cell__spec">{{
                                row.items[0].spec_text
                            }}</span>
                            <span class="items-cell__qty">×{{ totalQty(row.items) }}</span>
                            <span v-if="row.items.length > 1" class="items-cell__more"
                                >共 {{ row.items.length }} 件</span
                            >
                        </div>
                    </div>
                </div>
                <span v-else class="text-ink-400">—</span>
            </template>

            <template #pay_amount="{ row }">
                <span class="num order-price">¥{{ formatPrice(row.pay_amount) }}</span>
            </template>

            <template #pay_ship="{ row }">
                <div>{{ payMethodLabel(row.pay_type) }}</div>
                <div class="pay-ship-sub">{{ payShipSubLabel(row) }}</div>
            </template>

            <template #delivery_type="{ row }">
                <el-tag :type="deliveryTagMap[row.delivery_type ?? 'express']?.type" size="small">{{
                    deliveryTagMap[row.delivery_type ?? 'express']?.label || '快递'
                }}</el-tag>
            </template>

            <template #status="{ row }">
                <el-tag :type="statusTagMap[row.status]?.type" size="small">{{
                    statusTagMap[row.status]?.label || row.status
                }}</el-tag>
            </template>

            <template #action="{ row }">
                <div class="order-list-actions">
                <el-button type="primary" size="small" text @click="handleDetail(row)"
                    >详情</el-button
                >
                <el-button type="primary" size="small" text @click="handleRemark(row)"
                    >备注</el-button
                >
                <el-dropdown
                    v-if="hasMoreActions(row)"
                    trigger="click"
                    @command="(cmd: string) => handleMoreCommand(cmd, row)"
                >
                    <el-button type="primary" size="small" text>
                        更多
                        <i class="i-lucide:chevron-down ml-0.5" />
                    </el-button>
                    <template #dropdown>
                        <el-dropdown-menu>
                            <el-dropdown-item
                                v-if="
                                    row.status === 'paid' &&
                                    (row.delivery_type ?? 'express') === 'express'
                                "
                                v-has-perm="['order.ship']"
                                command="ship"
                            >
                                发货
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="
                                    row.status === 'paid' &&
                                    row.delivery_type === 'merchant' &&
                                    row.delivery_order
                                "
                                v-has-perm="['order.ship']"
                                command="assign"
                            >
                                派单
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="
                                    row.status === 'shipped' &&
                                    row.delivery_type === 'merchant' &&
                                    row.delivery_order &&
                                    row.delivery_order.status !== 'completed'
                                "
                                v-has-perm="['order.ship']"
                                command="markDelivered"
                            >
                                标记送达
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="row.status === 'pending'"
                                v-has-perm="['order.price-adjust']"
                                command="priceAdjust"
                            >
                                改价
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="row.status === 'paid'"
                                v-has-perm="['order.split']"
                                command="split"
                            >
                                拆单
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="row.status === 'pending'"
                                v-has-perm="['order.update']"
                                command="remind"
                            >
                                催付
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="canEditAddress(row)"
                                v-has-perm="['order.update']"
                                command="editAddress"
                            >
                                修改地址
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="row.status === 'pending'"
                                v-has-perm="['order.update']"
                                command="cancel"
                            >
                                取消
                            </el-dropdown-item>
                            <el-dropdown-item
                                v-if="row.status === 'cancelled' || row.status === 'closed'"
                                v-has-perm="['order.delete']"
                                command="delete"
                            >
                                删除
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </template>
                </el-dropdown>
                </div>
            </template>
        </ProTable>

        <OrderShipDialog
            v-model="shipDialogVisible"
            :mode="shipMode"
            :order-id="currentOrderId"
            :order-ids="batchShipIds"
            @success="getList"
        />

        <!-- 同城配送 派单对话框 -->
        <el-dialog v-model="assignDialogVisible" title="派单（同城配送）" width="420px">
            <el-form label-width="80px">
                <el-form-item label="骑手" required>
                    <el-select
                        v-model="assignForm.staff_id"
                        placeholder="请选择骑手"
                        :loading="staffLoading"
                        style="width: 280px"
                    >
                        <el-option
                            v-for="s in staffOptions"
                            :key="s.id"
                            :label="s.phone ? `${s.name}（${s.phone}）` : s.name"
                            :value="s.id"
                        />
                    </el-select>
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="assignDialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="assignLoading" @click="confirmAssign"
                    >确认派单</el-button
                >
            </template>
        </el-dialog>

        <!-- 备注对话框 -->
        <el-dialog v-model="remarkVisible" title="订单备注" width="480px">
            <el-form label-width="80px">
                <el-form-item label="备注">
                    <el-input
                        v-model="remarkForm.remark"
                        type="textarea"
                        :rows="4"
                        placeholder="请输入备注"
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="remarkVisible = false">取消</el-button>
                <el-button type="primary" :loading="remarkLoading" @click="confirmRemark"
                    >确认</el-button
                >
            </template>
        </el-dialog>

        <OrderAddressDialog
            v-model="addressVisible"
            :order-id="addressOrderId"
            :address="addressSnapshot"
            @success="getList"
        />

        <!-- 改价对话框 -->
        <PriceAdjustDialog
            v-model="priceAdjustVisible"
            :order-id="priceAdjustOrderId"
            @success="onPriceAdjustSuccess"
        />

        <!-- 拆单对话框 -->
        <OrderSplitDialog
            v-model="splitVisible"
            :order-id="splitOrderId"
            @split-success="onSplitSuccess"
        />

        <!-- 合单对话框 -->
        <OrderMergeDialog v-model="mergeVisible" :orders="selectedRows" @success="onMergeSuccess" />

        <PickingListDialog v-model="pickingVisible" :order-ids="pickingOrderIds" />
    </div>
</template>

<script setup lang="ts" name="OrderList">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { deliveryOrderApi, deliveryStaffApi } from '@/api/delivery'
import { orderApi } from '@/api/order'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import { exportCsv } from '@/utils/exportCsv'
import OrderAddressDialog from '@/views/order/components/OrderAddressDialog.vue'
import OrderSplitDialog from '@/views/order/components/OrderSplitDialog.vue'
import PriceAdjustDialog from '@/views/order/components/PriceAdjustDialog.vue'

import OrderMergeDialog from './components/OrderMergeDialog.vue'
import OrderShipDialog from '../components/OrderShipDialog.vue'
import PickingListDialog from './components/PickingListDialog.vue'

interface OrderSearchForm {
    order_no: string
    status: string
    pay_type: string
    start_date?: string
    end_date?: string
}

const router = useRouter()

const statusTagMap: Record<
    string,
    { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }
> = {
    pending: { label: '待付款', type: 'warning' },
    paid: { label: '待发货', type: 'primary' },
    shipped: { label: '已发货', type: 'info' },
    completed: { label: '已完成', type: 'success' },
    cancelled: { label: '已取消', type: 'danger' },
    closed: { label: '已关闭', type: 'info' }
}

const PAY_METHODS: Record<string, string> = {
    wechat: '微信',
    alipay: '支付宝',
    balance: '余额'
}
const payMethodLabel = (m?: string) => (m && PAY_METHODS[m]) || '—'

const deliveryTagMap: Record<
    string,
    { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }
> = {
    express: { label: '快递', type: 'info' },
    merchant: { label: '同城配送', type: 'warning' },
    pickup: { label: '到店自提', type: 'success' }
}

// 支付/物流副行：快递订单显示快递公司，merchant 显示骑手姓名
function payShipSubLabel(row: Record<string, any>): string {
    if ((row.delivery_type ?? 'express') === 'merchant') {
        return row.delivery_order?.staff?.name ? `骑手：${row.delivery_order.staff.name}` : '待派单'
    }
    if (row.delivery_type === 'pickup') {
        return '到店自提'
    }
    return row.logistics?.express_company || '—'
}

const {
    list,
    loading,
    pagination,
    searchForm,
    getList,
    handleSearch,
    resetSearch,
    handlePageChange,
    handleSizeChange
} = useListPage<Record<string, any>, OrderSearchForm>({
    fetchFn: (params) => orderApi.getOrderList(params),
    defaultSearchForm: {
        order_no: '',
        status: '',
        pay_type: '',
        start_date: undefined,
        end_date: undefined
    }
})

const dateRange = ref<string[]>([])
const handleDateChange = (val: string[] | null) => {
    if (val && val.length === 2) {
        searchForm.start_date = val[0]
        searchForm.end_date = val[1]
    } else {
        searchForm.start_date = undefined
        searchForm.end_date = undefined
    }
}

const handleResetAll = () => {
    dateRange.value = []
    resetSearch()
}

// 列定义（与设计稿对齐：订单号/时间 + 会员 + 商品摘要 + 金额 + 支付/物流 + 状态 + 操作）
const columns: ProColumn[] = [
    { key: 'order_meta', label: '订单号 / 时间', minWidth: 220, required: true },
    { key: 'user', label: '会员', width: 160 },
    { key: 'items_summary', label: '商品摘要', minWidth: 220 },
    { key: 'pay_amount', label: '金额', width: 110, align: 'right' },
    { key: 'delivery_type', label: '配送方式', width: 120 },
    { key: 'pay_ship', label: '支付 / 物流', width: 150 },
    { key: 'status', label: '状态', width: 110 },
    { key: 'action', label: '操作', width: 200, fixed: 'right', required: true }
]

// 当前页 KPI（无后端聚合接口前的本地估算）
const kpiCards = computed(() => {
    const todayStr = new Date().toISOString().slice(0, 10)
    let todayCnt = 0
    let totalAmt = 0
    let amtCnt = 0
    let cancelCnt = 0
    for (const row of list.value) {
        const r = row as Record<string, any>
        if (typeof r.created_at === 'string' && r.created_at.startsWith(todayStr)) todayCnt += 1
        const amt = Number(r.pay_amount || 0)
        if (amt > 0) {
            totalAmt += amt
            amtCnt += 1
        }
        if (['cancelled', 'closed'].includes(r.status)) cancelCnt += 1
    }
    const avg = amtCnt ? totalAmt / amtCnt : 0
    const cancelRate = list.value.length
        ? ((cancelCnt / list.value.length) * 100).toFixed(1) + '%'
        : '0%'
    return [
        { label: '今日订单', value: formatCount(todayCnt), suffix: '当前页', tone: '' },
        { label: '今日 GMV', value: '¥ ' + formatPrice(totalAmt), suffix: '当前页', tone: '' },
        { label: '取消率', value: cancelRate, suffix: '当前页', tone: 'down' },
        { label: '客单价', value: '¥ ' + formatPrice(avg), suffix: '均值', tone: '' }
    ]
})

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')
const formatPrice = (price: number) => (price == null ? '0.00' : Number(price).toFixed(2))

// 选中行（批量发货 / 导出）
const selectedRows = ref<Record<string, any>[]>([])
const selectedShippableIds = computed(() =>
    selectedRows.value.filter((r) => r.status === 'paid').map((r) => r.id)
)

const handleSelectionChange = (rows: Record<string, any>[]) => {
    selectedRows.value = rows
}

// ─── 改价 / 拆单 / 合单 ─────────────────────────────────────────
const priceAdjustVisible = ref(false)
const priceAdjustOrderId = ref(0)
const splitVisible = ref(false)
const splitOrderId = ref(0)
const mergeVisible = ref(false)

const handlePriceAdjust = (row: Record<string, any>) => {
    priceAdjustOrderId.value = row.id
    priceAdjustVisible.value = true
}

const onPriceAdjustSuccess = () => {
    getList()
}

const handleSplit = (row: Record<string, any>) => {
    splitOrderId.value = row.id
    splitVisible.value = true
}

const onSplitSuccess = (childOrderId: number) => {
    getList()
    // 跳转到子订单详情
    router.push({ path: '/order/order-detail', query: { id: childOrderId } })
}

// 合单前置条件：>=2 笔、同一用户、全部待付款
const rowUserId = (row: Record<string, any>) => row.user?.id ?? row.user_id

const canMerge = computed(() => {
    const rows = selectedRows.value
    if (rows.length < 2) return false
    if (rows.some((r) => r.status !== 'pending')) return false
    const uid = rowUserId(rows[0])
    return rows.every((r) => rowUserId(r) === uid)
})

const mergeDisabledReason = computed(() => {
    const rows = selectedRows.value
    if (rows.length < 2) return '请勾选至少 2 笔订单'
    if (rows.some((r) => r.status !== 'pending')) return '仅待付款订单可合并'
    const uid = rowUserId(rows[0])
    if (rows.some((r) => rowUserId(r) !== uid)) return '仅同一用户的订单可合并'
    return ''
})

const handleMerge = () => {
    if (!canMerge.value) return
    mergeVisible.value = true
}

const onMergeSuccess = () => {
    selectedRows.value = []
    getList()
}

// 详情 / 取消 / 催付 / 备注
const handleDetail = (row: Record<string, any>) => {
    router.push({ path: '/order/order-detail', query: { id: row.id } })
}

const handleCancel = async (row: Record<string, any>) => {
    try {
        await ElMessageBox.confirm(`确定取消订单 ${row.order_no} 吗？`, '取消订单', {
            type: 'warning'
        })
        await orderApi.cancelOrder(row.id, { reason: '管理员取消' })
        ElMessage.success('订单已取消')
        getList()
    } catch (e) {
        if (e !== 'cancel') console.error('取消订单失败:', e)
    }
}

const canEditAddress = (row: Record<string, any>) =>
    (row.status === 'pending' || row.status === 'paid') &&
    (row.delivery_type ?? 'express') !== 'pickup'

/** 是否有可收入「更多」的操作（按状态，不含权限；无权限时菜单项会被 v-has-perm 隐藏） */
const hasMoreActions = (row: Record<string, any>) => {
    const type = row.delivery_type ?? 'express'
    if (row.status === 'pending') return true
    if (row.status === 'paid') return true // 拆单 / 发货或派单 / 改地址
    if (
        row.status === 'shipped' &&
        type === 'merchant' &&
        row.delivery_order &&
        row.delivery_order.status !== 'completed'
    ) {
        return true
    }
    if (row.status === 'cancelled' || row.status === 'closed') return true
    return canEditAddress(row)
}

const handleMoreCommand = (cmd: string, row: Record<string, any>) => {
    switch (cmd) {
        case 'ship':
            handleShip(row)
            break
        case 'assign':
            handleAssign(row)
            break
        case 'markDelivered':
            handleMarkDelivered(row)
            break
        case 'priceAdjust':
            handlePriceAdjust(row)
            break
        case 'split':
            handleSplit(row)
            break
        case 'remind':
            handleRemind(row)
            break
        case 'editAddress':
            handleEditAddress(row)
            break
        case 'cancel':
            handleCancel(row)
            break
        case 'delete':
            handleDelete(row)
            break
    }
}

const addressVisible = ref(false)
const addressOrderId = ref<number | null>(null)
const addressSnapshot = ref<Record<string, any> | null>(null)

const handleEditAddress = (row: Record<string, any>) => {
    addressOrderId.value = row.id
    addressSnapshot.value = row.address_snapshot || null
    addressVisible.value = true
}

const handleDelete = async (row: Record<string, any>) => {
    try {
        await ElMessageBox.confirm(
            `确定删除订单 ${row.order_no} 吗？删除后列表中将不再显示。`,
            '删除订单',
            { type: 'warning' }
        )
        await orderApi.deleteOrder(row.id)
        ElMessage.success('订单已删除')
        getList()
    } catch (e) {
        if (e !== 'cancel') console.error('删除订单失败:', e)
    }
}

const handleRemind = (row: Record<string, any>) => {
    ElMessage.info(`已向 ${row.user_nickname || row.user_name || '用户'} 发送催付提醒`)
}

// 备注
const remarkVisible = ref(false)
const remarkLoading = ref(false)
const currentRemarkOrderId = ref(0)
const remarkForm = reactive({ remark: '' })

const handleRemark = (row: Record<string, any>) => {
    currentRemarkOrderId.value = row.id
    remarkForm.remark = row.admin_remark || ''
    remarkVisible.value = true
}

const confirmRemark = async () => {
    remarkLoading.value = true
    try {
        await orderApi.addRemark(currentRemarkOrderId.value, remarkForm)
        ElMessage.success('备注已保存')
        remarkVisible.value = false
        getList()
    } catch (e) {
        console.error(e)
    } finally {
        remarkLoading.value = false
    }
}

// 发货 (单 / 批量)
const shipDialogVisible = ref(false)
const shipMode = ref<'single' | 'batch'>('single')
const currentOrderId = ref<number | null>(null)
const batchShipIds = ref<number[]>([])

// 商品数量统计（>1 件时显示总数）
const totalQty = (items: any[]): number =>
    Array.isArray(items) ? items.reduce((s, it) => s + Number(it.quantity || 0), 0) : 0

const handleShip = (row: Record<string, any>) => {
    shipMode.value = 'single'
    currentOrderId.value = row.id
    batchShipIds.value = []
    shipDialogVisible.value = true
}

const handleBatchShip = () => {
    if (!selectedShippableIds.value.length) return
    shipMode.value = 'batch'
    currentOrderId.value = null
    batchShipIds.value = [...selectedShippableIds.value]
    shipDialogVisible.value = true
}

// ─── 同城配送 派单 / 标记送达 ─────────────────────────────────────────────
const assignDialogVisible = ref(false)
const assignLoading = ref(false)
const currentDeliveryOrderId = ref<number | null>(null)
const assignForm = reactive({ staff_id: 0 })
const staffOptions = ref<{ id: number; name: string; phone: string }[]>([])
const staffLoading = ref(false)

async function loadStaffOptions() {
    if (staffOptions.value.length) return
    staffLoading.value = true
    try {
        const { data } = await deliveryStaffApi.getOptions()
        staffOptions.value = (data || []).map((s: any) => ({
            id: s.id,
            name: s.name,
            phone: s.phone || ''
        }))
    } finally {
        staffLoading.value = false
    }
}

const handleAssign = (row: Record<string, any>) => {
    if (!row.delivery_order?.id) {
        ElMessage.warning('未找到配送记录')
        return
    }
    currentDeliveryOrderId.value = row.delivery_order.id
    assignForm.staff_id = 0
    loadStaffOptions()
    assignDialogVisible.value = true
}

const confirmAssign = async () => {
    if (!currentDeliveryOrderId.value || !assignForm.staff_id) {
        ElMessage.warning('请选择骑手')
        return
    }
    assignLoading.value = true
    try {
        await deliveryOrderApi.assign(currentDeliveryOrderId.value, {
            staff_id: assignForm.staff_id
        })
        ElMessage.success('派单成功')
        assignDialogVisible.value = false
        getList()
    } finally {
        assignLoading.value = false
    }
}

const handleMarkDelivered = async (row: Record<string, any>) => {
    if (!row.delivery_order?.id) return
    try {
        await ElMessageBox.confirm('确认骑手已送达？此操作会把订单状态推进到已完成。', '标记送达', {
            type: 'warning'
        })
        await deliveryOrderApi.updateStatus(row.delivery_order.id, { status: 'completed' })
        ElMessage.success('已标记送达')
        getList()
    } catch (e) {
        if (e !== 'cancel') console.error(e)
    }
}

// 导出 / 打印
const handleExport = () => {
    const rows = selectedRows.value.length ? selectedRows.value : list.value
    if (!rows.length) {
        ElMessage.warning('暂无可导出数据')
        return
    }
    const today = new Date().toISOString().slice(0, 10)
    exportCsv(`订单列表_${today}.csv`, rows, [
        { label: '订单号', key: 'order_no' },
        { label: '会员', key: (r) => r.user_nickname || r.user_name || '' },
        {
            label: '商品',
            key: (r) =>
                r.items?.length
                    ? r.items[0].goods_name + (r.items.length > 1 ? ` 等${r.items.length}件` : '')
                    : ''
        },
        { label: '金额', key: (r) => formatPrice(r.pay_amount) },
        { label: '支付', key: (r) => payMethodLabel(r.pay_type) },
        { label: '物流', key: (r) => r.logistics?.express_company || '' },
        { label: '状态', key: (r) => statusTagMap[r.status]?.label || r.status },
        { label: '下单时间', key: 'created_at' }
    ])
    ElMessage.success(`已导出 ${rows.length} 条`)
}

const pickingVisible = ref(false)
const pickingOrderIds = ref<number[]>([])

const handlePrint = () => {
    const ids = selectedRows.value.map((r) => Number(r.id)).filter((id) => id > 0)
    if (!ids.length) {
        ElMessage.warning('请先选择要打印配货单的订单')
        return
    }
    pickingOrderIds.value = ids
    pickingVisible.value = true
}
</script>

<style lang="scss" scoped>
.order-list-container {
    .order-list-actions {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        white-space: nowrap;
    }

    .merge-btn-wrap {
        display: inline-block;
        margin: 0 12px;
    }

    .order-no {
        color: var(--brand-500);
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;

        &:hover {
            text-decoration: underline;
        }
    }

    .order-time {
        font-size: 11px;
        color: var(--ink-400);
        margin-top: 2px;
    }

    .items-summary {
        color: var(--ink-700);
        font-size: 12.5px;
    }

    .order-price {
        color: var(--ink-900);
        font-weight: 600;
    }

    .pay-ship-sub {
        font-size: 11px;
        color: var(--ink-400);
        margin-top: 2px;
    }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 8px;

        &__avatar {
            flex-shrink: 0;
        }

        &__info {
            line-height: 1.3;
            min-width: 0;
        }
        &__name {
            font-size: 13px;
            color: var(--ink-900);
        }
        &__mobile {
            font-size: 11px;
            color: var(--ink-400);
        }
    }

    .items-cell {
        display: flex;
        gap: 8px;
        align-items: flex-start;

        &__img {
            width: 44px;
            height: 44px;
            border-radius: 4px;
            flex-shrink: 0;
            background: var(--ink-50);

            &--empty {
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--ink-400);
            }
        }

        &__info {
            flex: 1;
            min-width: 0;
        }

        &__name {
            font-size: 12.5px;
            color: var(--ink-900);
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        &__meta {
            font-size: 11px;
            color: var(--ink-400);
            margin-top: 2px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        &__qty {
            color: var(--ink-700);
            font-weight: 500;
        }
    }
}
</style>
