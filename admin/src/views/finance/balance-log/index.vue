<script setup lang="ts" name="FinanceBalanceLog">
import { ElMessage } from 'element-plus'
import { computed, reactive, ref } from 'vue'

import type { BalanceLogItem } from '@/api/user'
import { userManageApi } from '@/api/user'
import { useExport } from '@/composables/useExport'
import { useListPage } from '@/hooks/useListPage'
import type { BalanceLogQuery } from '@/types/api'

const {
    list: logList,
    loading,
    pagination,
    searchForm,
    getList: getLogList,
    handleSearch,
    resetSearch,
    handlePageChange,
    handleSizeChange,
} = useListPage<BalanceLogItem, BalanceLogQuery & { keyword?: string }>({
    fetchFn: (params) => userManageApi.getBalanceLogs(params),
    defaultSearchForm: { keyword: '', type: undefined, start_date: undefined, end_date: undefined },
})

const dateRange = ref<[string, string] | null>(null)
const handleDateChange = (val: [string, string] | null) => {
    if (val && val.length === 2) {
        searchForm.start_date = val[0]
        searchForm.end_date = val[1]
    } else {
        searchForm.start_date = undefined
        searchForm.end_date = undefined
    }
}
const handleReset = () => {
    dateRange.value = null
    resetSearch()
}

const fmt = (v: any) => Number(v ?? 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtCount = (v: any) => Number(v ?? 0).toLocaleString('zh-CN')

// 类型 → 设计稿 tag tone（充值绿/消费灰/退款蓝/调整黄/佣金紫）
const typeTone: Record<number, string> = {
    1: 'green',   // 充值
    2: 'gray',    // 消费
    3: 'blue',    // 退款
    4: 'amber',   // 后台调整
}

// 当前页统计
const stats = computed(() => {
    let plus = 0, minus = 0
    let plusCount = 0, minusCount = 0
    for (const row of logList.value) {
        const a = Number(row.amount ?? 0)
        if (a >= 0) { plus += a; plusCount += 1 } else { minus += -a; minusCount += 1 }
    }
    return { plus, minus, plusCount, minusCount, net: plus - minus }
})

const adjustVisible = ref(false)
const adjustLoading = ref(false)
const adjustForm = reactive({ user_id: undefined as number | undefined, amount: 0, remark: '' })

const handleAdjust = async () => {
    if (!adjustForm.user_id || !adjustForm.amount || !adjustForm.remark.trim()) {
        ElMessage.warning('请填写会员 ID、调整金额和原因')
        return
    }
    adjustLoading.value = true
    try {
        await userManageApi.adjustBalance({
            user_id: adjustForm.user_id,
            amount: adjustForm.amount,
            remark: adjustForm.remark.trim(),
        })
        ElMessage.success('余额调整成功')
        adjustVisible.value = false
        Object.assign(adjustForm, { user_id: undefined, amount: 0, remark: '' })
        getLogList()
    } finally {
        adjustLoading.value = false
    }
}

const { exporting: exportingBalance, doExport: doExportBalance } = useExport()
const handleExport = () => {
    doExportBalance('/adminapi/user/balance-logs/export', searchForm, '余额流水')
}
</script>

<template>
  <div class="balance-log-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">余额明细</h2>
        <p class="page-desc">会员余额变动逐笔追溯</p>
      </div>
      <div class="page-actions">
        <el-button :loading="exportingBalance" @click="handleExport">导出</el-button>
        <el-button type="primary" @click="adjustVisible = true">余额调整</el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">变动总数</div>
        <div class="nm num">{{ fmtCount(pagination.total) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">分页统计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页增加</div>
        <div class="nm num">¥ {{ fmt(stats.plus) }}</div>
        <div class="tr"><span style="color:var(--success)">{{ fmtCount(stats.plusCount) }} 笔</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页减少</div>
        <div class="nm num">¥ {{ fmt(stats.minus) }}</div>
        <div class="tr"><span style="color:var(--rose-500)">{{ fmtCount(stats.minusCount) }} 笔</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页净额</div>
        <div class="nm num">¥ {{ fmt(stats.net) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">差额</span></div>
      </div>
    </div>

    <!-- filter-bar -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索会员 / 单号"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">变动类型：</span>
      <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 130px">
        <el-option label="充值" :value="1" />
        <el-option label="消费" :value="2" />
        <el-option label="退款" :value="3" />
        <el-option label="后台调整" :value="4" />
      </el-select>
      <span class="filter-label">日期：</span>
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
      <el-button @click="handleReset">重置</el-button>
      <el-button type="primary" @click="handleSearch">查询</el-button>
    </div>

    <!-- 表格 -->
    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <span class="table-title">余额明细 <span class="table-count">共 {{ fmtCount(pagination.total) }} 笔</span></span>
      </div>

      <el-table v-loading="loading" :data="logList">
        <el-table-column label="时间" prop="created_at" width="200">
          <template #default="{ row }">
            <span class="num text-secondary">{{ row.created_at }}</span>
          </template>
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

        <el-table-column label="变动类型" width="120">
          <template #default="{ row }">
            <el-tag :class="['tag-tone-' + (typeTone[row.type] || 'gray')]" size="small" effect="light">{{ row.type_text }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column label="金额" width="140" align="right">
          <template #default="{ row }">
            <span class="num amount" :class="{ pos: parseFloat(row.amount) >= 0, neg: parseFloat(row.amount) < 0 }">
              {{ parseFloat(row.amount) >= 0 ? '+' : '' }}¥ {{ fmt(Math.abs(parseFloat(row.amount))) }}
            </span>
          </template>
        </el-table-column>

        <el-table-column label="变动前" width="120" align="right">
          <template #default="{ row }">
            <span class="num text-secondary">¥ {{ fmt(row.before_balance) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="变动后" width="120" align="right">
          <template #default="{ row }">
            <span class="num after-balance">¥ {{ fmt(row.after_balance) }}</span>
          </template>
        </el-table-column>

        <el-table-column label="关联单号" prop="order_no" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="num text-brand">{{ row.order_no || '-' }}</span>
          </template>
        </el-table-column>

        <el-table-column label="说明" prop="remark" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="text-secondary">{{ row.remark || '-' }}</span>
          </template>
        </el-table-column>

        <el-table-column label="操作人" prop="operator_name" width="110">
          <template #default="{ row }">
            <span class="text-secondary">{{ row.operator_name || '-' }}</span>
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
  </div>

  <el-dialog v-model="adjustVisible" title="调整会员余额" width="460px">
    <el-form :model="adjustForm" label-width="90px">
      <el-form-item label="会员 ID" required>
        <el-input-number v-model="adjustForm.user_id" :min="1" :precision="0" style="width:100%" />
      </el-form-item>
      <el-form-item label="调整金额" required>
        <el-input-number v-model="adjustForm.amount" :precision="2" :step="1" style="width:100%" />
        <div class="form-tip">正数增加，负数扣减</div>
      </el-form-item>
      <el-form-item label="调整原因" required>
        <el-input v-model="adjustForm.remark" type="textarea" :rows="3" maxlength="200" show-word-limit />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="adjustVisible = false">取消</el-button>
      <el-button type="primary" :loading="adjustLoading" @click="handleAdjust">确认调整</el-button>
    </template>
  </el-dialog>
</template>

<style lang="scss" scoped>
.balance-log-container {
  padding: 0;
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

.amount {
  font-weight: 700;

  &.pos { color: var(--success); }
  &.neg { color: var(--rose-500); }
}

.after-balance { font-weight: 600; color: var(--ink-900); }


.filter-label {
  font-size: 12px;
  color: var(--ink-500);
  margin-left: 4px;
}
</style>
