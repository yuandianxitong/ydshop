<script setup lang="ts" name="FinanceWithdrawal">
import type { FormInstance } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, defineAsyncComponent, onMounted, reactive, ref } from 'vue'

import { distributionApi } from '@/api/distribution'
import { batchUpdateConfigs, getConfigsByGroup } from '@/api/system/config'
import { useExport } from '@/composables/useExport'
import { useUserStore } from '@/store/modules/user.store'

const withdrawalReviewModules = import.meta.glob(
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

const statusMap: Record<string, { label: string; tone: string }> = {
  pending: { label: '待审核', tone: 'amber' },
  approved: { label: '已审核', tone: 'blue' },
  paid: { label: '已到账', tone: 'green' },
  rejected: { label: '已拒绝', tone: 'rose' },
}

const typeMap: Record<string, { label: string; tone: string }> = {
  wechat: { label: '微信', tone: 'green' },
  alipay: { label: '支付宝', tone: 'blue' },
  bank: { label: '银行卡', tone: 'gray' },
}

// 当前 tab：待审核 / 已处理（已审核+已打款+已拒绝） / 提现规则
const tab = ref<'pending' | 'done' | 'rule'>('pending')

const tableData = ref<Record<string, any>[]>([])
const loading = ref(false)

const pagination = reactive({ page: 1, limit: 12, total: 0 })

const fmt = (v: any) => Number(v ?? 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtCount = (v: any) => Number(v ?? 0).toLocaleString('zh-CN')
const isLedgerReviewPending = (row: Record<string, any>) => row.ledger_review_status === 'pending'
const withdrawalLedgerReviewVisible = ref(false)
const withdrawalLedgerReviewRow = ref<Record<string, any> | null>(null)
const openWithdrawalLedgerReview = (row: Record<string, any>) => {
  withdrawalLedgerReviewRow.value = row
  withdrawalLedgerReviewVisible.value = true
}

// KPI（基于当前数据 + 总数）
const stats = computed(() => {
  const counters = { pending: 0, paid: 0, rejected: 0 }
  let pendingAmt = 0, paidAmt = 0
  for (const row of tableData.value) {
    const s = row.status as keyof typeof counters
    if (s in counters) counters[s] += 1
    const a = Number(row.amount ?? 0)
    if (row.status === 'pending') pendingAmt += a
    if (row.status === 'paid') paidAmt += a
  }
  return { ...counters, pendingAmt, paidAmt }
})

const fetchList = async () => {
  if (tab.value === 'rule' || !hasDistribution.value) return
  try {
    loading.value = true
    const params: Record<string, any> = { page: pagination.page, limit: pagination.limit }
    if (tab.value === 'pending') params.status = 'pending'
    if (tab.value === 'done') params.status_in = 'approved,paid,rejected'
    if (keyword.value) params.keyword = keyword.value
    const response = await distributionApi.getWithdrawalList(params)
    tableData.value = response.data.list || []
    pagination.total = response.data.pagination?.total || 0
  } finally {
    loading.value = false
  }
}

const keyword = ref('')

const switchTab = (t: 'pending' | 'done' | 'rule') => {
  tab.value = t
  pagination.page = 1
  if (t !== 'rule') fetchList()
}

const handleSearch = () => {
  pagination.page = 1
  fetchList()
}

// 先收集外部流水号，再审核并登记线下打款结果。
const handleApproveAndPay = async (row: Record<string, any>) => {
  if (isLedgerReviewPending(row)) {
    ElMessage.warning('该提现资金流水待人工复核，请先到“佣金记录”处理')
    return
  }
  try {
    const { value } = await ElMessageBox.prompt(
      `请确认已在线下打款 ¥${fmt(row.actual_amount)}，并填写支付平台流水号或银行回单号。`,
      '审核并登记线下打款',
      {
        confirmButtonText: '确认审核并登记',
        cancelButtonText: '取消',
        inputPlaceholder: '请输入外部打款流水号',
        inputValidator: (input) => {
          const reference = input.trim()
          if (!reference) return '请输入外部打款流水号'
          return reference.length <= 100 || '外部打款流水号不能超过 100 个字符'
        },
      }
    )
    await distributionApi.approveWithdrawal(row.id)
    await distributionApi.payWithdrawal(row.id, { payout_reference: value.trim() })
    ElMessage.success('已审核，线下打款结果已登记')
    fetchList()
  } catch (error) {
    if (error !== 'cancel') console.error('操作失败:', error)
  }
}

// ---------- 拒绝 ----------
const rejectDialogVisible = ref(false)
const rejectSubmitLoading = ref(false)
const rejectFormRef = ref<FormInstance>()
let currentWithdrawalId = 0

const rejectForm = reactive({ remark: '' })
const rejectRules = {
  remark: [{ required: true, message: '请输入拒绝原因', trigger: 'blur' }],
}

const handleReject = (row: Record<string, any>) => {
  if (isLedgerReviewPending(row)) {
    ElMessage.warning('该提现资金流水待人工复核，请先到“佣金记录”处理')
    return
  }
  currentWithdrawalId = row.id
  rejectForm.remark = ''
  rejectDialogVisible.value = true
}

const handleRejectSubmit = async () => {
  if (!rejectFormRef.value) return
  try {
    await rejectFormRef.value.validate()
  } catch {
    return
  }
  try {
    rejectSubmitLoading.value = true
    await distributionApi.rejectWithdrawal(currentWithdrawalId, rejectForm)
    ElMessage.success('已拒绝提现申请')
    rejectDialogVisible.value = false
    fetchList()
  } finally {
    rejectSubmitLoading.value = false
  }
}

const handleRejectDialogClose = () => {
  rejectFormRef.value?.resetFields()
}

// ---------- 提现规则（system_configs withdrawal 分组） ----------
const ruleForm = reactive({
  withdrawal_min_amount: 50,
  withdrawal_max_amount: 50000,
  withdrawal_daily_count: 3,
  withdrawal_fee_rate: 0.6,
  withdrawal_fee_min: 1,
  withdrawal_fee_max: 25,
  withdrawal_channels: ['wechat', 'alipay', 'bank'] as string[],
})
const ruleLoading = ref(false)
const ruleSaving = ref(false)
const channelDefs: Array<{ key: string; label: string }> = [
  { key: 'wechat', label: '微信' },
  { key: 'alipay', label: '支付宝' },
  { key: 'bank',   label: '银行卡' },
]

const fetchRule = async () => {
  try {
    ruleLoading.value = true
    const res = await getConfigsByGroup('withdrawal')
    const list = res.data || []
    for (const item of list) {
      const k = item.config_key as keyof typeof ruleForm
      if (k in ruleForm) {
        if (k === 'withdrawal_channels') {
          ruleForm.withdrawal_channels = Array.isArray(item.config_value)
            ? item.config_value
            : String(item.config_value || '').split(',').filter(Boolean)
        } else {
          ;(ruleForm as any)[k] = Number(item.config_value) || 0
        }
      }
    }
  } catch {
    // 后端尚未配置 withdrawal 分组时使用默认值
  } finally {
    ruleLoading.value = false
  }
}

const toggleChannel = (k: string) => {
  const idx = ruleForm.withdrawal_channels.indexOf(k)
  if (idx >= 0) ruleForm.withdrawal_channels.splice(idx, 1)
  else ruleForm.withdrawal_channels.push(k)
}

const isChannelOn = (k: string) => ruleForm.withdrawal_channels.includes(k)

const saveRule = async () => {
  try {
    ruleSaving.value = true
    await batchUpdateConfigs([
      { config_key: 'withdrawal_min_amount',  config_value: ruleForm.withdrawal_min_amount },
      { config_key: 'withdrawal_max_amount',  config_value: ruleForm.withdrawal_max_amount },
      { config_key: 'withdrawal_daily_count', config_value: ruleForm.withdrawal_daily_count },
      { config_key: 'withdrawal_fee_rate',    config_value: ruleForm.withdrawal_fee_rate },
      { config_key: 'withdrawal_fee_min',     config_value: ruleForm.withdrawal_fee_min },
      { config_key: 'withdrawal_fee_max',     config_value: ruleForm.withdrawal_fee_max },
      { config_key: 'withdrawal_channels',    config_value: ruleForm.withdrawal_channels.join(',') },
    ])
    ElMessage.success('提现规则已保存')
  } finally {
    ruleSaving.value = false
  }
}

const { exporting: exportingWd, doExport: doExportWd } = useExport()
const handleExport = () => {
    const params: Record<string, any> = {}
    if (tab.value === 'pending') params.status = 'pending'
    if (tab.value === 'done') params.status_in = 'approved,paid,rejected'
    if (keyword.value) params.keyword = keyword.value
    doExportWd('/adminapi/distribution/withdrawal/export', params, '提现单')
}

onMounted(() => {
  if (!hasDistribution.value) {
    tab.value = 'rule'
  } else {
    fetchList()
  }
  fetchRule()
})
</script>

<template>
  <div class="withdrawal-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">提现管理</h2>
        <p class="page-desc">审核、打款与异常处理</p>
      </div>
      <div class="page-actions">
        <el-button @click="switchTab('rule')">提现规则</el-button>
        <el-button v-if="hasDistribution" :loading="exportingWd" @click="handleExport">导出</el-button>
      </div>
    </div>

    <!-- KPI -->
    <div v-if="hasDistribution" class="row-14">
      <div class="kpi-mini">
        <div class="lb">待审核</div>
        <div class="nm num">{{ fmtCount(stats.pending) }} 笔</div>
        <div class="tr"><span style="color:var(--amber-500)">¥ {{ fmt(stats.pendingAmt) }}</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">已到账</div>
        <div class="nm num">{{ fmtCount(stats.paid) }} 笔</div>
        <div class="tr"><span style="color:var(--success)">¥ {{ fmt(stats.paidAmt) }}</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">已拒绝</div>
        <div class="nm num">{{ fmtCount(stats.rejected) }} 笔</div>
        <div class="tr"><span style="color:var(--rose-500)">本页统计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">提现总数</div>
        <div class="nm num">{{ fmtCount(pagination.total) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">分页统计</span></div>
      </div>
    </div>

    <!-- 切换 tab + 搜索 -->
    <div class="filter-bar wd-filter">
      <div class="seg wd-seg">
        <button v-if="hasDistribution" :class="{ on: tab === 'pending' }" @click="switchTab('pending')">待审核</button>
        <button v-if="hasDistribution" :class="{ on: tab === 'done' }" @click="switchTab('done')">已处理</button>
        <button :class="{ on: tab === 'rule' }" @click="switchTab('rule')">提现规则</button>
      </div>
      <span class="filter-sp" />
      <el-input
        v-if="tab !== 'rule'"
        v-model="keyword"
        placeholder="搜索单号 / 会员"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <el-button v-if="tab !== 'rule'" type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 待审核：卡片网格 -->
    <div v-if="tab === 'pending'" class="row-22 wd-grid" v-loading="loading">
      <el-card v-for="w in tableData" :key="w.id" class="wd-card" shadow="never">
        <div class="flex justify-between items-start">
          <div class="user-cell">
            <el-avatar :size="36" :src="w.user_avatar || ''" class="user-cell__avatar">
              {{ (w.user_nickname || w.user_mobile || '?')[0] }}
            </el-avatar>
            <div class="user-cell__info">
              <div class="user-cell__name">{{ w.user_nickname || '-' }}</div>
              <div class="user-cell__sub num">
                <span v-if="w.user_mobile">{{ w.user_mobile }}</span>
                <span class="text-ink-400">WD-{{ w.id }}</span>
              </div>
            </div>
          </div>
          <div class="flex flex-col items-end gap-1">
            <el-tag class="tag-tone-amber" size="small" effect="light">待审核</el-tag>
            <el-tooltip v-if="isLedgerReviewPending(w)" :content="w.ledger_review_reason || '提现资金流水待人工复核'">
              <el-tag type="danger" size="small" effect="dark">资金待复核</el-tag>
            </el-tooltip>
          </div>
        </div>

        <div class="wd-amount-row flex items-baseline gap-2.5 mt-3.5">
          <span class="wd-amount num">¥ {{ fmt(w.amount) }}</span>
          <span class="wd-source">{{ w.source || '账户余额' }}</span>
        </div>

        <div class="wd-info">
          <span class="wd-info-lb">方式</span>
          <span>{{ typeMap[w.type]?.label || w.type || '-' }}</span>
          <span class="wd-info-lb">手续费</span>
          <span class="num">¥ {{ fmt(w.fee) }}</span>
          <span class="wd-info-lb">实到</span>
          <span class="num wd-actual">¥ {{ fmt(w.actual_amount) }}</span>
          <span class="wd-info-lb">申请</span>
          <span class="num wd-info-time">{{ w.created_at }}</span>
        </div>

        <div class="flex justify-end gap-2 mt-3">
          <template v-if="isLedgerReviewPending(w)">
            <el-button
              v-has-perm="['distribution.commission.review']"
              type="danger"
              size="small"
              plain
              @click="openWithdrawalLedgerReview(w)"
            >
              复核资金流水
            </el-button>
          </template>
          <template v-else>
            <el-button size="small" @click="handleReject(w)">拒绝</el-button>
            <el-button type="primary" size="small" @click="handleApproveAndPay(w)">审核并登记打款</el-button>
          </template>
        </div>
      </el-card>

      <el-empty v-if="!loading && !tableData.length" description="暂无待审核申请" :image-size="80" style="grid-column: 1 / -1" />
    </div>

    <!-- 待审核：分页 -->
    <div v-if="tab === 'pending' && pagination.total > pagination.limit" class="flex justify-end mb-3.5">
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[6, 12, 24, 48]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="fetchList"
        @current-change="fetchList"
      />
    </div>

    <!-- 已处理：表格 -->
    <el-card v-if="tab === 'done'" class="table-card" shadow="never">
      <div class="table-header">
        <span class="table-title">已处理 <span class="table-count">共 {{ fmtCount(pagination.total) }} 笔</span></span>
      </div>
      <el-table v-loading="loading" :data="tableData">
        <el-table-column label="单号" width="160">
          <template #default="{ row }"><span class="num text-brand">WD-{{ row.id }}</span></template>
        </el-table-column>
        <el-table-column label="会员" min-width="200">
          <template #default="{ row }">
            <div class="user-cell">
              <el-avatar :size="28" :src="row.user_avatar || ''" class="user-cell__avatar">
                {{ (row.user_nickname || row.user_mobile || '?')[0] }}
              </el-avatar>
              <div class="user-cell__info">
                <div class="user-cell__name">{{ row.user_nickname || '-' }}</div>
                <div v-if="row.user_mobile" class="user-cell__mobile num">{{ row.user_mobile }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="申请金额" width="120" align="right">
          <template #default="{ row }"><span class="num wd-amount-text">¥ {{ fmt(row.amount) }}</span></template>
        </el-table-column>
        <el-table-column label="实到金额" width="120" align="right">
          <template #default="{ row }"><span class="num">¥ {{ fmt(row.actual_amount) }}</span></template>
        </el-table-column>
        <el-table-column label="方式" width="100">
          <template #default="{ row }">
            <el-tag :class="['tag-tone-' + (typeMap[row.type]?.tone || 'gray')]" size="small" effect="light">
              {{ typeMap[row.type]?.label || row.type }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="申请时间" prop="created_at" width="160">
          <template #default="{ row }"><span class="num text-secondary">{{ row.created_at }}</span></template>
        </el-table-column>
        <el-table-column label="处理时间" prop="updated_at" width="160">
          <template #default="{ row }"><span class="num text-secondary">{{ row.updated_at || '-' }}</span></template>
        </el-table-column>
        <el-table-column label="状态" width="110">
          <template #default="{ row }">
            <div class="flex flex-col items-start gap-1">
              <el-tag :class="['tag-tone-' + (statusMap[row.status]?.tone || 'gray')]" size="small" effect="light">
                {{ statusMap[row.status]?.label || row.status }}
              </el-tag>
              <el-tag v-if="isLedgerReviewPending(row)" type="danger" size="small" effect="dark">资金待复核</el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="备注" prop="remark" min-width="160" show-overflow-tooltip>
          <template #default="{ row }"><span class="text-secondary">{{ row.remark || '-' }}</span></template>
        </el-table-column>
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
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
            <span v-else class="text-secondary">—</span>
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
        @size-change="fetchList"
        @current-change="fetchList"
      />
    </el-card>

    <!-- 提现规则 -->
    <el-card v-if="tab === 'rule'" v-loading="ruleLoading" class="rule-card" shadow="never">
      <div class="card-head">
        <div class="card-title">提现规则</div>
        <div class="card-meta">已对接 system_configs 配置项</div>
      </div>
      <div class="card-body">
        <div class="rule-grid">
          <div class="rule-lb">提现起点</div>
          <div>
            <el-input-number v-model="ruleForm.withdrawal_min_amount" :min="0" :precision="2" :step="10" style="width:200px" />
            <span class="rule-hint" style="margin-left:8px">元</span>
          </div>
          <div class="rule-lb">单笔上限</div>
          <div>
            <el-input-number v-model="ruleForm.withdrawal_max_amount" :min="0" :precision="2" :step="100" style="width:200px" />
            <span class="rule-hint" style="margin-left:8px">元</span>
          </div>
          <div class="rule-lb">每日次数</div>
          <div>
            <el-input-number v-model="ruleForm.withdrawal_daily_count" :min="0" :step="1" style="width:200px" />
            <span class="rule-hint" style="margin-left:8px">次</span>
          </div>
          <div class="rule-lb">手续费</div>
          <div class="rule-fee">
            <el-input-number v-model="ruleForm.withdrawal_fee_rate" :min="0" :max="100" :precision="2" :step="0.1" style="width:120px" />
            <span class="rule-hint">% · 最低</span>
            <el-input-number v-model="ruleForm.withdrawal_fee_min" :min="0" :precision="2" :step="0.5" style="width:100px" />
            <span class="rule-hint">元 · 最高</span>
            <el-input-number v-model="ruleForm.withdrawal_fee_max" :min="0" :precision="2" :step="1" style="width:100px" />
            <span class="rule-hint">元</span>
          </div>
          <div class="rule-lb">到账方式</div>
          <div class="rule-ways">
            <div
              v-for="c in channelDefs"
              :key="c.key"
              class="rule-way"
              :class="{ on: isChannelOn(c.key) }"
              @click="toggleChannel(c.key)"
            >
              {{ c.label }}
            </div>
          </div>
          <div class="rule-lb">到账周期</div>
          <div class="rule-cycles">
            <span>微信 / 支付宝：T+0 ~ 24h</span>
            <span>银行卡：T+1 ~ T+3</span>
          </div>
        </div>
        <div class="rule-foot">
          <el-button :disabled="ruleSaving" @click="fetchRule">重置</el-button>
          <el-button type="primary" :loading="ruleSaving" @click="saveRule">保存规则</el-button>
        </div>
      </div>
    </el-card>

    <!-- 拒绝原因对话框 -->
    <el-dialog
      v-model="rejectDialogVisible"
      title="拒绝提现申请"
      width="420px"
      @close="handleRejectDialogClose"
    >
      <el-form
        ref="rejectFormRef"
        :model="rejectForm"
        :rules="rejectRules"
        label-width="80px"
      >
        <el-form-item label="拒绝原因" prop="remark">
          <el-input
            v-model="rejectForm.remark"
            type="textarea"
            placeholder="请输入拒绝原因"
            :rows="4"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectDialogVisible = false">取消</el-button>
        <el-button type="danger" :loading="rejectSubmitLoading" @click="handleRejectSubmit">
          确认拒绝
        </el-button>
      </template>
    </el-dialog>
    <WithdrawalLedgerReviewDialog
      v-model="withdrawalLedgerReviewVisible"
      :row="withdrawalLedgerReviewRow"
      @resolved="fetchList"
    />
  </div>
</template>

<style lang="scss" scoped>
.withdrawal-container {
  padding: 0;
}

// ── filter-bar 内嵌 seg ──
.wd-filter {
  justify-content: space-between;
}

.wd-seg {
  display: inline-flex;
  background: var(--ink-50);
  padding: 3px;
  border-radius: 8px;

  button {
    border: 0;
    background: transparent;
    padding: 6px 16px;
    font-size: 13px;
    color: var(--ink-600);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;

    &.on {
      background: #fff;
      color: var(--brand-600);
      box-shadow: 0 1px 3px rgba(0,0,0,.08);
      font-weight: 600;
    }

    &:hover:not(.on) { color: var(--brand-500); }
  }
}

// ── 待审核：卡片网格（row-22 = 2 列）──
.wd-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
  margin-bottom: 14px;
}

@media (max-width: 1280px) {
  .wd-grid { grid-template-columns: 1fr; }
}

.wd-card {
  :deep(.el-card__body) { padding: 16px 18px; }
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 8px;

  &__avatar { flex-shrink: 0; }
  &__info { line-height: 1.3; min-width: 0; }
  &__name { font-size: 13px; color: var(--ink-900); font-weight: 500; }
  &__mobile { font-size: 11px; color: var(--ink-400); }
  &__sub {
    font-size: 11px;
    color: var(--ink-400);
    display: flex;
    gap: 6px;
    margin-top: 2px;
  }
}

.wd-amount {
  font-size: 28px;
  font-weight: 700;
  color: var(--rose-500);
}

.wd-source {
  font-size: 12px;
  color: var(--ink-400);
}

.wd-info {
  margin-top: 14px;
  padding: 12px;
  background: var(--ink-50);
  border-radius: 6px;
  font-size: 12.5px;
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 6px 12px;
}

.wd-info-lb { color: var(--ink-400); }
.wd-actual { color: var(--success); font-weight: 600; }
.wd-info-time { color: var(--ink-500); }


// ── 已处理：表格 ──
.wd-amount-text { font-weight: 700; color: var(--rose-500); }

// ── 提现规则 ──
.rule-card {
  :deep(.el-card__body) { padding: 0; }
}

.rule-grid {
  display: grid;
  grid-template-columns: 180px 1fr;
  gap: 14px 24px;
  font-size: 13px;
  align-items: center;
}

.rule-lb { color: var(--ink-500); }
.rule-fee { display: flex; gap: 14px; align-items: center; }
.rule-hint { color: var(--ink-500); font-size: 12px; }

.rule-ways {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.rule-way {
  padding: 8px 14px;
  border: 1px solid var(--ink-200);
  background: #fff;
  color: var(--ink-400);
  border-radius: 6px;
  font-size: 12.5px;
  cursor: pointer;
  transition: all 0.15s;

  &.on {
    border-color: var(--brand-500);
    background: var(--brand-50);
    color: var(--brand-500);
  }

  &:hover { border-color: var(--brand-400); }
}

.rule-cycles {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12.5px;
  color: var(--ink-700);
}

.rule-foot {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid var(--ink-100);
}

// ── el-tag tone ──
</style>
