<script setup lang="ts" name="FinanceOverview">
import { ref, computed, onMounted } from 'vue'
import '@/utils/echart'
import VChart from 'vue-echarts'
import { financeApi } from '@/api/finance'
import { useExport } from '@/composables/useExport'

const cc = computed(() => ({
    surface: '#fff',
    tooltipBg: 'rgba(255,255,255,0.96)',
    tooltipBorder: '#eee',
    tooltipText: '#333',
    axisLine: '#e5e7eb',
    splitLine: '#f0f0f0',
    axisLabel: '#94a3b8',
}))

const overview = ref<Record<string, any> | null>(null)
const trendData = ref<any[]>([])
const composition = ref<any[]>([])
const withdrawalStats = ref<Record<string, any> | null>(null)
const loading = ref(true)
const trendDays = ref(30)

const fmt = (v: any) => Number(v ?? 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const fmtCount = (v: any) => Number(v ?? 0).toLocaleString('zh-CN')

const loadAll = async () => {
    loading.value = true
    try {
        const [ov, tr, ic, ws] = await Promise.all([
            financeApi.getOverview(),
            financeApi.getTrend(trendDays.value),
            financeApi.getIncomeComposition(),
            financeApi.getWithdrawalStats(),
        ])
        overview.value = ov.data
        trendData.value = tr.data ?? []
        composition.value = ic.data ?? []
        withdrawalStats.value = ws.data
    } finally {
        loading.value = false
    }
}

const reloadTrend = async () => {
    const res = await financeApi.getTrend(trendDays.value)
    trendData.value = res.data ?? []
}

onMounted(loadAll)

const { exporting: exportingMonth, doExport: doExportMonth } = useExport()
const handleExportMonth = () => {
    doExportMonth('/adminapi/finance/overview/export-month', {}, '财务月报')
}

// ── KPI（row-14）─────────────────────────────────────
const kpi = computed(() => {
    const o = overview.value || {}
    const w = withdrawalStats.value || {}
    return [
        { lb: '今日收入', nm: '¥ ' + fmt(o.today_income), tr: '入账', trColor: 'var(--success)' },
        { lb: '今日退款', nm: '¥ ' + fmt(o.today_refund), tr: '出账', trColor: 'var(--rose-500)' },
        { lb: '本月营收', nm: '¥ ' + fmt(o.month_income), tr: '当月累计', trColor: 'var(--ink-500)' },
        { lb: '待提现金额', nm: '¥ ' + fmt(o.pending_withdrawal ?? w.pending_amount), tr: (w.pending_count ?? 0) + ' 笔', trColor: 'var(--amber-500)' },
    ]
})

// ── 现金流趋势（双折线 收入 / 退款）─────────────────
const trendOption = computed(() => {
    if (!trendData.value.length) return {}
    const dates = trendData.value.map((d: any) => d.date.slice(5))
    const incomes = trendData.value.map((d: any) => Number(d.income ?? 0))
    const refunds = trendData.value.map((d: any) => Number(d.refund ?? 0))
    return {
        tooltip: {
            trigger: 'axis',
            backgroundColor: cc.value.tooltipBg,
            borderColor: cc.value.tooltipBorder,
            textStyle: { color: cc.value.tooltipText, fontSize: 13 },
            formatter: (params: any[]) => {
                const date = trendData.value[params[0].dataIndex].date
                return `${date}<br/>`
                    + `<span style="color:#4f6bff">● 收入: ¥${fmt(params[0].value)}</span><br/>`
                    + `<span style="color:#10b981">● 退款: ¥${fmt(params[1].value)}</span>`
            },
        },
        legend: {
            data: ['收入(¥)', '退款(¥)'],
            bottom: 0,
            textStyle: { color: cc.value.axisLabel, fontSize: 12 },
        },
        grid: { top: 16, right: 20, bottom: 40, left: 60 },
        xAxis: {
            type: 'category',
            data: dates,
            axisLine: { lineStyle: { color: cc.value.axisLine } },
            axisLabel: { color: cc.value.axisLabel, fontSize: 11 },
            axisTick: { show: false },
        },
        yAxis: {
            type: 'value',
            splitLine: { lineStyle: { type: 'dashed', color: cc.value.splitLine } },
            axisLabel: {
                color: cc.value.axisLabel,
                fontSize: 11,
                formatter: (v: number) => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : String(v),
            },
        },
        series: [
            {
                name: '收入(¥)', type: 'line', data: incomes, smooth: true,
                symbol: 'circle', symbolSize: 6,
                lineStyle: { color: '#4f6bff', width: 3 },
                itemStyle: { color: '#4f6bff', borderWidth: 2, borderColor: '#fff' },
                areaStyle: {
                    color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [{ offset: 0, color: '#4f6bff40' }, { offset: 1, color: '#4f6bff05' }] },
                },
            },
            {
                name: '退款(¥)', type: 'line', data: refunds, smooth: true,
                symbol: 'circle', symbolSize: 6,
                lineStyle: { color: '#10b981', width: 2, type: 'dashed' },
                itemStyle: { color: '#10b981', borderWidth: 2, borderColor: '#fff' },
            },
        ],
    }
})

// ── 资金构成（progbar 列表，由收入构成派生）───────
const COMPOSITION_COLOR_MAP: Record<string, string> = {
    order: '#4f6bff', recharge: '#10b981', refund: '#f43f5e',
    withdrawal: '#f59e0b', other: '#a855f7',
}
const compositionList = computed(() => {
    const total = composition.value.reduce((s: number, d: any) => s + Math.abs(Number(d.amount ?? 0)), 0) || 1
    return composition.value
        .filter((d: any) => Number(d.amount) !== 0)
        .map((d: any) => ({
            label: d.label,
            amount: Number(d.amount),
            pct: Math.round(Math.abs(Number(d.amount)) / total * 1000) / 10,
            color: COMPOSITION_COLOR_MAP[d.type] ?? '#94a3b8',
        }))
})

// ── 提现状态分布（progbar 列表，从 withdrawalStats 派生）
const wdSplit = computed(() => {
    const w = withdrawalStats.value || {}
    const total = (Number(w.pending_amount ?? 0) + Number(w.approved_amount ?? 0) + Number(w.rejected_amount ?? 0)) || 1
    const pct = (n: any) => Math.round(Number(n ?? 0) / total * 1000) / 10
    return [
        { label: '已打款', amount: w.approved_amount, color: '#10b981', pct: pct(w.approved_amount) },
        { label: '待审核', amount: w.pending_amount, color: '#f59e0b', pct: pct(w.pending_amount) },
        { label: '已拒绝', amount: w.rejected_amount, color: '#f43f5e', pct: pct(w.rejected_amount) },
    ]
})
</script>

<template>
  <div class="finance-overview" v-loading="loading">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">财务概览</h2>
        <p class="page-desc">资金、收入、支出、应收应付的全景</p>
      </div>
      <div class="page-actions">
        <el-button :loading="exportingMonth" @click="handleExportMonth">导出月报</el-button>
      </div>
    </div>

    <!-- KPI -->
    <div class="row-14">
      <div v-for="(c, i) in kpi" :key="i" class="kpi-mini">
        <div class="lb">{{ c.lb }}</div>
        <div class="nm num">{{ c.nm }}</div>
        <div class="tr"><span :style="{ color: c.trColor }">{{ c.tr }}</span></div>
      </div>
    </div>

    <!-- 趋势 / 资金构成 -->
    <div class="row-83">
      <el-card class="card-trend" shadow="never">
        <div class="card-head">
          <div class="card-title">现金流趋势</div>
          <div class="flex items-center gap-2">
            <el-radio-group v-model="trendDays" size="small" @change="reloadTrend">
              <el-radio-button :value="7">7天</el-radio-button>
              <el-radio-button :value="30">30天</el-radio-button>
              <el-radio-button :value="90">90天</el-radio-button>
            </el-radio-group>
          </div>
        </div>
        <div class="card-body">
          <v-chart v-if="trendData.length" class="chart" :option="trendOption" autoresize />
          <el-empty v-else description="暂无数据" :image-size="60" style="height:240px" />
        </div>
      </el-card>

      <el-card class="card-comp" shadow="never">
        <div class="card-head">
          <div class="card-title">资金构成</div>
          <div class="card-meta">按业务类型</div>
        </div>
        <div class="card-body">
          <div v-for="(c, i) in compositionList" :key="i" class="comp-row">
            <div class="flex justify-between items-center mb-1.5 text-[12.5px]">
              <span class="comp-label">{{ c.label }}</span>
              <span class="comp-amount num" :class="{ neg: c.amount < 0 }">
                {{ c.amount < 0 ? '-' : '' }}¥ {{ fmt(Math.abs(c.amount)) }}
              </span>
            </div>
            <div class="progbar"><i :style="{ width: c.pct + '%', background: c.color }" /></div>
          </div>
          <el-empty v-if="!compositionList.length" description="暂无数据" :image-size="60" />
        </div>
      </el-card>
    </div>

    <!-- 本月收入分类 / 应收应付 -->
    <div class="row-12">
      <el-card class="card-cat" shadow="never">
        <div class="card-head">
          <div class="card-title">本月收入分类</div>
          <div class="card-meta">含退款扣减</div>
        </div>
        <div class="card-body">
          <table class="cat-tbl">
            <tbody>
              <tr v-for="(c, i) in compositionList" :key="i">
                <td class="cat-name">{{ c.label }}</td>
                <td class="cat-val num" :class="{ neg: c.amount < 0 }">
                  {{ c.amount < 0 ? '-' : '+' }}¥ {{ fmt(Math.abs(c.amount)) }}
                </td>
                <td class="cat-bar">
                  <div class="progbar"><i :style="{ width: c.pct + '%', background: c.color }" /></div>
                </td>
                <td class="cat-pct num" :style="{ color: c.color }">{{ c.pct }}%</td>
              </tr>
              <tr v-if="!compositionList.length">
                <td colspan="4" style="padding:30px 0;text-align:center;color:var(--ink-400)">暂无数据</td>
              </tr>
            </tbody>
          </table>
        </div>
      </el-card>

      <el-card class="card-wd" shadow="never">
        <div class="card-head">
          <div class="card-title">提现统计</div>
          <div class="card-meta">本月</div>
        </div>
        <div class="card-body">
          <div class="wd-cards">
            <div class="wd-cell teal">
              <div class="wd-lb">已打款金额</div>
              <div class="wd-nm num">¥ {{ fmt(withdrawalStats?.approved_amount) }}</div>
              <div class="wd-tr">{{ fmtCount(withdrawalStats?.month_count) }} 笔 · 本月</div>
            </div>
            <div class="wd-cell rose">
              <div class="wd-lb">待审核金额</div>
              <div class="wd-nm num">¥ {{ fmt(withdrawalStats?.pending_amount) }}</div>
              <div class="wd-tr">{{ fmtCount(withdrawalStats?.pending_count) }} 笔 · 待处理</div>
            </div>
          </div>
          <div class="wd-split-title">状态分布</div>
          <div v-for="(w, i) in wdSplit" :key="i" class="comp-row">
            <div class="flex justify-between items-center mb-1.5 text-[12.5px]">
              <span class="comp-label">{{ w.label }}</span>
              <span class="comp-amount num">¥ {{ fmt(w.amount) }} <span class="comp-pct" :style="{ color: w.color }">{{ w.pct }}%</span></span>
            </div>
            <div class="progbar"><i :style="{ width: w.pct + '%', background: w.color }" /></div>
          </div>
        </div>
      </el-card>
    </div>
  </div>
</template>

<style scoped lang="scss">
.finance-overview {
  padding: 0;
  min-height: 100%;
}

// ── 通用 card head（el-card 内部）──
:deep(.el-card__body) { padding: 0; }

// ── 趋势图 ──
.chart {
  width: 100%;
  height: 240px;
}

// ── 资金构成 / 状态分布 列表 ──
.comp-row {
  padding: 8px 0;

  & + & { border-top: 1px solid var(--ink-50); }
}

.comp-label {
  color: var(--ink-700);
}

.comp-amount {
  font-weight: 600;
  color: var(--ink-900);

  &.neg { color: var(--rose-500); }
}

.comp-pct {
  font-weight: 600;
  margin-left: 4px;
}

// ── 收入分类表 ──
.cat-tbl {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;

  td {
    padding: 10px 0;
    border-bottom: 1px solid var(--ink-50);
  }

  tr:last-child td { border-bottom: 0; }

  .cat-name { color: var(--ink-700); }

  .cat-val {
    width: 160px;
    font-weight: 600;
    color: var(--ink-900);

    &.neg { color: var(--rose-500); }
  }

  .cat-bar { width: 140px; }
  .cat-pct { width: 50px; text-align: right; font-size: 12.5px; }
}

// ── 提现统计 ──
.wd-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-bottom: 14px;
}

.wd-cell {
  padding: 14px;
  border-radius: 8px;
  border: 1px solid;

  &.teal {
    background: linear-gradient(135deg, #10b98112, #10b98122);
    border-color: #10b98133;

    .wd-lb { color: #10b981; }
  }

  &.rose {
    background: linear-gradient(135deg, #f4485e12, #f4485e22);
    border-color: #f4485e33;

    .wd-lb { color: #f43f5e; }
  }
}

.wd-lb {
  font-size: 12px;
  font-weight: 500;
}

.wd-nm {
  font-size: 24px;
  font-weight: 700;
  margin-top: 4px;
  color: var(--ink-900);
}

.wd-tr {
  font-size: 11px;
  color: var(--ink-500);
  margin-top: 2px;
}

.wd-split-title {
  font-size: 12px;
  color: var(--ink-500);
  margin-bottom: 6px;
}

// ── el-card 边界与阴影回归 SHOP card 风格 ──
:deep(.el-card) {
  --el-card-border-color: var(--ink-100);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-sm) !important;
}
</style>
