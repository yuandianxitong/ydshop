<template>
  <div class="order-invoice-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">发票管理</h2>
        <p class="page-desc">电子发票、增值税发票申请与开具</p>
      </div>
      <div class="page-actions">
        <el-button @click="handleExport">
          <i class="i-svg:download" /> 导出申请记录
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
        v-model="searchForm.order_no"
        placeholder="搜索发票号 / 订单号"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">类型：</span>
      <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 130px" @change="handleSearch">
        <el-option label="个人" value="personal" />
        <el-option label="公司" value="company" />
        <el-option label="增值税" value="vat" />
      </el-select>
      <span class="filter-label">状态：</span>
      <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 130px" @change="handleSearch">
        <el-option label="待开具" value="pending" />
        <el-option label="开票中" value="processing" />
        <el-option label="已开具" value="issued" />
        <el-option label="已作废" value="cancelled" />
      </el-select>
      <span class="filter-label">申请时间：</span>
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
      title="发票申请"
      storage-key="order-invoice-list"
      :columns="columns"
      :data="list"
      :loading="loading"
      :pagination="pagination"
      :show-column-config="false"
      @page-change="handlePageChange"
      @size-change="handleSizeChange"
    >
      <template #headerExtra>
        <el-button @click="handleExport">导出</el-button>
      </template>

      <template #invoice_no="{ row }">
        <span class="num link-text">IV-{{ String(row.id).padStart(6, '0') }}</span>
      </template>

      <template #order_no="{ row }">
        <span class="num order-src">{{ row.order_no }}</span>
      </template>

      <template #invoice_type="{ row }">
        <el-tag :type="row.invoice_type === 'electronic' ? 'primary' : 'warning'" size="small">{{ formatInvoiceType(row) }}</el-tag>
      </template>

      <template #title="{ row }">
        <div class="inv-title-name">{{ row.title || '—' }}</div>
        <div class="inv-title-sub">{{ typeMap[row.type] || row.type }}</div>
      </template>

      <template #tax_no="{ row }">
        <span class="num tax-no">{{ row.tax_no || '—' }}</span>
      </template>

      <template #amount="{ row }">
        <span class="num amount">¥{{ formatPrice(row.amount) }}</span>
      </template>

      <template #status="{ row }">
        <el-tag :type="statusTagMap[row.status]?.type" size="small">{{ statusTagMap[row.status]?.label || row.status }}</el-tag>
      </template>

      <template #action="{ row }">
        <el-button type="primary" size="small" text @click="handleDetail(row)">查看</el-button>
        <el-button v-if="row.status === 'pending'" v-has-perm="['order.invoice.update']" type="primary" size="small" text @click="handleProcess(row)">
          受理
        </el-button>
        <el-button v-if="row.status === 'pending' || row.status === 'processing'" v-has-perm="['order.invoice.update']" type="success" size="small" text @click="handleIssue(row)">
          开具
        </el-button>
        <el-link v-if="row.status === 'issued' && row.file_url" type="primary" :href="row.file_url" target="_blank" class="action-link">下载</el-link>
        <el-button v-if="row.status !== 'cancelled'" v-has-perm="['order.invoice.update']" type="warning" size="small" text @click="handleCancel(row)">
          作废
        </el-button>
      </template>
    </ProTable>

    <!-- 详情抽屉 -->
    <el-drawer v-model="detailVisible" title="发票申请详情" size="520px">
      <div v-if="currentInvoice" class="inv-detail">
        <div class="inv-section">
          <div class="inv-section-title">基础信息</div>
          <div class="inv-row"><span>状态</span><el-tag :type="statusTagMap[currentInvoice.status]?.type" size="small">{{ statusTagMap[currentInvoice.status]?.label }}</el-tag></div>
          <div class="inv-row"><span>订单号</span><span class="num">{{ currentInvoice.order_no }}</span></div>
          <div class="inv-row"><span>开票金额</span><span class="num inv-amount">¥{{ formatPrice(currentInvoice.amount) }}</span></div>
          <div class="inv-row"><span>申请时间</span><span class="num">{{ currentInvoice.created_at }}</span></div>
          <div v-if="currentInvoice.issued_at" class="inv-row"><span>开票时间</span><span class="num">{{ currentInvoice.issued_at }}</span></div>
        </div>

        <div class="inv-section">
          <div class="inv-section-title">抬头信息</div>
          <div class="inv-row"><span>抬头类型</span><span>{{ typeMap[currentInvoice.type] }}</span></div>
          <div class="inv-row"><span>发票形式</span><span>{{ currentInvoice.invoice_type === 'electronic' ? '电子发票' : '纸质发票' }}</span></div>
          <div class="inv-row"><span>抬头</span><span>{{ currentInvoice.title || '—' }}</span></div>
          <div v-if="currentInvoice.tax_no" class="inv-row"><span>税号</span><span class="num">{{ currentInvoice.tax_no }}</span></div>
          <div v-if="currentInvoice.bank_name" class="inv-row"><span>开户行</span><span>{{ currentInvoice.bank_name }}</span></div>
          <div v-if="currentInvoice.bank_account" class="inv-row"><span>银行账号</span><span class="num">{{ currentInvoice.bank_account }}</span></div>
          <div v-if="currentInvoice.company_address" class="inv-row"><span>注册地址</span><span>{{ currentInvoice.company_address }}</span></div>
          <div v-if="currentInvoice.company_phone" class="inv-row"><span>注册电话</span><span class="num">{{ currentInvoice.company_phone }}</span></div>
        </div>

        <div class="inv-section">
          <div class="inv-section-title">收件信息</div>
          <div v-if="currentInvoice.recipient_name" class="inv-row"><span>收件人</span><span>{{ currentInvoice.recipient_name }}</span></div>
          <div v-if="currentInvoice.recipient_phone" class="inv-row"><span>电话</span><span class="num">{{ currentInvoice.recipient_phone }}</span></div>
          <div v-if="currentInvoice.recipient_email" class="inv-row"><span>邮箱</span><span>{{ currentInvoice.recipient_email }}</span></div>
        </div>

        <div v-if="currentInvoice.content" class="inv-section">
          <div class="inv-section-title">发票内容</div>
          <div class="inv-content">{{ currentInvoice.content }}</div>
        </div>

        <div v-if="currentInvoice.admin_remark" class="inv-section">
          <div class="inv-section-title">备注</div>
          <div class="inv-content">{{ currentInvoice.admin_remark }}</div>
        </div>

        <div v-if="currentInvoice.file_url" class="inv-section">
          <div class="inv-section-title">开票文件</div>
          <el-link type="primary" :href="currentInvoice.file_url" target="_blank">下载查看</el-link>
        </div>
      </div>
    </el-drawer>

    <!-- 受理 -->
    <el-dialog v-model="processVisible" title="受理 — 进入开票中" width="480px">
      <el-form label-width="80px">
        <el-form-item label="备注">
          <el-input v-model="processForm.admin_remark" type="textarea" :rows="3" placeholder="可选" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="processVisible = false">取消</el-button>
        <el-button type="primary" :loading="processLoading" @click="confirmProcess">确认受理</el-button>
      </template>
    </el-dialog>

    <!-- 开票 -->
    <el-dialog v-model="issueVisible" title="完成开票" width="520px">
      <el-form ref="issueFormRef" :model="issueForm" :rules="issueRules" label-width="100px">
        <el-form-item label="开票文件" prop="file_url">
          <el-upload
            class="invoice-uploader"
            :action="uploadAction"
            :headers="uploadHeaders"
            accept=".pdf,.jpg,.jpeg,.png"
            :show-file-list="false"
            :before-upload="beforeInvoiceUpload"
            :on-success="handleInvoiceUploadSuccess"
            :on-error="handleInvoiceUploadError"
          >
            <el-button :loading="invoiceUploading" type="primary" plain>
              <i class="i-svg:upload" />
              {{ issueForm.file_url ? '重新上传' : '上传发票文件' }}
            </el-button>
          </el-upload>
          <div v-if="issueForm.file_url" class="invoice-file-line">
            <i class="i-svg:file-text" />
            <el-link type="primary" :href="issueForm.file_url" target="_blank">{{ invoiceFileName }}</el-link>
            <el-button type="danger" size="small" text @click="clearInvoiceFile">清除</el-button>
          </div>
          <div class="field-hint">支持 PDF / JPG / PNG，单个文件 ≤ 10MB</div>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="issueForm.admin_remark" type="textarea" :rows="3" placeholder="可选" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="issueVisible = false">取消</el-button>
        <el-button type="primary" :loading="issueLoading" @click="confirmIssue">确认开票</el-button>
      </template>
    </el-dialog>

    <!-- 作废 -->
    <el-dialog v-model="cancelVisible" title="作废发票" width="480px">
      <el-form ref="cancelFormRef" :model="cancelForm" :rules="cancelRules" label-width="80px">
        <el-form-item label="作废原因" prop="admin_remark">
          <el-input v-model="cancelForm.admin_remark" type="textarea" :rows="3" placeholder="请填写作废原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="cancelVisible = false">取消</el-button>
        <el-button type="warning" :loading="cancelLoading" @click="confirmCancel">确认作废</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="OrderInvoiceList">
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'

import { orderInvoiceApi, type OrderInvoiceInfo, type OrderInvoiceStats } from '@/api/order-invoice'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useListPage } from '@/hooks/useListPage'
import useUserStore from '@/store/modules/user.store'
import { exportCsv } from '@/utils/exportCsv'

interface InvoiceSearchForm {
  order_no: string
  type?: string
  status: string
  start_date?: string
  end_date?: string
}

const statusTagMap: Record<string, { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }> = {
  pending: { label: '待开具', type: 'warning' },
  processing: { label: '开票中', type: 'primary' },
  issued: { label: '已开具', type: 'success' },
  cancelled: { label: '已作废', type: 'info' },
}

const typeMap: Record<string, string> = {
  personal: '个人',
  company: '公司',
  vat: '增值税专用',
}

const formatInvoiceType = (row: OrderInvoiceInfo) => {
  if (row.type === 'vat') return '增值税专用发票'
  if (row.invoice_type === 'electronic') return '电子普通发票'
  return '纸质普通发票'
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
} = useListPage<OrderInvoiceInfo, InvoiceSearchForm>({
  fetchFn: (params) => orderInvoiceApi.getList(params),
  defaultSearchForm: {
    order_no: '',
    type: undefined,
    status: '',
    start_date: undefined,
    end_date: undefined,
  },
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

// 列定义（与设计稿对齐：发票号 / 原订单 / 类型 / 抬头 / 税号 / 金额 / 状态 / 操作）
const columns: ProColumn[] = [
  { key: 'invoice_no', label: '发票号', width: 140, required: true },
  { key: 'order_no', label: '原订单', width: 180 },
  { key: 'invoice_type', label: '类型', width: 150 },
  { key: 'title', label: '抬头', minWidth: 200 },
  { key: 'tax_no', label: '税号', width: 160 },
  { key: 'amount', label: '金额', width: 120, align: 'right' },
  { key: 'status', label: '状态', width: 110 },
  { key: 'action', label: '操作', width: 260, fixed: 'right', required: true },
]

// KPI（来自后端 stats）
const stats = reactive<OrderInvoiceStats>({ pending: 0, processing: 0, issued: 0, cancelled: 0, total: 0 })

const loadStats = async () => {
  try {
    const res = await orderInvoiceApi.getStats()
    Object.assign(stats, (res as any).data || {})
  } catch (e) {
    console.error(e)
  }
}

// 用当前页累加金额（设计稿中的"开票金额"）
const issuedAmount = computed(() => {
  return list.value
    .filter((r) => r.status === 'issued')
    .reduce((sum, r) => sum + Number(r.amount || 0), 0)
})

const vatCount = computed(() => list.value.filter((r) => r.type === 'vat').length)

const kpiCards = computed(() => [
  { label: '本月开票', value: formatCount(stats.issued),    suffix: '张',     tone: '' },
  { label: '待开具',   value: formatCount(stats.pending),   suffix: '需处理', tone: '' },
  { label: '专票',     value: formatCount(vatCount.value),  suffix: '当前页', tone: '' },
  { label: '开票金额', value: '¥ ' + formatPrice(issuedAmount.value), suffix: '当前页', tone: '' },
])

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')
const formatPrice = (n: number) => (n == null ? '0.00' : Number(n).toFixed(2))

// 详情
const detailVisible = ref(false)
const currentInvoice = ref<OrderInvoiceInfo | null>(null)

const handleDetail = (row: OrderInvoiceInfo) => {
  currentInvoice.value = row
  detailVisible.value = true
}

// 受理
const processVisible = ref(false)
const processLoading = ref(false)
const currentId = ref(0)
const processForm = reactive({ admin_remark: '' })

const handleProcess = (row: OrderInvoiceInfo) => {
  currentId.value = row.id
  processForm.admin_remark = ''
  processVisible.value = true
}

const confirmProcess = async () => {
  processLoading.value = true
  try {
    await orderInvoiceApi.process(currentId.value, processForm)
    ElMessage.success('已受理')
    processVisible.value = false
    await Promise.all([getList(), loadStats()])
  } catch (e) {
    console.error(e)
  } finally {
    processLoading.value = false
  }
}

// 开票
const issueVisible = ref(false)
const issueLoading = ref(false)
const issueFormRef = ref()
const issueForm = reactive({ file_url: '', admin_remark: '' })
const issueRules = {
  file_url: [{ required: true, message: '请上传开票文件', trigger: 'change' }],
}

// 文件上传
const userStore = useUserStore()
const uploadAction = `${import.meta.env.VITE_APP_API_URL || ''}/adminapi/upload/file`
const uploadHeaders = computed(() => ({
  Authorization: `Bearer ${userStore.getToken()}`,
}))
const invoiceUploading = ref(false)
const invoiceFileName = computed(() => {
  if (!issueForm.file_url) return ''
  const parts = issueForm.file_url.split('/')
  return decodeURIComponent(parts[parts.length - 1] || issueForm.file_url)
})

const beforeInvoiceUpload = (file: File) => {
  const okType = ['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)
  if (!okType) {
    ElMessage.error('仅支持 PDF / JPG / PNG 格式')
    return false
  }
  if (file.size > 10 * 1024 * 1024) {
    ElMessage.error('文件不能超过 10MB')
    return false
  }
  invoiceUploading.value = true
  return true
}
const handleInvoiceUploadSuccess = (response: any) => {
  invoiceUploading.value = false
  if (response?.code === 200 || response?.code === 0) {
    issueForm.file_url = response.data?.url || response.data?.file_url || response.data || ''
    if (!issueForm.file_url) {
      ElMessage.error('上传成功但未获取到文件地址')
      return
    }
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response?.message || '上传失败')
  }
}
const handleInvoiceUploadError = () => {
  invoiceUploading.value = false
  ElMessage.error('上传失败，请重试')
}
const clearInvoiceFile = () => {
  issueForm.file_url = ''
}

const handleIssue = (row: OrderInvoiceInfo) => {
  currentId.value = row.id
  issueForm.file_url = ''
  issueForm.admin_remark = ''
  issueVisible.value = true
}

const confirmIssue = async () => {
  if (!issueFormRef.value) return
  await issueFormRef.value.validate()
  issueLoading.value = true
  try {
    await orderInvoiceApi.issue(currentId.value, issueForm)
    ElMessage.success('开票完成')
    issueVisible.value = false
    await Promise.all([getList(), loadStats()])
  } catch (e) {
    console.error(e)
  } finally {
    issueLoading.value = false
  }
}

// 作废
const cancelVisible = ref(false)
const cancelLoading = ref(false)
const cancelFormRef = ref()
const cancelForm = reactive({ admin_remark: '' })
const cancelRules = {
  admin_remark: [{ required: true, message: '请填写作废原因', trigger: 'blur' }],
}

const handleCancel = (row: OrderInvoiceInfo) => {
  currentId.value = row.id
  cancelForm.admin_remark = ''
  cancelVisible.value = true
}

const confirmCancel = async () => {
  if (!cancelFormRef.value) return
  await cancelFormRef.value.validate()
  cancelLoading.value = true
  try {
    await orderInvoiceApi.cancel(currentId.value, cancelForm)
    ElMessage.success('已作废')
    cancelVisible.value = false
    await Promise.all([getList(), loadStats()])
  } catch (e) {
    console.error(e)
  } finally {
    cancelLoading.value = false
  }
}

// 导出
const handleExport = () => {
  if (!list.value.length) {
    ElMessage.warning('暂无可导出数据')
    return
  }
  const today = new Date().toISOString().slice(0, 10)
  exportCsv(`发票申请_${today}.csv`, list.value, [
    { label: '发票号', key: (r) => 'IV-' + String(r.id).padStart(6, '0') },
    { label: '原订单', key: 'order_no' },
    { label: '类型', key: (r) => formatInvoiceType(r) },
    { label: '抬头', key: 'title' },
    { label: '税号', key: 'tax_no' },
    { label: '金额', key: (r) => formatPrice(r.amount) },
    { label: '状态', key: (r) => statusTagMap[r.status]?.label || r.status },
    { label: '申请时间', key: 'created_at' },
  ])
  ElMessage.success(`已导出 ${list.value.length} 条`)
}

onMounted(() => {
  loadStats()
})
</script>

<style lang="scss" scoped>
.order-invoice-container {
  .link-text {
    color: var(--brand-500);
    font-weight: 500;
  }

  .order-src {
    color: var(--ink-500);
    font-size: 12px;
  }

  .inv-title-name {
    font-weight: 500;
    color: var(--ink-900);
  }

  .inv-title-sub {
    font-size: 11px;
    color: var(--ink-400);
    margin-top: 2px;
  }

  .tax-no {
    font-size: 11.5px;
    color: var(--ink-500);
  }

  .amount {
    font-weight: 600;
    color: var(--ink-900);
  }

  .action-link {
    margin: 0 4px;
    font-size: 13px;
  }
}

.field-hint {
  margin-top: 4px;
  font-size: 12px;
  color: var(--ink-500);
}

.invoice-uploader {
  display: inline-block;
}

.invoice-file-line {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 6px 10px;
  background: var(--ink-50);
  border-radius: 6px;
  font-size: 12.5px;

  i { color: var(--brand-500); }
}

/* 详情抽屉 */
.inv-detail {
  padding: 4px 4px 24px;
}

.inv-section {
  margin-bottom: 18px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--ink-100);

  &:last-child {
    border-bottom: 0;
    margin-bottom: 0;
  }
}

.inv-section-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-900);
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;

  &::before {
    content: '';
    width: 3px;
    height: 13px;
    border-radius: 2px;
    background: var(--brand-500);
  }
}

.inv-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 13px;

  > span:first-child {
    color: var(--ink-500);
    flex-shrink: 0;
    margin-right: 12px;
  }
}

.inv-amount {
  color: var(--rose-500);
  font-weight: 600;
}

.inv-content {
  font-size: 13px;
  color: var(--ink-700);
  line-height: 1.7;
  padding: 10px 12px;
  background: var(--ink-50);
  border-radius: 6px;
}
</style>
