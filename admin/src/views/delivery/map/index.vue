<script setup lang="ts" name="DeliveryMap">
import AMapLoader from '@amap/amap-jsapi-loader'
import { ElMessage } from 'element-plus'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { deliveryMapApi } from '@/api/delivery'
import type { DeliveryMapOrder } from '@/types/api'

// ─── 状态着色 + label ──────────────────────────────
const statusMeta: Record<DeliveryMapOrder['status'], { label: string; color: string }> = {
    pending:    { label: '待派单', color: '#94a3b8' },
    assigned:   { label: '已分配', color: '#f59e0b' },
    picking:    { label: '取货中', color: '#eab308' },
    delivering: { label: '配送中', color: '#3b82f6' },
    completed:  { label: '已完成', color: '#10b981' },
    cancelled:  { label: '已取消', color: '#ef4444' },
}

const statusOrder: DeliveryMapOrder['status'][] = ['pending', 'assigned', 'picking', 'delivering', 'completed', 'cancelled']

// ─── 筛选 state ────────────────────────────────────
const selectedStatuses = ref<DeliveryMapOrder['status'][]>(['pending', 'assigned', 'picking', 'delivering'])
const timeRange        = ref<'today' | '3days' | '7days'>('today')

const computeSince = (): string => {
    const now = new Date()
    const offset = timeRange.value === 'today' ? 0 : (timeRange.value === '3days' ? 2 : 6)
    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate() - offset, 0, 0, 0)
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} 00:00:00`
}

const escapeHtml = (s: string): string =>
    String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')

// ─── 地图实例 ──────────────────────────────────────
const mapContainer    = ref<HTMLDivElement | null>(null)
const mapInstance     = ref<any>(null)
const AMapRef         = ref<any>(null)
const markers         = ref<any[]>([])
const infoWindow      = ref<any>(null)
const orders          = ref<DeliveryMapOrder[]>([])
const loading         = ref(false)
const initFailed      = ref(false)
const initFailedReason = ref('')

const stats = computed(() => {
    const counts: Record<string, number> = {}
    for (const s of statusOrder) counts[s] = 0
    for (const o of orders.value) {
        if (counts[o.status] !== undefined) counts[o.status]++
    }
    return counts
})

const loadOrders = async () => {
    if (!mapInstance.value || !AMapRef.value) return
    loading.value = true
    try {
        const res = await deliveryMapApi.getOrders({
            statuses: selectedStatuses.value.join(','),
            since: computeSince(),
        })
        orders.value = (res.data ?? []) as DeliveryMapOrder[]
        renderMarkers()
    } finally {
        loading.value = false
    }
}

const renderMarkers = () => {
    if (!mapInstance.value || !AMapRef.value) return
    for (const m of markers.value) {
        mapInstance.value.remove(m)
    }
    markers.value = []
    if (infoWindow.value) {
        infoWindow.value.close()
    }

    if (orders.value.length === 0) return

    for (const o of orders.value) {
        const meta = statusMeta[o.status] ?? { label: o.status, color: '#888' }
        const marker = new AMapRef.value.Marker({
            position: [o.dest_lng, o.dest_lat],
            content: `<div style="
                width: 14px; height: 14px; border-radius: 50%;
                background: ${meta.color};
                border: 2px solid #fff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.3);
            "></div>`,
            offset: new AMapRef.value.Pixel(-7, -7),
        })
        marker.on('click', () => {
            if (!infoWindow.value) {
                infoWindow.value = new AMapRef.value.InfoWindow({ offset: new AMapRef.value.Pixel(0, -10) })
            }
            infoWindow.value.setContent(`
                <div style="font-size:13px;line-height:1.6">
                    <div><b>${escapeHtml(o.order_no)}</b></div>
                    <div>状态：<span style="color:${meta.color}">${escapeHtml(meta.label)}</span></div>
                    <div>地址：${escapeHtml(o.address)}</div>
                </div>
            `)
            infoWindow.value.open(mapInstance.value, [o.dest_lng, o.dest_lat])
        })
        marker.setMap(mapInstance.value)
        markers.value.push(marker)
    }

    if (markers.value.length > 0) {
        mapInstance.value.setFitView(markers.value, false, [60, 60, 60, 60])
    }
}

const initMap = async () => {
    let cfg: any
    try {
        const res = await deliveryMapApi.getConfig()
        cfg = res.data
    } catch (e: any) {
        initFailed.value = true
        initFailedReason.value = '获取地图配置失败'
        return
    }
    if (!cfg || !cfg.js_api_key) {
        initFailed.value = true
        initFailedReason.value = '高德 JS API Key 未配置'
        return
    }
    ;(window as any)._AMapSecurityConfig = { securityJsCode: cfg.js_security_code }
    try {
        const AMap = await AMapLoader.load({
            key: cfg.js_api_key,
            version: '2.0',
            plugins: ['AMap.InfoWindow'],
        })
        AMapRef.value = AMap
        mapInstance.value = new AMap.Map(mapContainer.value!, {
            zoom: 11,
            center: [116.404, 39.915],
        })
        await loadOrders()
    } catch (e: any) {
        initFailed.value = true
        initFailedReason.value = '高德地图加载失败：' + (e?.message ?? '未知错误')
    }
}

watch([selectedStatuses, timeRange], () => {
    loadOrders()
}, { deep: true })

onMounted(() => {
    initMap()
})

onBeforeUnmount(() => {
    if (mapInstance.value) {
        mapInstance.value.destroy()
    }
})

const toggleStatus = (s: DeliveryMapOrder['status']) => {
    const i = selectedStatuses.value.indexOf(s)
    if (i >= 0) selectedStatuses.value.splice(i, 1)
    else selectedStatuses.value.push(s)
}

const refresh = () => {
    loadOrders()
    ElMessage.success('已刷新')
}
</script>

<template>
    <div class="delivery-map">
        <div class="filter-bar">
            <div class="left">
                <span class="lb">状态：</span>
                <el-button-group>
                    <el-button
                        v-for="s in statusOrder"
                        :key="s"
                        :type="selectedStatuses.includes(s) ? 'primary' : 'default'"
                        size="small"
                        @click="toggleStatus(s)"
                    >
                        <span class="dot" :style="{ background: statusMeta[s].color }"></span>
                        {{ statusMeta[s].label }} ({{ stats[s] }})
                    </el-button>
                </el-button-group>
            </div>
            <div class="right">
                <el-radio-group v-model="timeRange" size="small">
                    <el-radio-button value="today">今日</el-radio-button>
                    <el-radio-button value="3days">近 3 天</el-radio-button>
                    <el-radio-button value="7days">近 7 天</el-radio-button>
                </el-radio-group>
                <el-button :loading="loading" size="small" @click="refresh">刷新</el-button>
            </div>
        </div>

        <div v-if="initFailed" class="empty-state">
            <el-empty :description="initFailedReason">
                <template #description>
                    <p>{{ initFailedReason }}</p>
                    <p style="font-size:12px;color:var(--el-text-color-secondary)">
                        请在 系统设置 → 系统配置 → amap 分组 填写 web_api_key / js_api_key / js_security_code
                    </p>
                </template>
            </el-empty>
        </div>
        <div v-else ref="mapContainer" v-loading="loading" class="map-canvas"></div>
    </div>
</template>

<style scoped lang="scss">
.delivery-map {
    display: flex;
    flex-direction: column;
    /* header + tabs + main-content 上下 padding(20×2) */
    height: calc(100vh - var(--header-h) - var(--tabs-h) - 40px);
    min-height: 0;
}

.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #fff;
    border-radius: 4px;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 8px;
    flex-shrink: 0;

    .left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .right {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lb {
        font-size: 13px;
        color: var(--ink-500, #8b95a7);
    }
    .dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 4px;
        vertical-align: middle;
    }
}

.map-canvas {
    flex: 1;
    width: 100%;
    min-height: 0;
    border-radius: 4px;
    overflow: hidden;
}

.empty-state {
    flex: 1;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
