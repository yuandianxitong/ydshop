<template>
  <div class="order-refund-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">售后管理</h2>
        <p class="page-desc">仅退款与退货退款申请处理</p>
      </div>
      <div class="page-actions">
        <el-button @click="handleExport">
          <i class="i-svg:download" /> 导出
        </el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div v-for="card in kpiCards" :key="card.label" class="kpi-mini">
        <div class="lb">{{ card.label }}</div>
        <div class="nm num">{{ card.value }}</div>
        <div class="tr"><span :class="card.tone">{{ card.suffix }}</span></div>
      </div>
    </div>

    <!-- 单行过滤栏 -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索售后单 / 订单号"
        clearable
        style="width: 260px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">类型：</span>
      <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 130px" @change="handleSearch">
        <el-option label="仅退款" value="refund_only" />
        <el-option label="退货退款" value="return_refund" />
      </el-select>
      <span class="filter-label">状态：</span>
      <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 130px" @change="handleSearch">
        <el-option label="待审核" value="pending" />
        <el-option label="已同意" value="approved" />
        <el-option label="待退货" value="returning" />
        <el-option label="待验收" value="received" />
        <el-option label="退款中" value="refunding" />
        <el-option label="可重试失败" value="retryable_failed" />
        <el-option label="待人工复核" value="manual_review" />
        <el-option label="已退款" value="refunded" />
        <el-option label="已拒绝" value="rejected" />
      </el-select>
      <span class="filter-sp" />
      <el-button @click="resetSearch">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格 -->
    <ProTable
      title="售后退款"
      storage-key="order-refund-list"
      :columns="columns"
      :data="list"
      :loading="loading"
      :pagination="pagination"
      :show-column-config="false"
      @page-change="handlePageChange"
      @size-change="handleSizeChange"
    >
      <template #refund_no="{ row }">
        <span class="num link-text">{{ row.refund_no }}</span>
      </template>

      <template #order_no="{ row }">
        <span class="num order-src">{{ row.order?.order_no || '—' }}</span>
      </template>

      <template #user="{ row }">
        <div v-if="row.user" class="user-cell">
          <el-avatar :size="28" :src="row.user.avatar || ''" class="user-cell__avatar">
            {{ (row.user.nickname || row.user.mobile || '?')[0] }}
          </el-avatar>
          <div class="user-cell__info">
            <div class="user-cell__name">{{ row.user.nickname || `用户${row.user.id}` }}</div>
            <div v-if="row.user.mobile" class="user-cell__mobile num">{{ row.user.mobile }}</div>
          </div>
        </div>
        <span v-else class="text-ink-400">—</span>
      </template>

      <template #type="{ row }">
        <span>{{ typeMap[row.type] || row.type }}</span>
      </template>

      <template #refund_amount="{ row }">
        <span class="num refund-amt">¥{{ formatPrice(row.refund_amount) }}</span>
      </template>

      <template #reason="{ row }">
        <span class="reason-text">{{ row.reason || '—' }}</span>
      </template>

      <template #created_at="{ row }">
        <span class="num">{{ row.created_at }}</span>
      </template>

      <template #status="{ row }">
        <el-tag :type="statusTagMap[row.status]?.type" size="small">{{ statusTagMap[row.status]?.label || row.status }}</el-tag>
      </template>

      <template #action="{ row }">
        <el-button v-if="row.status === 'pending'" v-has-perm="['order.refund.approve']" type="primary" size="small" text @click="handleApprove(row)">
          处理
        </el-button>
        <el-button v-if="row.status === 'pending'" v-has-perm="['order.refund.reject']" type="danger" size="small" text @click="handleReject(row)">
          拒绝
        </el-button>
        <el-button v-if="['returning', 'received'].includes(row.status)" v-has-perm="['order.refund.approve']" type="primary" size="small" text @click="handleConfirmReceived(row)">
          确认收货
        </el-button>
        <el-button v-if="['refunding', 'retryable_failed', 'manual_review'].includes(row.status)" v-has-perm="['order.refund.approve']" type="warning" size="small" text @click="handleRetry(row)">
          {{ row.status === 'retryable_failed' ? '重新发起' : '同步退款' }}
        </el-button>
        <el-button v-if="row.status === 'retryable_failed'" v-has-perm="['order.refund.reject']" type="danger" size="small" text @click="handleReject(row)">
          释放退款
        </el-button>
      </template>
    </ProTable>

    <!-- 同意 -->
    <el-dialog v-model="approveDialogVisible" title="同意退款" width="480px" @close="resetApproveForm">
      <el-form ref="approveFormRef" :model="approveForm" label-width="100px">
        <el-form-item label="管理员备注">
          <el-input v-model="approveForm.admin_remark" type="textarea" :rows="4" placeholder="请输入备注（选填）" style="width: 300px" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="approveDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="approveLoading" @click="confirmApprove">确认同意</el-button>
      </template>
    </el-dialog>

    <!-- 拒绝 -->
    <el-dialog v-model="rejectDialogVisible" title="拒绝退款" width="480px" @close="resetRejectForm">
      <el-form ref="rejectFormRef" :model="rejectForm" :rules="rejectRules" label-width="100px">
        <el-form-item label="拒绝原因" prop="refuse_reason">
          <el-input v-model="rejectForm.refuse_reason" type="textarea" :rows="4" placeholder="请输入拒绝原因" style="width: 300px" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectDialogVisible = false">取消</el-button>
        <el-button type="danger" :loading="rejectLoading" @click="confirmReject">确认拒绝</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="OrderRefund">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, reactive, ref } from 'vue'

import { orderRefundApi } from '@/api/order-refund'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import { exportCsv } from '@/utils/exportCsv'

interface RefundSearchForm {
  keyword: string
  type: string
  status: string
}

const statusTagMap: Record<string, { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }> = {
  pending: { label: '待审核', type: 'warning' },
  approved: { label: '已同意 待退款', type: 'primary' },
  returning: { label: '退货寄回中', type: 'info' },
  received: { label: '待验收', type: 'info' },
  refunding: { label: '退款中', type: 'warning' },
  retryable_failed: { label: '渠道不存在 可重试', type: 'danger' },
  manual_review: { label: '待人工复核', type: 'danger' },
  refunded: { label: '已完成', type: 'success' },
  rejected: { label: '已拒绝', type: 'danger' },
}

const typeMap: Record<string, string> = {
  refund_only: '仅退款',
  return_refund: '退货退款',
  exchange: '换货',
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
  handleSizeChange,
} = useListPage<Record<string, any>, RefundSearchForm>({
  fetchFn: (params) => orderRefundApi.getRefundList(params),
  defaultSearchForm: { keyword: '', type: '', status: '' },
})

const columns: ProColumn[] = [
  { key: 'refund_no', label: '售后单号', minWidth: 180, required: true },
  { key: 'order_no', label: '原订单', width: 180 },
  { key: 'user', label: '会员', width: 180 },
  { key: 'type', label: '类型', width: 120 },
  { key: 'refund_amount', label: '金额', width: 120, align: 'right' },
  { key: 'reason', label: '原因', minWidth: 160 },
  { key: 'created_at', label: '申请时间', width: 200 },
  { key: 'status', label: '状态', width: 130 },
  { key: 'action', label: '操作', width: 200, fixed: 'right', required: true },
]

// KPI（当前页）
const kpiCards = computed(() => {
  const todayStr = new Date().toISOString().slice(0, 10)
  let todayCnt = 0
  let processing = 0
  let dispute = 0
  let refundAmt = 0
  for (const row of list.value) {
    const r = row as Record<string, any>
    if (typeof r.created_at === 'string' && r.created_at.startsWith(todayStr)) todayCnt += 1
    if (['pending', 'approved', 'returning', 'received', 'refunding', 'retryable_failed', 'manual_review'].includes(r.status)) processing += 1
    if (r.status === 'rejected') dispute += 1
    if (r.status === 'refunded') refundAmt += Number(r.refund_amount || 0)
  }
  return [
    { label: '今日新增', value: formatCount(todayCnt), suffix: '当前页', tone: '' },
    { label: '处理中',   value: formatCount(processing), suffix: '当前页', tone: '' },
    { label: '客户争议', value: formatCount(dispute), suffix: '需介入', tone: 'down' },
    { label: '已退金额', value: '¥ ' + formatPrice(refundAmt), suffix: '当前页', tone: '' },
  ]
})

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')
const formatPrice = (p: number) => (p == null ? '0.00' : Number(p).toFixed(2))

// 同意 / 拒绝 / 确认收货 / 同步退款
const currentRefundId = ref<number | null>(null)

const approveDialogVisible = ref(false)
const approveLoading = ref(false)
const approveFormRef = ref()
const approveForm = reactive({ admin_remark: '' })

const rejectDialogVisible = ref(false)
const rejectLoading = ref(false)
const rejectFormRef = ref()
const rejectForm = reactive({ refuse_reason: '' })
const rejectRules = {
  refuse_reason: [{ required: true, message: '请输入拒绝原因', trigger: 'blur' }],
}

const handleApprove = (row: Record<string, any>) => {
  currentRefundId.value = row.id
  approveDialogVisible.value = true
}
const resetApproveForm = () => {
  approveForm.admin_remark = ''
  currentRefundId.value = null
}
const confirmApprove = async () => {
  if (!currentRefundId.value) return
  approveLoading.value = true
  try {
    await orderRefundApi.approveRefund(currentRefundId.value, { admin_remark: approveForm.admin_remark })
    ElMessage.success('已同意退款申请')
    approveDialogVisible.value = false
    getList()
  } finally {
    approveLoading.value = false
  }
}

const handleReject = (row: Record<string, any>) => {
  currentRefundId.value = row.id
  rejectDialogVisible.value = true
}
const resetRejectForm = () => {
  rejectForm.refuse_reason = ''
  currentRefundId.value = null
}
const confirmReject = async () => {
  if (!rejectFormRef.value || !currentRefundId.value) return
  await rejectFormRef.value.validate()
  rejectLoading.value = true
  try {
    await orderRefundApi.rejectRefund(currentRefundId.value, { refuse_reason: rejectForm.refuse_reason })
    ElMessage.success('已拒绝退款申请')
    rejectDialogVisible.value = false
    getList()
  } finally {
    rejectLoading.value = false
  }
}

const handleConfirmReceived = async (row: Record<string, any>) => {
  try {
    await ElMessageBox.confirm('确认已收到退货商品并核验无误？确认后将执行退款。', '确认收货', {
      type: 'warning',
    })
    await orderRefundApi.confirmReceived(row.id)
    ElMessage.success('已确认收货')
    getList()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

const handleRetry = async (row: Record<string, any>) => {
  try {
    const retrying = row.status === 'retryable_failed'
    await ElMessageBox.confirm(
      retrying ? '渠道已明确不存在原退款请求，将使用同一幂等退款单号重新发起，是否继续？' : '将使用原退款单号同步支付渠道结果，是否继续？',
      retrying ? '重新发起退款' : '同步退款', {
      type: 'warning',
      })
    await orderRefundApi.retryRefund(row.id)
    ElMessage.success(retrying ? '退款已重新发起' : '退款结果已同步')
    getList()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

// 导出
const handleExport = () => {
  if (!list.value.length) {
    ElMessage.warning('暂无可导出数据')
    return
  }
  const today = new Date().toISOString().slice(0, 10)
  exportCsv(`售后单_${today}.csv`, list.value, [
    { label: '售后单号', key: 'refund_no' },
    { label: '原订单', key: (r) => r.order?.order_no || '' },
    { label: '会员', key: (r) => r.user?.nickname || r.user?.mobile || '' },
    { label: '类型', key: (r) => typeMap[r.type] || r.type },
    { label: '金额', key: (r) => formatPrice(r.refund_amount) },
    { label: '原因', key: 'reason' },
    { label: '申请时间', key: 'created_at' },
    { label: '状态', key: (r) => statusTagMap[r.status]?.label || r.status },
  ])
  ElMessage.success(`已导出 ${list.value.length} 条`)
}
</script>

<style lang="scss" scoped>
.order-refund-container {
  .link-text {
    color: var(--brand-500);
  }

  .order-src {
    color: var(--ink-500);
    font-size: 12px;
  }

  .refund-amt {
    color: var(--rose-500);
    font-weight: 600;
  }

  .reason-text {
    color: var(--ink-600);
    font-size: 12.5px;
  }

  .user-cell {
    display: flex;
    align-items: center;
    gap: 8px;

    &__avatar { flex-shrink: 0; }
    &__info { line-height: 1.3; min-width: 0; }
    &__name { font-size: 13px; color: var(--ink-900); }
    &__mobile { font-size: 11px; color: var(--ink-400); }
  }
}
</style>
