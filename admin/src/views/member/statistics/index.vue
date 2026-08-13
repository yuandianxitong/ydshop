<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import '@/utils/echart'
import VChart from 'vue-echarts'
import { memberStatisticsApi } from '@/api/member-statistics'

// ─── State ───────────────────────────────────────────────────
const overview = ref<Record<string, any> | null>(null)
const trendData = ref<any[]>([])
const activeData = ref<any[]>([])
const levelData = ref<any[]>([])
const consumeData = ref<any[]>([])
const sourceData = ref<any[]>([])
const retentionData = ref<any[]>([])
const loading = ref(true)
const trendDays = ref(30)

// ─── Load data ───────────────────────────────────────────────
const loadAll = async () => {
    loading.value = true
    try {
        const [ov, td, ad, ld, cd, sd, rd] = await Promise.all([
            memberStatisticsApi.getOverview(),
            memberStatisticsApi.getRegisterTrend(trendDays.value),
            memberStatisticsApi.getActiveAnalysis(),
            memberStatisticsApi.getLevelDistribution(),
            memberStatisticsApi.getConsumeDistribution(),
            memberStatisticsApi.getSourceDistribution(),
            memberStatisticsApi.getRetentionMatrix(8),
        ])
        overview.value  = ov.data
        trendData.value = td.data ?? []
        activeData.value = ad.data ?? []
        levelData.value = ld.data ?? []
        consumeData.value = cd.data ?? []
        sourceData.value = sd.data ?? []
        retentionData.value = rd.data ?? []
    } finally {
        loading.value = false
    }
}

const reloadTrend = async () => {
    const res = await memberStatisticsApi.getRegisterTrend(trendDays.value)
    trendData.value = res.data ?? []
}

onMounted(loadAll)

// ─── KPI（设计稿：总会员 / 月活 MAU / 日活 DAU / ARPU） ────
const dauCount = computed(() => {
  const item = activeData.value.find((d: any) => d.type === 'day')
  return item?.count ?? 0
})
const mauCount = computed(() => {
  const item = activeData.value.find((d: any) => d.type === 'month')
  return item?.count ?? 0
})

const kpiCards = computed(() => {
    const o = overview.value
    return [
        { label: '总会员',   value: o ? o.total : '-',     suffix: '人', tip: '累计注册', tone: '' },
        { label: '月活 MAU', value: mauCount.value || '-', suffix: '人', tip: '近 30 日',  tone: '' },
        { label: '日活 DAU', value: dauCount.value || '-', suffix: '人', tip: '当日',      tone: '' },
        { label: '本月新增', value: o ? o.month : '-',     suffix: '人', tip: '当月',      tone: 'tr-success' },
    ]
})

// ─── Register/Active 趋势（折线，2 序列） ─────────────────────
const cc = computed(() => ({
    tooltipBg: 'rgba(255,255,255,0.96)',
    tooltipBorder: '#eee',
    tooltipText: '#333',
    axisLine: '#e5e7eb',
    splitLine: '#f0f0f0',
    axisLabel: '#94a3b8',
}))

const trendOption = computed(() => {
    if (!trendData.value.length) return {}
    const dates  = trendData.value.map((d: any) => d.date.slice(5))
    const counts = trendData.value.map((d: any) => d.count)

    return {
        tooltip: {
            trigger: 'axis',
            backgroundColor: cc.value.tooltipBg,
            borderColor: cc.value.tooltipBorder,
            textStyle: { color: cc.value.tooltipText, fontSize: 13 },
        },
        grid: { top: 40, right: 20, bottom: 32, left: 50 },
        xAxis: {
            type: 'category',
            data: dates,
            axisLine: { lineStyle: { color: cc.value.axisLine } },
            axisLabel: {
                color: cc.value.axisLabel,
                fontSize: 11,
                interval: trendDays.value > 30 ? 6 : (trendDays.value > 14 ? 2 : 0),
            },
            axisTick: { show: false },
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            splitLine: { lineStyle: { type: 'dashed', color: cc.value.splitLine } },
            axisLabel: { color: cc.value.axisLabel, fontSize: 11 },
        },
        series: [
            {
                name: '新增注册',
                type: 'line',
                data: counts,
                smooth: true,
                symbol: 'circle',
                symbolSize: 5,
                lineStyle: { color: '#4f6bff', width: 2.5 },
                itemStyle: { color: '#4f6bff', borderWidth: 2, borderColor: '#fff' },
                areaStyle: {
                    color: {
                        type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(79,107,255,0.18)' },
                            { offset: 1, color: 'rgba(79,107,255,0.02)' },
                        ],
                    },
                },
            },
        ],
    }
})

// ─── 注册来源（后端按 users 身份字段推断） ──────────────────
const sourceList = computed(() => {
    const palette = ['#10b981', '#0ea5e9', '#a855f7', '#f43f5e', '#f59e0b']
    return sourceData.value.map((s: any, i: number) => ({
        label: s.label,
        count: Number(s.count) || 0,
        pct:   Number(s.pct) || 0,
        color: palette[i % palette.length],
    }))
})

// ─── 留存矩阵（后端 retention-matrix 端点） ──────────────────
const retentionMatrix = computed(() =>
    retentionData.value.map((r: any) => ({
        date:  r.week,
        news:  r.news,
        cells: r.cells, // { d1, d3, d7, d14, d30: number | null }
    }))
)

const cellBg = (v: number | null) => {
    if (v === null) return ''
    const alpha = (Number(v) / 80).toFixed(2)
    return `rgba(79,107,255,${alpha})`
}
const cellColor = (v: number | null) => {
    if (v === null) return 'var(--ink-300, #cbd0d9)'
    return Number(v) > 50 ? '#fff' : 'var(--ink-700, #4b5563)'
}

// ─── RFM 9 宫格（取 consume-distribution 数据） ──────────────
const rfmCells = computed(() => {
    const palette = ['#f43f5e','#f59e0b','#a855f7','#0ea5e9','#10b981','#14b8a6','#94a3b8','#64748b','#475569']
    const labels = ['重要价值','重要发展','重要保持','重要挽留','一般价值','一般发展','一般保持','一般挽留','流失人群']
    return labels.map((label, i) => {
        const value = consumeData.value[i]?.count ?? 0
        return { label, value: Number(value).toLocaleString('zh-CN'), color: palette[i] }
    })
})
</script>

<template>
  <div class="member-statistics" v-loading="loading">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">用户统计</h2>
        <p class="page-desc">新增、活跃、留存与价值分布的全景看板</p>
      </div>
      <div class="page-actions"></div>
    </div>

    <!-- Row 1: KPI mini -->
    <div class="row-14">
      <div v-for="(card, i) in kpiCards" :key="i" class="kpi-mini">
        <div class="lb">{{ card.label }}</div>
        <div class="nm num">{{ card.value }}<span v-if="card.value !== '-'" class="kpi-suffix"> {{ card.suffix }}</span></div>
        <div class="tr"><span :class="card.tone">{{ card.tip }}</span></div>
      </div>
    </div>

    <!-- Row 2: 趋势 + 注册来源 (row-83 = 2:1) -->
    <div class="row-83">
      <el-card class="chart-card" shadow="never">
        <div class="card-header">
          <span class="card-title">新增 / 活跃 趋势</span>
          <div class="card-actions">
            <el-radio-group v-model="trendDays" size="small" @change="reloadTrend">
              <el-radio-button :value="7">7天</el-radio-button>
              <el-radio-button :value="30">30天</el-radio-button>
              <el-radio-button :value="90">90天</el-radio-button>
            </el-radio-group>
          </div>
        </div>
        <v-chart v-if="trendData.length" class="chart" :option="trendOption" autoresize />
        <el-empty v-else description="暂无数据" :image-size="60" style="height:268px" />
      </el-card>

      <el-card class="chart-card" shadow="never">
        <div class="card-header">
          <span class="card-title">注册来源</span>
        </div>
        <div v-if="sourceList.length" class="source-list">
          <div v-for="s in sourceList" :key="s.label" class="source-item">
            <div class="source-row">
              <span>{{ s.label }}<span class="source-count num"> · {{ s.count }} 人</span></span>
              <span class="source-pct num" :style="{ color: s.color }">{{ s.pct }}%</span>
            </div>
            <div class="progbar">
              <i :style="{ width: Math.min(s.pct, 100) + '%', background: s.color }" />
            </div>
          </div>
        </div>
        <el-empty v-else description="暂无注册来源数据" :image-size="60" style="height:268px" />
      </el-card>
    </div>

    <!-- Row 3: 留存矩阵 + RFM 9 宫格 (row-12 = 1.4:1) -->
    <div class="row-12">
      <el-card class="chart-card" shadow="never">
        <div class="card-header">
          <span class="card-title">留存矩阵</span>
          <span class="card-hint">注册当周 → 留存率</span>
        </div>
        <div class="retention-wrap">
          <table class="retention-tbl num">
            <thead>
              <tr>
                <th>日期</th>
                <th>新增</th>
                <th>D1</th>
                <th>D3</th>
                <th>D7</th>
                <th>D14</th>
                <th>D30</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in retentionMatrix" :key="r.date">
                <td class="date-col">{{ r.date }}</td>
                <td class="news-col">{{ r.news }}</td>
                <td v-for="dn in (['d1','d3','d7','d14','d30'] as const)" :key="dn">
                  <span
                    v-if="r.cells[dn] !== null && r.cells[dn] !== undefined"
                    class="ret-cell"
                    :style="{ background: cellBg(r.cells[dn]), color: cellColor(r.cells[dn]) }"
                  >
                    {{ r.cells[dn] }}%
                  </span>
                  <span v-else class="ret-cell-empty">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </el-card>

      <el-card class="chart-card" shadow="never">
        <div class="card-header">
          <span class="card-title">用户价值分布 (RFM)</span>
          <span class="card-hint">最近一次 / 频次 / 金额</span>
        </div>
        <div class="rfm-grid">
          <div
            v-for="cell in rfmCells"
            :key="cell.label"
            class="rfm-cell"
            :style="{ background: cell.color + '12', borderColor: cell.color + '33' }"
          >
            <div class="rfm-label" :style="{ color: cell.color }">{{ cell.label }}</div>
            <div class="rfm-value num">{{ cell.value }}</div>
          </div>
        </div>
      </el-card>
    </div>
  </div>
</template>

<style scoped lang="scss">
.member-statistics {
  padding: 0;
  min-height: 100%;
}

.kpi-suffix {
  font-size: 14px;
  font-weight: 400;
  color: var(--ink-500, #8b95a7);
  margin-left: 4px;
}

.tr-success { color: var(--el-color-success); }

// ═══════════════════════════════════════
// Chart card
// ═══════════════════════════════════════
.chart-card {
  :deep(.el-card__body) { padding: 16px 18px; }
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--el-text-color-primary);
}

.card-hint {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.card-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.chart {
  height: 268px;
  width: 100%;
}

// ═══════════════════════════════════════
// 注册来源 progbar
// ═══════════════════════════════════════
.source-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.source-item {
  padding: 9px 0;
}
.source-row {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
  margin-bottom: 5px;
}
.source-pct { font-weight: 600; }
.source-count { color: var(--ink-500); font-weight: 400; }
.progbar {
  width: 100%;
  height: 6px;
  border-radius: 3px;
  background: var(--ink-100, #eef0f5);
  overflow: hidden;
  i {
    display: block;
    height: 100%;
    border-radius: 3px;
    transition: width .3s ease;
  }
}

// ═══════════════════════════════════════
// 留存矩阵
// ═══════════════════════════════════════
.retention-wrap {
  overflow-x: auto;
}
.retention-tbl {
  border-collapse: collapse;
  font-size: 12px;
  width: 100%;

  th {
    padding: 8px 10px;
    text-align: left;
    color: var(--ink-500, #8b95a7);
    font-weight: 500;
    border-bottom: 1px solid var(--ink-100, #eef0f5);
    font-family: inherit;
  }
  td {
    padding: 10px;
  }
  .date-col { color: var(--ink-500, #8b95a7); }
  .news-col {
    color: var(--ink-900, #1f2937);
    font-weight: 600;
  }
}

.ret-cell {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 4px;
  font-weight: 500;
  min-width: 48px;
  text-align: center;
}
.ret-cell-empty { color: var(--ink-300, #cbd0d9); }

// ═══════════════════════════════════════
// RFM 9 宫格
// ═══════════════════════════════════════
.rfm-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
}

.rfm-cell {
  padding: 12px 14px;
  border-radius: 6px;
  border: 1px solid;
}

.rfm-label {
  font-size: 11.5px;
  font-weight: 500;
}

.rfm-value {
  font-size: 18px;
  font-weight: 700;
  margin-top: 4px;
  color: var(--ink-900, #1f2937);
}
</style>
