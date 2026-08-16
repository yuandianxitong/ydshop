<template>
  <div class="account-fund-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">账户资金</h2>
        <p class="page-desc">余额、预存款、充值与提现管理</p>
      </div>
      <div class="page-actions">
        <el-button :loading="exportingFund" @click="handleExport"><i class="i-svg:download" /> 导出对账单</el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">账户余额总额</div>
        <div class="nm num">¥ {{ formatPrice(stats.balance_total) }}</div>
        <div class="tr"><span>所有会员合计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本月充值</div>
        <div class="nm num">¥ {{ formatPrice(stats.recharge_month) }}</div>
        <div class="tr"><span>已支付</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本月提现</div>
        <div class="nm num">¥ {{ formatPrice(stats.withdraw_month) }}</div>
        <div class="tr"><span>已打款</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">待审核提现</div>
        <div class="nm num">{{ stats.pending_count }}</div>
        <div class="tr"><span class="text-warning">需处理</span></div>
      </div>
    </div>

    <!-- 单行过滤栏：分段切换 + 搜索 -->
    <div class="filter-bar fund-filter">
      <div class="seg">
        <button :class="{ on: tab === 'bal' }" @click="switchTab('bal')">余额变动</button>
        <button :class="{ on: tab === 'rech' }" @click="switchTab('rech')">充值记录</button>
        <button v-if="hasDistribution" :class="{ on: tab === 'wd' }" @click="switchTab('wd')">提现申请</button>
      </div>
      <span class="filter-sp" />
      <el-input v-model="keyword" placeholder="搜索会员" clearable style="width: 220px" @keyup.enter="reload" />
      <el-button @click="reload">查询</el-button>
    </div>

    <!-- 余额变动 -->
    <ProTable
      v-if="tab === 'bal'"
      title="余额变动"
      storage-key="fund-balance-logs"
      :columns="balColumns"
      :data="balanceLogs"
      :loading="balLoading"
      :pagination="balPagination"
      :show-column-config="false"
      :show-batch-delete="false"
      @page-change="(p) => handlePageChange('bal', p)"
      @size-change="(s) => handleSizeChange('bal', s)"
    >
      <template #user_nickname="{ row }">
        <div class="user-cell">
          <el-avatar :size="28" :src="row.user_avatar || ''" class="user-cell__avatar">
            {{ (row.user_nickname || row.user_mobile || '?')[0] }}
          </el-avatar>
          <div class="user-cell__info">
            <div class="user-cell__name">{{ row.user_nickname || '—' }}</div>
            <div v-if="row.user_mobile" class="user-cell__mobile num">{{ row.user_mobile }}</div>
          </div>
        </div>
      </template>
      <template #type_text="{ row }">
        <el-tag :type="balTagType(row.type)" size="small">{{ row.type_text }}</el-tag>
      </template>
      <template #amount="{ row }">
        <span class="num" :class="row.amount >= 0 ? 'amt-up' : 'amt-dn'">
          {{ row.amount >= 0 ? '+' : '' }}¥ {{ formatPrice(Math.abs(Number(row.amount))) }}
        </span>
      </template>
      <template #after_balance="{ row }">
        <span class="num">¥ {{ formatPrice(row.after_balance) }}</span>
      </template>
      <template #remark="{ row }">{{ row.remark || row.source || '—' }}</template>
      <template #created_at="{ row }"><span class="num">{{ row.created_at }}</span></template>
    </ProTable>

    <!-- 充值记录 -->
    <ProTable
      v-else-if="tab === 'rech'"
      title="充值记录"
      storage-key="fund-recharge-orders"
      :columns="rechColumns"
      :data="rechargeOrders"
      :loading="rechLoading"
      :pagination="rechPagination"
      :show-column-config="false"
      :show-batch-delete="false"
      @page-change="(p) => handlePageChange('rech', p)"
      @size-change="(s) => handleSizeChange('rech', s)"
    >
      <template #user_nickname="{ row }">
        <div class="user-cell">
          <el-avatar :size="28" :src="row.user_avatar || ''" class="user-cell__avatar">
            {{ (row.user_nickname || row.user_mobile || '?')[0] }}
          </el-avatar>
          <div class="user-cell__info">
            <div class="user-cell__name">{{ row.user_nickname || '—' }}</div>
            <div v-if="row.user_mobile" class="user-cell__mobile num">{{ row.user_mobile }}</div>
          </div>
        </div>
      </template>
      <template #amount="{ row }">
        <span class="num amt-up">+¥ {{ formatPrice(row.amount) }}</span>
      </template>
      <template #pay_type="{ row }">{{ payTypeMap[row.pay_type] || row.pay_type || '—' }}</template>
      <template #status="{ row }">
        <el-tag :type="row.status === 1 ? 'success' : 'warning'" size="small">
          {{ row.status === 1 ? '已到账' : '待支付' }}
        </el-tag>
      </template>
      <template #paid_at="{ row }"><span class="num">{{ row.paid_at || row.created_at }}</span></template>
    </ProTable>

    <!-- 提现申请 -->
    <ProTable
      v-else
      title="提现申请"
      storage-key="fund-withdrawals"
      :columns="wdColumns"
      :data="withdrawals"
      :loading="wdLoading"
      :pagination="wdPagination"
      :show-column-config="false"
      :show-batch-delete="false"
      @page-change="(p) => handlePageChange('wd', p)"
      @size-change="(s) => handleSizeChange('wd', s)"
    >
      <template #user_nickname="{ row }">
        <div class="user-cell">
          <el-avatar :size="28" :src="row.user_avatar || ''" class="user-cell__avatar">
            {{ (row.user_nickname || row.user_mobile || '?')[0] }}
          </el-avatar>
          <div class="user-cell__info">
            <div class="user-cell__name">{{ row.user_nickname || '—' }}</div>
            <div v-if="row.user_mobile" class="user-cell__mobile num">{{ row.user_mobile }}</div>
          </div>
        </div>
      </template>
      <template #amount="{ row }">
        <span class="num amt-dn">¥ {{ formatPrice(row.amount) }}</span>
      </template>
      <template #type="{ row }">{{ wdTypeMap[row.type] || row.type }}</template>
      <template #status="{ row }">
        <div class="flex flex-col items-start gap-1">
          <el-tag :type="wdStatusTag[row.status]?.type" size="small">{{ wdStatusTag[row.status]?.label }}</el-tag>
          <el-tooltip v-if="isLedgerReviewPending(row)" :content="row.ledger_review_reason || '提现资金流水待人工复核'">
            <el-tag type="danger" size="small" effect="dark">资金待复核</el-tag>
          </el-tooltip>
        </div>
      </template>
      <template #created_at="{ row }"><span class="num">{{ row.created_at }}</span></template>
      <template #action="{ row }">
        <el-button
          v-if="isLedgerReviewPending(row)"
          v-has-perm="['distribution.commission.review']"
          type="danger"
          size="small"
          text
          @click="openWithdrawalLedgerReview(row)"
        >
          复核资金
        </el-button>
        <template v-else-if="row.status === 'pending'">
          <el-button v-has-perm="['member.fund.withdraw.approve']" type="primary" size="small" text @click="handleApprove(row)">通过</el-button>
          <el-button v-has-perm="['member.fund.withdraw.approve']" type="danger" size="small" text @click="handleReject(row)">拒绝</el-button>
        </template>
        <el-button v-if="row.status === 'approved'" v-has-perm="['member.fund.withdraw.pay']" type="success" size="small" text @click="handlePay(row)">打款</el-button>
        <span v-if="['paid', 'rejected'].includes(row.status)" class="text-ink-400">—</span>
      </template>
    </ProTable>

    <!-- 拒绝提现弹窗 -->
    <el-dialog v-model="rejectVisible" title="拒绝提现" width="460px">
      <el-form ref="rejectFormRef" :model="rejectForm" :rules="rejectRules" label-width="80px">
        <el-form-item label="拒绝原因" prop="remark">
          <el-input v-model="rejectForm.remark" type="textarea" :rows="3" placeholder="请填写拒绝原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectVisible = false">取消</el-button>
        <el-button type="danger" :loading="actionLoading" @click="confirmReject">确认拒绝</el-button>
      </template>
    </el-dialog>
    <WithdrawalLedgerReviewDialog
      v-model="withdrawalLedgerReviewVisible"
      :row="withdrawalLedgerReviewRow"
      @resolved="handleWithdrawalLedgerResolved"
    />
  </div>
</template>

<script setup lang="ts" name="AccountFundCenter">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, defineAsyncComponent, onMounted, reactive, ref, type Component } from 'vue'

import { accountFundApi, type BalanceLogInfo, type FundStats, type RechargeOrderInfo, type WithdrawalInfo } from '@/api/account-fund'
import ProTable from '@/components/ProTable/index.vue'
import type { ProColumn } from '@/components/ProTable/types'
import { useExport } from '@/composables/useExport'
import { useUserStore } from '@/store/modules/user.store'

const withdrawalReviewModules = import.meta.glob<{ default: Component }>(
  '../../distribution/commission/components/WithdrawalLedgerReviewDialog.vue'
)
const WithdrawalLedgerReviewDialog = defineAsyncComponent(async () => {
  const loader = Object.values(withdrawalReviewModules)[0]
  if (!loader) {
    return { render: () => null }
  }
  return loader()
})

const userStore = useUserStore()
const hasDistribution = computed(() => userStore.hasPermission('distribution.withdrawal.list'))

const tab = ref<'bal' | 'rech' | 'wd'>('bal')
const keyword = ref('')

// KPI
const stats = reactive<FundStats>({ balance_total: 0, recharge_month: 0, withdraw_month: 0, pending_count: 0 })
const loadStats = async () => {
  try {
    const res = await accountFundApi.getStats()
    Object.assign(stats, (res as any).data || {})
  } catch (e) { console.error(e) }
}

// 余额变动
const balanceLogs = ref<BalanceLogInfo[]>([])
const balLoading = ref(false)
const balPagination = reactive({ page: 1, limit: 20, total: 0 })
const balColumns: ProColumn[] = [
  { key: 'user_nickname', label: '会员', width: 200, required: true },
  { key: 'type_text', label: '类型', width: 120 },
  { key: 'amount', label: '金额', width: 140 },
  { key: 'remark', label: '说明', minWidth: 200 },
  { key: 'created_at', label: '时间', width: 200 },
  { key: 'after_balance', label: '余额', width: 130 },
]
const balTagType = (t: number): 'success' | 'primary' | 'info' | 'warning' => {
  if (t === 1) return 'success'   // 充值
  if (t === 3) return 'primary'   // 退款
  if (t === 4) return 'warning'   // 后台调整
  return 'info'                   // 消费/其他
}

// 充值记录
const rechargeOrders = ref<RechargeOrderInfo[]>([])
const rechLoading = ref(false)
const rechPagination = reactive({ page: 1, limit: 20, total: 0 })
const rechColumns: ProColumn[] = [
  { key: 'user_nickname', label: '会员', width: 200, required: true },
  { key: 'amount', label: '充值金额', width: 140 },
  { key: 'pay_type', label: '支付方式', width: 110 },
  { key: 'paid_at', label: '时间', width: 170 },
  { key: 'status', label: '状态', width: 100 },
]
const payTypeMap: Record<string, string> = {
  wechat: '微信', alipay: '支付宝', balance: '余额', bank: '银行卡',
}

// 提现申请
const withdrawals = ref<WithdrawalInfo[]>([])
const wdLoading = ref(false)
const wdPagination = reactive({ page: 1, limit: 20, total: 0 })
const wdColumns: ProColumn[] = [
  { key: 'user_nickname', label: '会员', width: 200, required: true },
  { key: 'amount', label: '提现金额', width: 140 },
  { key: 'type', label: '提现方式', width: 110 },
  { key: 'created_at', label: '申请时间', width: 170 },
  { key: 'status', label: '状态', width: 100 },
  { key: 'action', label: '操作', width: 160, fixed: 'right', required: true },
]
const wdTypeMap: Record<string, string> = { wechat: '微信', alipay: '支付宝', bank: '银行卡' }
const wdStatusTag: Record<string, { label: string; type: 'primary' | 'success' | 'warning' | 'info' | 'danger' }> = {
  pending:  { label: '待审核', type: 'warning' },
  approved: { label: '已审核', type: 'primary' },
  paid:     { label: '已打款', type: 'success' },
  rejected: { label: '已拒绝', type: 'danger' },
}

const formatPrice = (n: number | string) => (n == null ? '0.00' : Number(n).toFixed(2))
const isLedgerReviewPending = (row: WithdrawalInfo) => row.ledger_review_status === 'pending'
const withdrawalLedgerReviewVisible = ref(false)
const withdrawalLedgerReviewRow = ref<WithdrawalInfo | null>(null)
const openWithdrawalLedgerReview = (row: WithdrawalInfo) => {
  withdrawalLedgerReviewRow.value = row
  withdrawalLedgerReviewVisible.value = true
}
const handleWithdrawalLedgerResolved = async () => {
  await Promise.all([fetchWd(), loadStats()])
}

const fetchCurrent = async () => {
  if (tab.value === 'bal') return fetchBal()
  if (tab.value === 'rech') return fetchRech()
  return fetchWd()
}

const fetchBal = async () => {
  balLoading.value = true
  try {
    const res = await accountFundApi.getBalanceLogs({
      page: balPagination.page,
      limit: balPagination.limit,
      keyword: keyword.value || undefined,
    })
    balanceLogs.value = (res as any).data?.list || []
    balPagination.total = (res as any).data?.pagination?.total || 0
  } catch (e) { console.error(e) } finally { balLoading.value = false }
}

const fetchRech = async () => {
  rechLoading.value = true
  try {
    const res = await accountFundApi.getRechargeOrders({
      page: rechPagination.page,
      limit: rechPagination.limit,
      keyword: keyword.value || undefined,
    })
    rechargeOrders.value = (res as any).data?.list || []
    rechPagination.total = (res as any).data?.pagination?.total || 0
  } catch (e) { console.error(e) } finally { rechLoading.value = false }
}

const fetchWd = async () => {
  wdLoading.value = true
  try {
    const res = await accountFundApi.getWithdrawals({
      page: wdPagination.page,
      limit: wdPagination.limit,
      keyword: keyword.value || undefined,
    })
    withdrawals.value = (res as any).data?.list || []
    wdPagination.total = (res as any).data?.pagination?.total || 0
  } catch (e) { console.error(e) } finally { wdLoading.value = false }
}

const switchTab = (t: 'bal' | 'rech' | 'wd') => {
  tab.value = t
  if (t === 'bal') balPagination.page = 1
  else if (t === 'rech') rechPagination.page = 1
  else wdPagination.page = 1
  fetchCurrent()
}
const reload = () => fetchCurrent()

const handlePageChange = (which: 'bal' | 'rech' | 'wd', p: number) => {
  if (which === 'bal') balPagination.page = p
  if (which === 'rech') rechPagination.page = p
  if (which === 'wd') wdPagination.page = p
  fetchCurrent()
}
const handleSizeChange = (which: 'bal' | 'rech' | 'wd', s: number) => {
  if (which === 'bal') { balPagination.limit = s; balPagination.page = 1 }
  if (which === 'rech') { rechPagination.limit = s; rechPagination.page = 1 }
  if (which === 'wd') { wdPagination.limit = s; wdPagination.page = 1 }
  fetchCurrent()
}

// 提现操作
const actionLoading = ref(false)
const handleApprove = async (row: WithdrawalInfo) => {
  if (isLedgerReviewPending(row)) {
    ElMessage.warning('该提现资金流水待人工复核，请先到“佣金记录”处理')
    return
  }
  try {
    await ElMessageBox.confirm(`确认通过该提现申请（¥${formatPrice(row.amount)}）？`, '审核确认', { type: 'warning' })
    await accountFundApi.approveWithdrawal(row.id)
    ElMessage.success('已通过')
    await Promise.all([fetchWd(), loadStats()])
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

const rejectVisible = ref(false)
const rejectFormRef = ref()
const rejectForm = reactive({ id: 0, remark: '' })
const rejectRules = { remark: [{ required: true, message: '请填写拒绝原因', trigger: 'blur' }] }
const handleReject = (row: WithdrawalInfo) => {
  if (isLedgerReviewPending(row)) {
    ElMessage.warning('该提现资金流水待人工复核，请先到“佣金记录”处理')
    return
  }
  rejectForm.id = row.id
  rejectForm.remark = ''
  rejectVisible.value = true
}
const confirmReject = async () => {
  if (!rejectFormRef.value) return
  await rejectFormRef.value.validate()
  actionLoading.value = true
  try {
    await accountFundApi.rejectWithdrawal(rejectForm.id, rejectForm.remark)
    ElMessage.success('已拒绝')
    rejectVisible.value = false
    await Promise.all([fetchWd(), loadStats()])
  } finally { actionLoading.value = false }
}

const handlePay = async (row: WithdrawalInfo) => {
  if (isLedgerReviewPending(row)) {
    ElMessage.warning('该提现资金流水待人工复核，请先到“佣金记录”处理')
    return
  }
  try {
    const { value } = await ElMessageBox.prompt(
      `请确认已在线下打款 ¥${formatPrice(row.actual_amount)}，并填写支付平台流水号或银行回单号。`,
      '登记线下打款',
      {
        confirmButtonText: '确认登记',
        cancelButtonText: '取消',
        inputPlaceholder: '请输入外部打款流水号',
        inputValidator: (input) => {
          const reference = input.trim()
          if (!reference) return '请输入外部打款流水号'
          return reference.length <= 100 || '外部打款流水号不能超过 100 个字符'
        },
      }
    )
    await accountFundApi.payWithdrawal(row.id, { payout_reference: value.trim() })
    ElMessage.success('线下打款结果已登记')
    await Promise.all([fetchWd(), loadStats()])
  } catch (e) {
    if (e !== 'cancel') console.error('线下打款登记失败:', e)
  }
}

const { exporting: exportingFund, doExport: doExportFund } = useExport()
const handleExport = () => {
  const params: Record<string, any> = { tab: tab.value }
  if (keyword.value) params.keyword = keyword.value
  doExportFund('/adminapi/member/fund/export', params, '账户对账单')
}

onMounted(() => {
  loadStats()
  fetchBal()
})
</script>

<style lang="scss" scoped>
.account-fund-container {
  .fund-filter {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .seg {
    display: inline-flex;
    background: var(--ink-50, #f4f6f9);
    padding: 3px;
    border-radius: 8px;
    button {
      border: none;
      background: transparent;
      padding: 6px 14px;
      font-size: 13px;
      color: var(--ink-600, #5b6577);
      border-radius: 6px;
      cursor: pointer;
      transition: all .15s ease;
      &.on {
        background: #fff;
        color: var(--brand-600, #2563eb);
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
        font-weight: 600;
      }
      &:hover:not(.on) { color: var(--brand-500, #3b82f6); }
    }
  }
  .amt-up { color: var(--el-color-success); font-weight: 600; }
  .amt-dn { color: var(--el-color-danger); font-weight: 600; }
  .text-warning { color: var(--el-color-warning); }

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
