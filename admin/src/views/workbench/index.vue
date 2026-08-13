<script setup lang="ts">
import '@/utils/echart'

import { computed, onMounted, ref } from 'vue'
import VChart from 'vue-echarts'
import { useRouter } from 'vue-router'

import {
    getEcommerceStats,
    getHotProducts,
    getPaymentMix,
    getPendingTasks,
    getRealtimeKpi,
    getRecentOrders,
    getSalesTrend,
} from '@/api/dashboard'
import type {
    EcommerceStats,
    HotProduct,
    PaymentMixItem,
    PendingTasks,
    RealtimeKpi,
    RecentOrder,
    SalesTrendItem,
} from '@/types/api'
import { useUserStore } from '@/store/modules/user.store'

const router = useRouter()
const userStore = useUserStore()

// ─── Real data ───
const stats = ref<EcommerceStats | null>(null)
const trendData = ref<SalesTrendItem[]>([])
const hotProducts = ref<HotProduct[]>([])
const pendingTasks = ref<PendingTasks | null>(null)
const realtime = ref<RealtimeKpi | null>(null)
const paymentMix = ref<PaymentMixItem[]>([])
const recentOrders = ref<RecentOrder[]>([])
const loading = ref(true)

const loadAll = async () => {
    loading.value = true
    try {
        const [s, t, h, p, k, m, r] = await Promise.all([
            getEcommerceStats(),
            getSalesTrend(7),
            getHotProducts(),
            getPendingTasks(),
            getRealtimeKpi(),
            getPaymentMix(30),
            getRecentOrders(6),
        ])
        stats.value = s.data
        trendData.value = t.data
        hotProducts.value = h.data
        pendingTasks.value = p.data
        realtime.value = k.data
        paymentMix.value = m.data
        recentOrders.value = r.data
    } finally {
        loading.value = false
    }
}

onMounted(loadAll)

const fmtMoney = (n: number | string | undefined, withSym = true) => {
    if (n === undefined || n === null) return withSym ? '¥ 0.00' : '0.00'
    const v = Number(n).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    return withSym ? `¥ ${v}` : v
}
const fmtNum = (n: number | string | undefined) =>
    n === undefined || n === null ? '0' : Number(n).toLocaleString('zh-CN')

const navigateTo = (path: string) => router.push(path)

// 同比工具：返回百分比字符串与方向
const pctDelta = (today: number, yesterday: number): { d: string; up: boolean } => {
    if (!yesterday) {
        return { d: today ? '100%' : '0%', up: today > 0 }
    }
    const v = (today - yesterday) / yesterday * 100
    return { d: `${Math.abs(v).toFixed(1)}%`, up: v >= 0 }
}

// 百分点差（用于率类指标，如新客占比）
const ppDelta = (today: number, yesterday: number): { d: string; up: boolean } => {
    const v = today - yesterday
    return { d: `${Math.abs(v).toFixed(1)} pp`, up: v >= 0 }
}

// ─── KPI cards ───
type KpiTone = 'primary' | 'teal' | 'amber' | 'purple'
interface Kpi {
    tone: KpiTone
    label: string
    num: string
    delta: string
    up: boolean
    deltaLabel: string
    iconClass: string
}

const kpiCards = computed<Kpi[]>(() => {
    const s = stats.value
    const todayRev = Number(s?.today_revenue || 0)
    const yestRev  = Number(s?.yesterday_revenue || 0)
    const todayOrd = Number(s?.today_orders || 0)
    const yestOrd  = Number(s?.yesterday_orders || 0)
    const aovToday = todayOrd ? todayRev / todayOrd : 0
    const aovYest  = yestOrd ? yestRev / yestOrd : 0
    const newRate     = Number(s?.today_new_buyer_rate || 0)
    const newRateYest = Number(s?.yesterday_new_buyer_rate || 0)

    const gmv = pctDelta(todayRev, yestRev)
    const ord = pctDelta(todayOrd, yestOrd)
    const newer = ppDelta(newRate, newRateYest)
    const aov = pctDelta(aovToday, aovYest)

    return [
        { tone: 'primary', label: '今日 GMV',    num: fmtMoney(todayRev),
          delta: gmv.d, up: gmv.up, deltaLabel: '同比', iconClass: 'i-svg:shopping-cart' },
        { tone: 'teal',    label: '订单数',      num: fmtNum(todayOrd),
          delta: ord.d, up: ord.up, deltaLabel: '同比', iconClass: 'i-lucide:receipt' },
        { tone: 'amber',   label: '新客占比',    num: `${newRate.toFixed(1)}%`,
          delta: newer.d, up: newer.up, deltaLabel: '同比', iconClass: 'i-svg:bolt' },
        { tone: 'purple',  label: '客单价',      num: fmtMoney(aovToday),
          delta: aov.d, up: aov.up, deltaLabel: '同比', iconClass: 'i-svg:wallet' },
    ]
})

// ─── Bar chart (近 7 日 GMV / 订单, 双柱) ───
const barOption = computed(() => {
    const data = trendData.value
    const dates = data.map((d) => d.date.slice(5))
    // GMV (万) + 订单 (百)
    const gmv = data.map((d) => Number((Number(d.revenue) / 10000).toFixed(1)))
    const orders = data.map((d) => Number((d.orders / 100).toFixed(1)))
    return {
        tooltip: {
            trigger: 'axis' as const,
            backgroundColor: 'rgba(255,255,255,0.96)',
            borderColor: '#eef1f6',
            borderWidth: 1,
            padding: [10, 14],
            textStyle: { color: '#334155', fontSize: 13 },
            axisPointer: {
                type: 'shadow' as const,
                shadowStyle: { color: 'rgba(79,107,255,0.04)' },
            },
        },
        grid: { top: 24, right: 18, bottom: 30, left: 42 },
        xAxis: {
            type: 'category' as const,
            data: dates,
            axisLine: { show: false },
            axisTick: { show: false },
            axisLabel: { color: '#94a3b8', fontSize: 11 },
        },
        yAxis: {
            type: 'value' as const,
            max: 80,
            min: 0,
            interval: 20,
            splitLine: { lineStyle: { type: 'dashed' as const, color: '#eef1f6' } },
            axisLabel: {
                color: '#94a3b8',
                fontSize: 10,
                formatter: '{value}万',
            },
        },
        series: [
            {
                name: 'GMV (万)',
                type: 'bar' as const,
                data: gmv,
                barWidth: 10,
                barGap: '30%',
                itemStyle: {
                    borderRadius: [3, 3, 0, 0],
                    color: {
                        type: 'linear' as const,
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: '#4f6bff' },
                            { offset: 1, color: '#7a8dff' },
                        ],
                    },
                },
            },
            {
                name: '订单 (百)',
                type: 'bar' as const,
                data: orders,
                barWidth: 10,
                itemStyle: {
                    borderRadius: [3, 3, 0, 0],
                    color: {
                        type: 'linear' as const,
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: '#c7d1ff' },
                            { offset: 1, color: '#dfe5ff' },
                        ],
                    },
                },
            },
        ],
    }
})

// ─── Payment mix donut (real) ───
// 支付方式中文化字典（兜底：原值）
const PAY_LABELS: Record<string, string> = {
    wechat:  '微信支付',
    alipay:  '支付宝',
    balance: '余额支付',
    cash:    '现金',
    bank:    '银行卡',
    union:   '银联',
}
const PAY_COLORS = ['#4f6bff', '#8b5cf6', '#f43f5e', '#14b8a6', '#f59e0b', '#cbd5e1']
const payLabel = (k: string) => PAY_LABELS[k] || k

// 图例
const paymentLegend = computed(() => paymentMix.value.map((d, i) => ({
    l: payLabel(d.pay_type),
    v: d.pct,
    c: PAY_COLORS[i % PAY_COLORS.length],
})))

const donutOption = computed(() => ({
    tooltip: {
        trigger: 'item' as const,
        formatter: '{b}: {d}%',
        backgroundColor: 'rgba(255,255,255,0.96)',
        borderColor: '#eef1f6',
        textStyle: { color: '#334155', fontSize: 13 },
    },
    legend: { show: false },
    series: [
        {
            type: 'pie' as const,
            radius: ['62%', '88%'],
            center: ['50%', '50%'],
            avoidLabelOverlap: false,
            label: { show: false },
            emphasis: { label: { show: false } },
            itemStyle: { borderRadius: 0, borderWidth: 0 },
            data: paymentMix.value.map((d, i) => ({
                value: d.count,
                name: payLabel(d.pay_type),
                itemStyle: { color: PAY_COLORS[i % PAY_COLORS.length] },
            })),
        },
    ],
}))

// ─── Pending tasks (all sourced from the workbench API) ───
const todos = computed(() => {
    const p = pendingTasks.value
    const items = [
        { t: '待发货订单', c: p?.pending_shipment ?? 0, iconClass: 'i-svg:truck', tone: 'tone-blue', route: '/order/order-list?status=paid' },
        { t: '售后申请待处理', c: p?.pending_refund ?? 0, iconClass: 'i-svg:rotate-ccw', tone: 'tone-rose', route: '/order/order-refund?status=pending' },
        { t: '低库存预警', c: p?.low_stock ?? 0, iconClass: 'i-svg:box', tone: 'tone-amber', route: '/goods/goods-spu?low_stock=1' },
        { t: '佣金待结算', c: p?.pending_withdrawal ?? 0, iconClass: 'i-svg:wallet', tone: 'tone-sky', route: '/distribution/withdrawal?status=pending' },
    ]
    if (!userStore.hasPermission('distribution.withdrawal.list')) {
        return items.filter((item) => item.route !== '/distribution/withdrawal?status=pending')
    }
    return items
})

const todoTotal = computed(() => todos.value.reduce((s, t) => s + (Number(t.c) || 0), 0))

// ─── 实时统计 6 KPI (今日 + 同比) ───
const liveStats = computed(() => {
    const k = realtime.value
    if (!k) return [] as Array<{ l: string; n: string; d: string; up: boolean }>
    const mk = (label: string, today: number, yest: number) => {
        const p = pctDelta(today, yest)
        return { l: label, n: fmtNum(today), d: (p.up ? '+' : '-') + p.d, up: p.up }
    }
    return [
        mk('今日订单总数', k.today_orders_total, k.yesterday_orders_total),
        mk('下单用户数',   k.today_buyers,       k.yesterday_buyers),
        mk('购物车 SKU 数', k.today_cart_items,  k.yesterday_cart_items),
        mk('支付订单',     k.today_paid,         k.yesterday_paid),
        mk('新增会员',     k.today_new_members,  k.yesterday_new_members),
        mk('退款订单',     k.today_refunds,      k.yesterday_refunds),
    ]
})

const STATUS_LABEL: Record<string, string> = {
    pending:   '待付款',
    paid:      '待发货',
    shipped:   '已发货',
    completed: '已完成',
    cancelled: '已取消',
    closed:    '已关闭',
}
const STATUS_TONE: Record<string, string> = {
    pending:   'rose',
    paid:      'amber',
    shipped:   'blue',
    completed: 'green',
    cancelled: 'gray',
    closed:    'gray',
}
const stageOf = (s: string) => STATUS_LABEL[s] || s
const stageMap = STATUS_TONE

const recentTotalLabel = computed(() => {
    const n = stats.value?.today_orders
    return n ? `最近 24h · ${fmtNum(n)} 笔` : '最近 24h'
})

// ─── 月度 GMV (Donut center) ───
const monthlyGmv = computed(() => {
    const today = Number(stats.value?.today_revenue || 0)
    if (!today) return '¥9.2M'
    const est = today * 30 / 1000000
    return `¥${est.toFixed(1)}M`
})

// ─── Hot products with sales percentage ───
const hotList = computed(() => {
    const arr = hotProducts.value.slice(0, 6)
    if (arr.length === 0) return []
    const sumQty = arr.reduce((s, p) => s + (Number(p.sales_count) || 0), 0)
    return arr.map((p, i) => ({
        id: p.id,
        rk: i + 1,
        n: p.name,
        cat: '商品',
        sales: `¥ ${(Number(p.min_price) * p.sales_count).toLocaleString('zh-CN')}`,
        qty: p.sales_count,
        conv: sumQty ? `${(p.sales_count / sumQty * 100).toFixed(1)}%` : '—',
    }))
})
</script>

<template>
    <div v-loading="loading" class="content shop-dash">
        <!-- ════════ Row 1: 4 KPI ════════ -->
        <div class="kpi-grid">
            <div
                v-for="(k, i) in kpiCards"
                :key="i"
                class="kpi"
                :class="k.tone"
            >
                <div v-if="k.tone === 'primary'" class="kpi-bg">¥</div>
                <div class="kpi-icon"><i :class="k.iconClass" /></div>
                <div class="kpi-num">{{ k.num }}</div>
                <div class="kpi-label">{{ k.label }}</div>
                <div class="kpi-trend" :class="{ up: k.tone === 'primary' }">
                    <span>{{ k.deltaLabel }}：</span>
                    <b :style="{ color: k.tone === 'primary' ? '#fff' : (k.up ? 'var(--success)' : 'var(--danger)') }">
                        {{ k.delta }}
                    </b>
                    <i
                        :class="k.up ? 'i-lucide:arrow-up-right' : 'i-lucide:arrow-down-right'"
                        :style="{ color: k.tone === 'primary' ? '#fff' : (k.up ? 'var(--success)' : 'var(--danger)') }"
                    />
                </div>
            </div>
        </div>

        <!-- ════════ Row 2: BarChart + Donut ════════ -->
        <div class="row-12">
            <div class="card">
                <div class="card-head">
                    <div class="card-title">近 7 日 GMV / 订单</div>
                    <div class="bar-legend flex gap-3.5 text-xs">
                        <span><i class="legend-dot" style="background:#4f6bff"></i>GMV (万)</span>
                        <span><i class="legend-dot" style="background:#c7d1ff"></i>订单 (百)</span>
                    </div>
                </div>
                <div class="card-body bar-body">
                    <v-chart class="bar-chart" :option="barOption" autoresize />
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-title">支付方式分布</div>
                    <div class="card-meta">本月</div>
                </div>
                <div class="card-body">
                    <div class="donut-wrap flex items-center gap-4.5 pt-3">
                        <div class="donut">
                            <v-chart class="donut-chart" :option="donutOption" autoresize />
                            <div class="donut-center">
                                <div class="v">{{ monthlyGmv }}</div>
                                <div class="l">月度 GMV</div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2.5 flex-1">
                            <div
                                v-for="(d, i) in paymentLegend"
                                :key="i"
                                class="legend-row flex items-center justify-between text-[12.5px]"
                            >
                                <span class="ll">
                                    <span class="dot" :style="{ background: d.c }"></span>{{ d.l }}
                                </span>
                                <span class="vv">{{ d.v }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════ Row 3: 待办事项 + 实时直播 ════════ -->
        <div class="row-12">
            <div class="card">
                <div class="card-head">
                    <div class="card-title">待办事项</div>
                    <div class="card-meta">共 {{ todoTotal }} 项</div>
                </div>
                <div class="card-body">
                    <div class="mini-grid mini-grid-3">
                        <div
                            v-for="(t, i) in todos"
                            :key="i"
                            class="kpi-mini"
                            :class="t.tone"
                            @click="navigateTo(t.route)"
                        >
                            <div class="lb">
                                <span class="ic"><i :class="t.iconClass" /></span>{{ t.t }}
                            </div>
                            <div class="nm">{{ t.c }}</div>
                            <div class="tr">
                                <span class="link">立即处理 →</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-title">实时数据</div>
                    <div class="card-meta">今日 / 昨日</div>
                </div>
                <div class="card-body">
                    <div class="mini-grid mini-grid-2">
                        <div
                            v-for="(it, i) in liveStats"
                            :key="i"
                            class="kpi-mini"
                        >
                            <div class="lb">{{ it.l }}</div>
                            <div class="nm">{{ it.n }}</div>
                            <div class="tr">
                                <span :class="it.up ? 'up' : 'dn'">
                                    <i :class="it.up ? 'i-lucide:arrow-up-right' : 'i-lucide:arrow-down-right'" />
                                    {{ it.d }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════ Row 4: 实时订单流 + 热销 TOP6 ════════ -->
        <div class="row-12">
            <div class="card">
                <div class="card-head">
                    <div class="card-title">实时订单流</div>
                    <div class="card-meta">{{ recentTotalLabel }}</div>
                </div>
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>订单号</th>
                            <th>会员</th>
                            <th>金额</th>
                            <th>支付方式</th>
                            <th>状态</th>
                            <th style="text-align:right">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="o in recentOrders" :key="o.id">
                            <td class="td-no" @click="navigateTo('/order/order-list')">{{ o.order_no }}</td>
                            <td>{{ o.nickname }}</td>
                            <td class="td-amt">{{ '¥ ' + Number(o.pay_amount).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                            <td class="td-pay">{{ payLabel(o.pay_type) }}</td>
                            <td>
                                <span class="tag" :class="'tag-' + stageMap[o.status]">{{ stageOf(o.status) }}</span>
                            </td>
                            <td style="text-align:right">
                                <button class="btn-ghost" @click="navigateTo('/order/order-list')">详情</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-title">热销商品 TOP 6</div>
                    <div class="card-meta">今日</div>
                </div>
                <table class="cd-table-mini">
                    <thead>
                        <tr>
                            <th>排名</th>
                            <th>商品</th>
                            <th>销售额</th>
                            <th>销量占比</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="h in hotList"
                            :key="h.rk"
                            @click="navigateTo(`/goods/goods-spu/edit?id=${h.id}`)"
                        >
                            <td>
                                <span class="rk-num" :style="{ color: h.rk <= 3 ? 'var(--rose-500)' : 'var(--ink-500)' }">
                                    {{ String(h.rk).padStart(2, '0') }}
                                </span>
                            </td>
                            <td>
                                <div class="hot-name">{{ h.n }}</div>
                                <div class="hot-cat">{{ h.cat }} · 销量 {{ h.qty }}</div>
                            </td>
                            <td><span class="num">{{ h.sales }}</span></td>
                            <td><span class="conv">{{ h.conv }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.shop-dash {
    padding: 0;
}

// .kpi / .kpi-mini / .row-* / .card / .card-head / .card-title / .card-meta / .card-body
// 全部使用全局类（base.scss + shop-extras.scss），不再 scoped 重定义

// ════════ Bar chart 区域 ════════
.bar-legend {
    color: var(--ink-500);

    span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 2px;
        display: inline-block;
    }
}

.bar-body {
    position: relative;
}

.bar-chart {
    width: 100%;
    height: 240px;
}

// ════════ Donut chart 区域 ════════

.donut {
    position: relative;
    width: 140px;
    height: 140px;
    flex-shrink: 0;
}

.donut-chart {
    width: 140px;
    height: 140px;
}

.donut-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;

    .v {
        font-size: 22px;
        font-weight: 700;
        font-family: var(--font-num);
        color: var(--ink-900);
    }

    .l {
        font-size: 11px;
        color: var(--ink-400);
        margin-top: 2px;
    }
}

.legend-row {
    .ll {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--ink-600);
    }

    .dot {
        width: 8px;
        height: 8px;
        border-radius: 2px;
    }

    .vv {
        font-weight: 600;
        color: var(--ink-800);
        font-family: var(--font-num);
    }
}

// ════════ mini KPI grid (待办 / 实时) ════════
.mini-grid {
    display: grid;
    gap: 10px;
}

.mini-grid-3 {
    grid-template-columns: 1fr 1fr 1fr;
}

.mini-grid-2 {
    grid-template-columns: 1fr 1fr;
}

// .kpi-mini 与 .tone-* 全部用 shop-extras.scss 全局类

// ════════ 表格 (实时订单流) ════════
// 卡内表格与标题的上间距 + 两侧留白
.card > .tbl,
.card > .cd-table-mini {
    width: calc(100% - 32px);
    margin: 12px 16px 16px;
    border: 1px solid var(--ink-100);
    border-radius: 6px;
    overflow: hidden;
}

.tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;

    th {
        text-align: left;
        font-weight: 500;
        color: var(--ink-500);
        padding: 10px 16px;
        background: var(--ink-50);
        border-bottom: 1px solid var(--ink-100);
        font-size: 12.5px;
        white-space: nowrap;
    }

    td {
        padding: 12px 16px;
        color: var(--ink-700);
        border-bottom: 1px solid var(--ink-100);
    }

    tr:last-child td {
        border-bottom: 0;
    }

    tr:hover td {
        background: var(--ink-50);
    }
}

.td-no {
    font-family: var(--font-num);
    color: var(--brand-500);
    cursor: pointer;

    &:hover {
        text-decoration: underline;
    }
}

.td-amt {
    font-family: var(--font-num);
    font-weight: 600;
    color: var(--ink-900);
}

.td-pay {
    color: var(--ink-600);
    font-size: 12.5px;
}

// ════════ Tag (订单状态) ════════
.tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    font-size: 11.5px;
    border-radius: 3px;
    font-weight: 500;
    line-height: 1.6;
}

.tag-gray {
    background: var(--ink-100);
    color: var(--ink-600);
}
.tag-blue {
    background: var(--brand-50);
    color: var(--brand-600);
}
.tag-green {
    background: var(--teal-50);
    color: var(--teal-500);
}
.tag-amber {
    background: var(--amber-50);
    color: var(--amber-500);
}
.tag-rose {
    background: var(--rose-50);
    color: var(--rose-500);
}
.tag-purple {
    background: var(--purple-50);
    color: var(--purple-500);
}

// ════════ Btn ghost ════════
.btn-ghost {
    color: var(--brand-500);
    background: transparent;
    border: none;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    border-radius: 4px;

    &:hover {
        background: var(--brand-50);
    }
}

// ════════ 紧凑表 (热销) ════════
.cd-table-mini {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;

    th {
        text-align: left;
        padding: 10px 12px;
        font-weight: 500;
        color: var(--ink-500);
        font-size: 11.5px;
        background: var(--ink-50);
        border-bottom: 1px solid var(--ink-100);
    }

    td {
        padding: 11px 12px;
        border-bottom: 1px solid var(--ink-100);
        color: var(--ink-700);
    }

    tr:last-child td {
        border-bottom: 0;
    }

    tr {
        cursor: pointer;
    }

    tr:hover td {
        background: var(--ink-50);
    }

    td .num {
        font-family: var(--font-num);
        font-weight: 600;
        color: var(--ink-900);
    }
}

.rk-num {
    font-family: var(--font-num);
    font-weight: 700;
    font-size: 14px;
}

.hot-name {
    font-weight: 500;
    color: var(--ink-900);
}

.hot-cat {
    font-size: 11px;
    color: var(--ink-400);
    margin-top: 2px;
}

.conv {
    color: var(--success);
    font-family: var(--font-num);
    font-weight: 600;
}

// ════════ 响应式 ════════
@media (max-width: 1280px) {
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .row-12,
    .row-2 {
        grid-template-columns: 1fr;
    }
    .mini-grid-3 {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .mini-grid-3,
    .mini-grid-2 {
        grid-template-columns: 1fr 1fr;
    }
}
</style>
