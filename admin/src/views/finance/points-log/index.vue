<script setup lang="ts" name="FinancePointsLog">
import { ElMessage } from 'element-plus'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import type { PointsLogItem } from '@/api/user'
import { userManageApi } from '@/api/user'
import { useExport } from '@/composables/useExport'
import { useListPage } from '@/hooks/useListPage'
import type { PointsLogQuery } from '@/types/api'

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
} = useListPage<PointsLogItem, PointsLogQuery & { keyword?: string }>({
    fetchFn: (params) => userManageApi.getPointsLogs(params),
    defaultSearchForm: { keyword: '', type: undefined, start_date: undefined, end_date: undefined },
})

const router = useRouter()

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

const adjustVisible = ref(false)
const adjustLoading = ref(false)
const adjustForm = reactive({ user_id: undefined as number | undefined, points: 0, remark: '' })

const handleAdjust = async () => {
    if (!adjustForm.user_id || !adjustForm.points || !adjustForm.remark.trim()) {
        ElMessage.warning('请填写会员 ID、调整积分和原因')
        return
    }
    adjustLoading.value = true
    try {
        await userManageApi.adjustPoints({
            user_id: adjustForm.user_id,
            points: adjustForm.points,
            remark: adjustForm.remark.trim(),
        })
        ElMessage.success('积分调整成功')
        adjustVisible.value = false
        Object.assign(adjustForm, { user_id: undefined, points: 0, remark: '' })
        getLogList()
    } finally {
        adjustLoading.value = false
    }
}

const { exporting: exportingPoints, doExport: doExportPoints } = useExport()
const handleExport = () => {
    doExportPoints('/adminapi/user/points-logs/export', searchForm, '积分流水')
}

const fmtCount = (v: any) => Number(v ?? 0).toLocaleString('zh-CN')

const typeTone: Record<number, string> = {
    1: 'amber',  // 后台调整
    2: 'green',  // 注册赠送
    3: 'green',  // 签到
    4: 'blue',   // 消费赠送
    5: 'rose',   // 消费扣减
}

// 当前页统计
const stats = computed(() => {
    let plus = 0, minus = 0
    let plusCount = 0, minusCount = 0
    for (const row of logList.value) {
        const p = Number(row.points ?? 0)
        if (p >= 0) { plus += p; plusCount += 1 } else { minus += -p; minusCount += 1 }
    }
    return { plus, minus, plusCount, minusCount, net: plus - minus }
})

// 发放/消耗 来源/去向（来自当前页类型聚合）
const sourceList = computed(() => {
    const acc: Record<string, { label: string; v: number; color: string }> = {}
    const palette = ['#4f6bff', '#10b981', '#f59e0b', '#a855f7', '#0ea5e9', '#94a3b8']
    let i = 0
    for (const row of logList.value) {
        const p = Number(row.points ?? 0)
        if (p <= 0) continue
        const k = row.type_text || String(row.type)
        if (!acc[k]) { acc[k] = { label: k, v: 0, color: palette[i++ % palette.length] } }
        acc[k].v += p
    }
    const total = Object.values(acc).reduce((s, x) => s + x.v, 0) || 1
    return Object.values(acc)
        .map(x => ({ ...x, pct: Math.round(x.v / total * 1000) / 10 }))
        .sort((a, b) => b.v - a.v)
})

const destList = computed(() => {
    const acc: Record<string, { label: string; v: number; color: string }> = {}
    const palette = ['#f43f5e', '#ec4899', '#a855f7', '#f59e0b', '#94a3b8']
    let i = 0
    for (const row of logList.value) {
        const p = Number(row.points ?? 0)
        if (p >= 0) continue
        const k = row.type_text || String(row.type)
        if (!acc[k]) { acc[k] = { label: k, v: 0, color: palette[i++ % palette.length] } }
        acc[k].v += -p
    }
    const total = Object.values(acc).reduce((s, x) => s + x.v, 0) || 1
    return Object.values(acc)
        .map(x => ({ ...x, pct: Math.round(x.v / total * 1000) / 10 }))
        .sort((a, b) => b.v - a.v)
})

</script>

<template>
  <div class="points-log-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">积分明细</h2>
        <p class="page-desc">积分发放与核销逐笔记录</p>
      </div>
      <div class="page-actions">
        <el-button :loading="exportingPoints" @click="handleExport">导出</el-button>
        <el-button @click="router.push('/finance/points-rules')">积分规则</el-button>
        <el-button type="primary" @click="adjustVisible = true">积分调整</el-button>
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
        <div class="lb">本页发放</div>
        <div class="nm num">{{ fmtCount(stats.plus) }}</div>
        <div class="tr"><span style="color:var(--success)">{{ fmtCount(stats.plusCount) }} 笔</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页消耗</div>
        <div class="nm num">{{ fmtCount(stats.minus) }}</div>
        <div class="tr"><span style="color:var(--rose-500)">{{ fmtCount(stats.minusCount) }} 笔</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页净额</div>
        <div class="nm num">{{ fmtCount(stats.net) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">差额</span></div>
      </div>
    </div>

    <!-- 发放来源 / 消耗去向 -->
    <div class="row-12">
      <el-card class="card-pts" shadow="never">
        <div class="card-head">
          <div class="card-title">积分发放来源</div>
          <div class="card-meta">本页统计</div>
        </div>
        <div class="card-body">
          <div v-for="(s, i) in sourceList" :key="i" class="pts-row">
            <div class="flex justify-between items-center mb-1.5 text-[12.5px]">
              <span class="pts-label">{{ s.label }}</span>
              <span class="pts-val num">
                {{ fmtCount(s.v) }} <span class="pts-pct" :style="{ color: s.color }">{{ s.pct }}%</span>
              </span>
            </div>
            <div class="progbar"><i :style="{ width: s.pct + '%', background: s.color }" /></div>
          </div>
          <el-empty v-if="!sourceList.length" description="暂无发放数据" :image-size="60" />
        </div>
      </el-card>

      <el-card class="card-pts" shadow="never">
        <div class="card-head">
          <div class="card-title">积分消耗去向</div>
          <div class="card-meta">本页统计</div>
        </div>
        <div class="card-body">
          <div v-for="(d, i) in destList" :key="i" class="pts-row">
            <div class="flex justify-between items-center mb-1.5 text-[12.5px]">
              <span class="pts-label">{{ d.label }}</span>
              <span class="pts-val num">
                {{ fmtCount(d.v) }} <span class="pts-pct" :style="{ color: d.color }">{{ d.pct }}%</span>
              </span>
            </div>
            <div class="progbar"><i :style="{ width: d.pct + '%', background: d.color }" /></div>
          </div>
          <el-empty v-if="!destList.length" description="暂无消耗数据" :image-size="60" />
        </div>
      </el-card>
    </div>

    <!-- filter-bar -->
    <div class="filter-bar">
      <el-input
        v-model="searchForm.keyword"
        placeholder="搜索会员"
        clearable
        style="width: 240px"
        @keyup.enter="handleSearch"
      />
      <span class="filter-label">类型：</span>
      <el-select v-model="searchForm.type" placeholder="全部" clearable style="width: 130px">
        <el-option label="后台调整" :value="1" />
        <el-option label="注册赠送" :value="2" />
        <el-option label="签到" :value="3" />
        <el-option label="消费赠送" :value="4" />
        <el-option label="消费扣减" :value="5" />
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
        <span class="table-title">积分流水 <span class="table-count">共 {{ fmtCount(pagination.total) }} 笔</span></span>
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

        <el-table-column label="类型" width="120">
          <template #default="{ row }">
            <el-tag :class="['tag-tone-' + (typeTone[row.type] || 'gray')]" size="small" effect="light">{{ row.type_text }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column label="变动" width="120" align="right">
          <template #default="{ row }">
            <span class="num pts-amount" :class="{ pos: row.points >= 0, neg: row.points < 0 }">
              {{ row.points >= 0 ? '+' : '' }}{{ fmtCount(row.points) }}
            </span>
          </template>
        </el-table-column>

        <el-table-column label="变动前" prop="before_points" width="100" align="right">
          <template #default="{ row }"><span class="num text-secondary">{{ fmtCount(row.before_points) }}</span></template>
        </el-table-column>

        <el-table-column label="变动后" prop="after_points" width="100" align="right">
          <template #default="{ row }"><span class="num after-pts">{{ fmtCount(row.after_points) }}</span></template>
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

  <el-dialog v-model="adjustVisible" title="调整会员积分" width="460px">
    <el-form :model="adjustForm" label-width="90px">
      <el-form-item label="会员 ID" required>
        <el-input-number v-model="adjustForm.user_id" :min="1" :precision="0" style="width:100%" />
      </el-form-item>
      <el-form-item label="调整积分" required>
        <el-input-number v-model="adjustForm.points" :precision="0" :step="100" style="width:100%" />
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
.points-log-container {
  padding: 0;
}

// el-card 内嵌 card-head（与 overview 对齐）
:deep(.el-card__body) { padding: 0; }


.card-pts {
  :deep(.el-card__body) { padding: 0; }
}

.pts-row {
  padding: 8px 0;

  & + & { border-top: 1px solid var(--ink-50); }
}

.pts-label { color: var(--ink-700); }
.pts-val { color: var(--ink-900); font-weight: 600; }
.pts-pct { font-weight: 600; margin-left: 4px; }

.user-cell {
  display: flex;
  align-items: center;
  gap: 8px;

  &__avatar { flex-shrink: 0; }
  &__info { line-height: 1.3; min-width: 0; }
  &__name { font-size: 13px; color: var(--ink-900); }
  &__mobile { font-size: 11px; color: var(--ink-400); }
}

.pts-amount {
  font-weight: 700;

  &.pos { color: var(--success); }
  &.neg { color: var(--rose-500); }
}

.after-pts { font-weight: 600; color: var(--ink-900); }

:deep(.el-card.card-pts) {
  --el-card-border-color: var(--ink-100);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm) !important;
}

.card-pts .card-body { min-height: 200px; }


.filter-label {
  font-size: 12px;
  color: var(--ink-500);
  margin-left: 4px;
}
</style>
