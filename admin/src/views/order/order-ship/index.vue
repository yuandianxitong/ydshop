<template>
  <div class="order-ship-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">发货管理</h2>
        <p class="page-desc">拣货、打单、物流对接与运费模板（仅快递订单；同城配送与自提订单请分别前往配送记录 / 核销页面处理）</p>
      </div>
      <div class="page-actions">
        <el-button :loading="printing" :disabled="!selectedShippableIds.length" @click="handleBatchPrint">
          <i class="i-svg:download" /> 批量打单
        </el-button>
        <el-button v-has-perm="['order.ship']" :disabled="!selectedShippableIds.length" type="primary" @click="handleBatchShip">
          <i class="i-svg:truck" /> 批量发货
        </el-button>
      </div>
    </div>

    <!-- filter-bar 内置 tab 切换 + 搜索（与设计稿一致） -->
    <div class="filter-bar ship-filter">
      <div class="seg ship-tab-seg">
        <button :class="{ on: searchForm.status === 'paid' }" @click="setStatus('paid')">
          待发货
        </button>
        <button :class="{ on: searchForm.status === 'shipped' }" @click="setStatus('shipped')">
          已发货
        </button>
        <button :class="{ on: searchForm.status === 'completed' }" @click="setStatus('completed')">
          已完成
        </button>
        <button :class="{ on: searchForm.status === '' }" @click="setStatus('')">
          全部
        </button>
      </div>
      <el-input
        v-model="searchForm.order_no"
        placeholder="搜索订单号 / 收件人"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
    </div>

    <!-- 表格 -->
    <ProTable
      title="发货列表"
      storage-key="order-ship-list"
      :columns="columns"
      :data="list"
      :loading="loading"
      :pagination="pagination"
      selectable
      :show-column-config="true"
      @page-change="handlePageChange"
      @size-change="handleSizeChange"
      @selection-change="handleSelectionChange"
    >
      <template #order_no="{ row }">
        <span class="num link-text">{{ row.order_no }}</span>
      </template>

      <template #recipient="{ row }">
        <div class="recipient-name">{{ getRecipientName(row) }}</div>
        <div class="recipient-addr">{{ getRecipientAddress(row) }}</div>
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
              <span v-if="row.items[0].spec_text" class="items-cell__spec">{{ row.items[0].spec_text }}</span>
              <span class="items-cell__qty">×{{ totalQty(row.items) }}</span>
              <span v-if="row.items.length > 1" class="items-cell__more">共 {{ row.items.length }} 件</span>
            </div>
          </div>
        </div>
        <span v-else class="text-ink-400">—</span>
      </template>

      <template #weight="{ row }">
        <span class="num">{{ row.weight ? row.weight + 'kg' : '—' }}</span>
      </template>

      <template #warehouse="{ row }">
        <span>{{ row.warehouse || '默认仓' }}</span>
      </template>

      <template #status="{ row }">
        <el-tag :type="statusTagMap[row.status]?.type" size="small">{{ statusTagMap[row.status]?.label || row.status }}</el-tag>
      </template>

      <template #action="{ row }">
        <el-button v-if="row.status === 'paid' && row.delivery_type === 'express'" v-has-perm="['order.ship']" type="primary" size="small" text @click="handleShip(row)">
          发货
        </el-button>
        <el-button v-if="row.status === 'shipped' || row.status === 'completed'" v-has-perm="['order.ship']" type="primary" size="small" text @click="handleEditLogistics(row)">
          修改物流
        </el-button>
        <el-button v-if="row.status === 'shipped' || row.status === 'completed'" type="primary" size="small" text @click="handleTracking(row)">
          轨迹
        </el-button>
        <el-button type="primary" size="small" text @click="handleDetail(row)">详情</el-button>
      </template>
    </ProTable>

    <OrderShipDialog
      v-model="shipDialogVisible"
      :mode="shipMode === 'batch' ? 'batch' : 'single'"
      :order-id="currentOrderId"
      :order-ids="batchShipIds"
      @success="getList"
    />

    <!-- 修改物流对话框 -->
    <el-dialog v-model="editDialogVisible" title="修改物流信息" width="480px" @open="loadExpressOptions" @close="resetEditForm">
      <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-width="100px">
        <el-form-item label="快递公司" prop="express_company">
          <el-select
            v-model="editForm.express_company"
            filterable
            placeholder="请选择快递公司"
            style="width: 280px"
            :loading="expressLoading"
          >
            <el-option
              v-for="opt in expressOptions"
              :key="opt.value"
              :label="opt.label"
              :value="opt.label"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="快递单号" prop="express_no">
          <el-input v-model="editForm.express_no" placeholder="请输入快递单号" style="width: 280px" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="editLoading" @click="confirmEditLogistics">保存</el-button>
      </template>
    </el-dialog>

    <!-- 轨迹弹窗 -->
    <el-dialog v-model="trackDialogVisible" title="物流轨迹" width="560px">
      <div v-loading="trackLoading">
        <div v-if="trackingInfo?.traces && trackingInfo.traces.length" class="track-list">
          <div v-for="(t, i) in trackingInfo.traces" :key="i" class="track-item" :class="{ 'track-first': i === 0 }">
            <span class="track-dot" />
            <div class="track-content">
              <div class="track-time num">{{ t.time }}</div>
              <div class="track-text">{{ t.context }}</div>
            </div>
          </div>
        </div>
        <el-empty v-else description="暂无物流轨迹" />
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="OrderShipList">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { expressCompanyApi } from '@/api/express-company'
import { orderApi } from '@/api/order'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import type { WaybillBatchResult } from '@/types/api'
import OrderShipDialog from '@/views/order/components/OrderShipDialog.vue'

interface ShipSearchForm {
  order_no: string
  status: string
  delivery_type: string
}

const router = useRouter()

const statusTagMap: Record<string, { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }> = {
  pending: { label: '待付款', type: 'warning' },
  paid: { label: '待发货', type: 'warning' },
  shipped: { label: '已发货', type: 'primary' },
  completed: { label: '已完成', type: 'success' },
  cancelled: { label: '已取消', type: 'danger' },
  closed: { label: '已关闭', type: 'info' },
}

const {
  list,
  loading,
  pagination,
  searchForm,
  getList,
  handleSearch,
  handlePageChange,
  handleSizeChange,
} = useListPage<Record<string, any>, ShipSearchForm>({
  fetchFn: (params) => orderApi.getOrderList(params),
  // 本页面语义即快递发货：同城配送走「配送记录」页派单、自提走核销，固定只查快递订单
  defaultSearchForm: { order_no: '', status: 'paid', delivery_type: 'express' },
})

// 自提单 (delivery_type='pickup') 没有快递地址，展示门店占位；其他订单读 address_snapshot
function getRecipientName(row: Record<string, any>): string {
  if (row.delivery_type === 'pickup') return '到店自提'
  const snap = row.address_snapshot
  if (!snap) return '—'
  const name = snap.name || ''
  const phone = snap.phone || snap.mobile || ''
  return name && phone ? `${name} ${phone}` : (name || phone || '—')
}

function getRecipientAddress(row: Record<string, any>): string {
  if (row.delivery_type === 'pickup') return row.user?.nickname || '—'
  const snap = row.address_snapshot
  if (!snap) return '—'
  return [snap.province, snap.city, snap.district, snap.detail].filter(Boolean).join(' ') || '—'
}

const setStatus = (s: string) => {
  searchForm.status = s
  handleSearch()
}

// 列定义（订单号 + 收件人/地址 + 商品 + 重量 + 仓库 + 状态 + 操作）
const columns: ProColumn[] = [
  { key: 'order_no', label: '订单号', width: 200, required: true },
  { key: 'recipient', label: '收件人 / 地址', minWidth: 240 },
  { key: 'items_summary', label: '商品', minWidth: 200 },
  { key: 'weight', label: '重量', width: 100, align: 'right' },
  { key: 'warehouse', label: '仓库', width: 120, defaultVisible: false },
  { key: 'status', label: '状态', width: 110 },
  { key: 'action', label: '操作', width: 240, fixed: 'right', required: true },
]

// 选中行
const selectedRows = ref<Record<string, any>[]>([])
const selectedShippableIds = computed(() =>
  selectedRows.value.filter((r) => r.status === 'paid' && r.delivery_type === 'express').map((r) => r.id)
)

const handleSelectionChange = (rows: Record<string, any>[]) => {
  selectedRows.value = rows
}

const handleDetail = (row: Record<string, any>) => {
  router.push({ path: '/order/order-detail', query: { id: row.id } })
}

// 发货
const shipDialogVisible = ref(false)
const shipMode = ref<'ship' | 'batch'>('ship')
const currentOrderId = ref<number | null>(null)
const batchShipIds = ref<number[]>([])

// 修改物流
const editDialogVisible = ref(false)
const editLoading = ref(false)
const editFormRef = ref()
const currentLogisticsId = ref<number | null>(null)
const editForm = reactive({ express_company: '', express_no: '' })
const editRules = {
  express_company: [{ required: true, message: '请选择快递公司', trigger: 'change' }],
  express_no: [{ required: true, message: '请输入快递单号', trigger: 'blur' }],
}

// 商品数量（>1 件时显示总数）
const totalQty = (items: any[]): number =>
  Array.isArray(items) ? items.reduce((s, it) => s + Number(it.quantity || 0), 0) : 0

// 快递公司下拉数据
interface ExpressOption { label: string; value: string | number }
const expressOptions = ref<ExpressOption[]>([])
const expressLoading = ref(false)
async function loadExpressOptions() {
  if (expressOptions.value.length) return
  expressLoading.value = true
  try {
    const { data } = await expressCompanyApi.getOptions()
    expressOptions.value = (data || []).map((opt: any) => ({
      label: opt.name || opt.label,
      value: opt.id ?? opt.value ?? opt.code ?? opt.name,
    }))
  } finally {
    expressLoading.value = false
  }
}

const handleShip = (row: Record<string, any>) => {
  shipMode.value = 'ship'
  currentOrderId.value = row.id
  batchShipIds.value = []
  shipDialogVisible.value = true
}

const handleEditLogistics = (row: Record<string, any>) => {
  currentLogisticsId.value = row.id
  editForm.express_company = row.logistics?.express_company || ''
  editForm.express_no = row.logistics?.express_no || ''
  editDialogVisible.value = true
}

const handleBatchShip = () => {
  if (!selectedShippableIds.value.length) return
  shipMode.value = 'batch'
  currentOrderId.value = null
  batchShipIds.value = [...selectedShippableIds.value]
  shipDialogVisible.value = true
}

const printing = ref(false)
const handleBatchPrint = async () => {
    const ids = selectedShippableIds.value
    if (!ids.length) {
        ElMessage.warning('请选择订单')
        return
    }
    if (ids.length > 50) {
        ElMessage.warning('单次最多 50 单')
        return
    }
    printing.value = true
    try {
        const res  = await orderApi.batchGenerateWaybill(ids)
        const data = (res.data ?? { success: [], failed: [] }) as WaybillBatchResult

        if (data.success.length === 0) {
            ElMessageBox.alert(
                '全部失败：\n' + data.failed.map(f => `订单 ${f.order_id}: ${f.reason}`).join('\n'),
                '生成失败',
            )
            return
        }

        const combined = data.success
            .map(s => s.print_template_html)
            .join('<div style="page-break-after:always"></div>')

        const fullDoc = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>电子面单</title></head><body>${combined}<script>setTimeout(function(){window.print();},300);<\/script></body></html>`
        const blob = new Blob([fullDoc], { type: 'text/html' })
        const url  = URL.createObjectURL(blob)
        const w    = window.open(url, '_blank', 'width=900,height=700')
        if (!w) {
            URL.revokeObjectURL(url)
            ElMessage.error('浏览器阻止弹窗，请允许后重试')
            return
        }
        setTimeout(() => URL.revokeObjectURL(url), 60_000)

        if (data.failed.length > 0) {
            ElMessageBox.alert(
                `成功 ${data.success.length} 张，失败 ${data.failed.length} 张：\n` +
                data.failed.map(f => `订单 ${f.order_id}: ${f.reason}`).join('\n'),
                '部分失败',
            )
        } else {
            ElMessage.success(`成功生成 ${data.success.length} 张面单`)
        }
    } finally {
        printing.value = false
    }
}

const confirmEditLogistics = async () => {
  if (!editFormRef.value || !currentLogisticsId.value) return
  await editFormRef.value.validate()
  editLoading.value = true
  try {
    await orderApi.updateLogistics(currentLogisticsId.value, {
      express_company: editForm.express_company,
      express_no: editForm.express_no,
    })
    ElMessage.success('物流信息已更新')
    editDialogVisible.value = false
    getList()
  } catch (e) {
    console.error(e)
  } finally {
    editLoading.value = false
  }
}

const resetEditForm = () => {
  editForm.express_company = ''
  editForm.express_no = ''
  currentLogisticsId.value = null
}

// 轨迹
const trackDialogVisible = ref(false)
const trackLoading = ref(false)
const trackingInfo = ref<Record<string, any> | null>(null)

const handleTracking = async (row: Record<string, any>) => {
  trackDialogVisible.value = true
  trackLoading.value = true
  trackingInfo.value = null
  try {
    const res = await orderApi.getTracking(row.id)
    trackingInfo.value = (res as any).data || null
  } catch (e) {
    console.error(e)
  } finally {
    trackLoading.value = false
  }
}
</script>

<style lang="scss" scoped>
.order-ship-container {
  .ship-filter {
    justify-content: flex-start;
  }

  .ship-tab-seg {
    margin-right: auto;
  }

  .link-text {
    color: var(--brand-500);
    font-weight: 500;
  }

  .recipient-name {
    font-weight: 500;
    color: var(--ink-900);
  }

  .recipient-addr {
    font-size: 11.5px;
    color: var(--ink-500);
    margin-top: 2px;
  }

  .items-summary {
    color: var(--ink-700);
    font-size: 12.5px;
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

    &__info { flex: 1; min-width: 0; }

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

    &__qty { color: var(--ink-700); font-weight: 500; }
  }
}

.track-list {
  position: relative;
  padding-left: 18px;

  &::before {
    content: '';
    position: absolute;
    left: 5px;
    top: 6px;
    bottom: 6px;
    width: 1px;
    background: var(--ink-200);
  }
}

.track-item {
  position: relative;
  padding-bottom: 16px;

  &:last-child { padding-bottom: 0; }

  .track-dot {
    position: absolute;
    left: -16px;
    top: 6px;
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background: var(--ink-200);
    border: 2px solid #fff;
  }

  &.track-first .track-dot {
    background: var(--brand-500);
    box-shadow: 0 0 0 3px var(--brand-100);
  }
}

.track-time {
  font-size: 11.5px;
  color: var(--ink-500);
  margin-bottom: 2px;
}

.track-text {
  font-size: 13px;
  color: var(--ink-800);
  line-height: 1.6;
}
</style>
