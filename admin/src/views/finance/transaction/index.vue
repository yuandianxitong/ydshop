<script setup lang="ts" name="FinanceTransaction">
import { computed, ref } from 'vue'

import { financeApi } from '@/api/finance'
import { useExport } from '@/composables/useExport'
import { useListPage } from '@/hooks/useListPage'
import type { FinanceTransactionInfo, FinanceTransactionQuery } from '@/types/api'

const typeTagMap: Record<string, { label: string; tone: string }> = {
    income:  { label: '收入', tone: 'green' },
    expense: { label: '支出', tone: 'rose' },
    refund:  { label: '退款', tone: 'amber' },
}

const bizTypeTagMap: Record<string, { label: string; tone: string }> = {
    order:      { label: '订单收款', tone: 'green' },
    recharge:   { label: '会员充值', tone: 'blue' },
    withdrawal: { label: '提现支出', tone: 'amber' },
    refund:     { label: '退款支出', tone: 'rose' },
}

const {
    list: tableData,
    loading,
    pagination,
    searchForm,
    getList: fetchList,
    handleSearch,
    resetSearch,
    handlePageChange,
    handleSizeChange,
} = useListPage<FinanceTransactionInfo, FinanceTransactionQuery>({
    fetchFn: (params) => financeApi.getTransactionList(params),
    defaultSearchForm: {
        type: undefined,
        biz_type: undefined,
        keyword: '',
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

const handleReset = () => {
    dateRange.value = []
    resetSearch()
}

const stats = computed(() => {
    let income = 0, expense = 0, refund = 0
    let incomeAmt = 0, expenseAmt = 0
    for (const row of tableData.value) {
        const amt = Number(row.amount ?? 0)
        if (row.type === 'income')  { income  += 1; incomeAmt += amt }
        if (row.type === 'expense') { expense += 1; expenseAmt += amt }
        if (row.type === 'refund')  refund += 1
    }
    return { income, expense, refund, incomeAmt, expenseAmt, net: incomeAmt - expenseAmt }
})

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')
const fmt = (v: any) => Number(v ?? 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const { exporting: exportingTrans, doExport: doExportTrans } = useExport()
const handleExport = () => {
    doExportTrans('/adminapi/finance/transactions/export', searchForm, '资金流水')
}
</script>

<template>
  <div class="transaction-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">资金流水</h2>
        <p class="page-desc">所有进出账明细，支持对账与导出</p>
      </div>
      <div class="page-actions">
        <el-button :loading="exportingTrans" @click="handleExport">导出 Excel</el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">流水总数</div>
        <div class="nm num">{{ fmtCount(pagination.total) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">分页统计</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页入账</div>
        <div class="nm num">¥ {{ fmt(stats.incomeAmt) }}</div>
        <div class="tr"><span style="color:var(--success)">{{ fmtCount(stats.income) }} 笔</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页出账</div>
        <div class="nm num">¥ {{ fmt(stats.expenseAmt) }}</div>
        <div class="tr"><span style="color:var(--rose-500)">{{ fmtCount(stats.expense) }} 笔</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页净流入</div>
        <div class="nm num">¥ {{ fmt(stats.net) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">差额</span></div>
      </div>
    </div>

    <!-- filter-bar -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索流水号 / 单号 / 备注"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">交易类型：</span>
      <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 120px">
        <el-option label="收入" value="income" />
        <el-option label="支出" value="expense" />
        <el-option label="退款" value="refund" />
      </el-select>
      <span class="filter-label">业务类型：</span>
      <el-select v-model="searchForm.biz_type" placeholder="全部" clearable style="width: 130px">
        <el-option label="订单收款" value="order" />
        <el-option label="会员充值" value="recharge" />
        <el-option label="提现支出" value="withdrawal" />
        <el-option label="退款支出" value="refund" />
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
        <span class="table-title">流水明细 <span class="table-count">共 {{ fmtCount(pagination.total) }} 笔</span></span>
      </div>

      <el-table v-loading="loading" :data="tableData">
        <el-table-column label="时间" prop="created_at" width="200">
          <template #default="{ row }">
            <span class="num text-secondary">{{ row.created_at }}</span>
          </template>
        </el-table-column>

        <el-table-column label="流水号" prop="transaction_no" width="220" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="num text-brand">{{ row.transaction_no }}</span>
          </template>
        </el-table-column>

        <el-table-column label="类型" width="120">
          <template #default="{ row }">
            <el-tag
              v-if="bizTypeTagMap[row.biz_type]"
              :class="['tag-tone-' + bizTypeTagMap[row.biz_type].tone]"
              size="small"
              effect="light"
            >
              {{ bizTypeTagMap[row.biz_type].label }}
            </el-tag>
            <el-tag v-else size="small">{{ row.biz_type }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column label="业务单号" prop="biz_no" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="num text-secondary">{{ row.biz_no || '-' }}</span>
          </template>
        </el-table-column>

        <el-table-column label="金额" width="140" align="right">
          <template #default="{ row }">
            <span class="num amount" :class="row.type">
              {{ row.type === 'income' ? '+' : '-' }}¥ {{ fmt(row.amount) }}
            </span>
          </template>
        </el-table-column>

        <el-table-column label="支付方式" prop="payment_channel" width="120">
          <template #default="{ row }">
            <span class="text-secondary">{{ row.payment_channel || '-' }}</span>
          </template>
        </el-table-column>

        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag
              :class="['tag-tone-' + (typeTagMap[row.type]?.tone || 'gray')]"
              size="small"
              effect="light"
            >
              {{ typeTagMap[row.type]?.label || row.type }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="备注" prop="remark" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            <span class="text-secondary">{{ row.remark || '-' }}</span>
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
</template>

<style lang="scss" scoped>
.transaction-container {
  padding: 0;
}

.amount {
  font-weight: 700;

  &.income { color: var(--success); }
  &.expense { color: var(--rose-500); }
  &.refund { color: var(--amber-500); }
}



.filter-label {
  font-size: 12px;
  color: var(--ink-500);
  margin-left: 4px;
}
</style>
