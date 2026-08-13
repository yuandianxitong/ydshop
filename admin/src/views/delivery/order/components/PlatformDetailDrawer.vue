<script setup lang="ts" name="PlatformDetailDrawer">
import { computed, ref } from 'vue'

import { deliveryOrderApi } from '@/api/delivery'
import type { DeliveryOrderInfo, DeliveryOrderTrack } from '@/types/api'

const props = defineProps<{
    statusMap: Record<string, { label: string; tone: string }>
    platformMap: Record<string, string>
}>()

const visible = ref(false)
const loading = ref(false)
const tracksLoading = ref(false)
const detail = ref<DeliveryOrderInfo | null>(null)
const tracks = ref<DeliveryOrderTrack[]>([])

const isThirdParty = computed(
    () => !!detail.value?.platform && detail.value.platform !== 'merchant'
)

const receiver = computed(() => {
    const snap = (detail.value?.order as any)?.address_snapshot
    if (!snap) return null
    return {
        name: snap.name || '-',
        phone: snap.phone || snap.mobile || '',
        address: [snap.province, snap.city, snap.district, snap.detail].filter(Boolean).join(' ')
    }
})

const fmt = (v: any) =>
    Number(v ?? 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const open = async (id: number) => {
    visible.value = true
    detail.value = null
    tracks.value = []
    loading.value = true
    try {
        const res = await deliveryOrderApi.getDetail(id)
        detail.value = res.data
    } finally {
        loading.value = false
    }
    if (isThirdParty.value) {
        tracksLoading.value = true
        try {
            const res = await deliveryOrderApi.getTracks(id)
            tracks.value = res.data || []
        } catch {
            tracks.value = []
        } finally {
            tracksLoading.value = false
        }
    }
}

defineExpose({ open })
</script>

<template>
    <el-drawer v-model="visible" title="配送单详情" size="480px" destroy-on-close>
        <div v-loading="loading" class="drawer-body">
            <template v-if="detail">
                <!-- 基础信息 -->
                <div class="section-title">基础信息</div>
                <el-descriptions :column="1" border size="small">
                    <el-descriptions-item label="配送单号">
                        <span class="num">DL-{{ detail.id }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="关联订单">
                        <span class="num">{{ (detail.order as any)?.order_no || '-' }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="配送状态">
                        <el-tag
                            :class="[
                                'tag-tone-' + (props.statusMap[detail.status]?.tone || 'gray')
                            ]"
                            size="small"
                            effect="light"
                        >
                            {{ props.statusMap[detail.status]?.label || detail.status }}
                        </el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="配送员">
                        {{ detail.staff?.name || '未分配' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="运费">
                        <span class="num">¥ {{ fmt(detail.fee) }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item v-if="receiver" label="收件人">
                        {{ receiver.name
                        }}<span v-if="receiver.phone" class="num receiver-phone">{{
                            receiver.phone
                        }}</span>
                        <div v-if="receiver.address" class="receiver-addr">
                            {{ receiver.address }}
                        </div>
                    </el-descriptions-item>
                    <el-descriptions-item label="创建时间">
                        <span class="num">{{ detail.created_at || '-' }}</span>
                    </el-descriptions-item>
                </el-descriptions>

                <!-- 三方平台信息 -->
                <template v-if="isThirdParty">
                    <div class="section-title">三方平台信息</div>
                    <el-descriptions :column="1" border size="small">
                        <el-descriptions-item label="配送平台">
                            {{ props.platformMap[detail.platform!] || detail.platform }}
                        </el-descriptions-item>
                        <el-descriptions-item label="三方单号">
                            <span class="num">{{ detail.platform_order_id || '-' }}</span>
                        </el-descriptions-item>
                        <el-descriptions-item label="平台原始状态">
                            {{ detail.platform_status || '-' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="骑手姓名">
                            {{ detail.rider_name || '-' }}
                        </el-descriptions-item>
                        <el-descriptions-item label="骑手电话">
                            <span class="num">{{ detail.rider_phone || '-' }}</span>
                        </el-descriptions-item>
                        <el-descriptions-item label="发单时间">
                            <span class="num">{{ detail.dispatched_at || '-' }}</span>
                        </el-descriptions-item>
                        <el-descriptions-item v-if="detail.dispatch_fail_reason" label="失败原因">
                            <span class="fail-reason">{{ detail.dispatch_fail_reason }}</span>
                        </el-descriptions-item>
                    </el-descriptions>

                    <!-- 骑手轨迹 -->
                    <div class="section-title">骑手轨迹</div>
                    <div v-loading="tracksLoading" class="tracks-wrap">
                        <el-empty
                            v-if="!tracksLoading && tracks.length === 0"
                            description="暂无轨迹数据"
                            :image-size="72"
                        />
                        <el-timeline v-else>
                            <el-timeline-item
                                v-for="(t, i) in tracks"
                                :key="i"
                                :timestamp="t.reported_at"
                                placement="top"
                            >
                                <div class="track-status">{{ t.platform_status || '-' }}</div>
                                <div class="track-pos num">经度 {{ t.lng }} · 纬度 {{ t.lat }}</div>
                            </el-timeline-item>
                        </el-timeline>
                    </div>
                </template>
            </template>
        </div>
    </el-drawer>
</template>

<style lang="scss" scoped>
.drawer-body {
    min-height: 200px;
}

.section-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-900);
    margin: 18px 0 10px;

    &:first-child {
        margin-top: 0;
    }
}

.receiver-phone {
    font-size: 12px;
    color: var(--ink-500);
    margin-left: 8px;
}

.receiver-addr {
    font-size: 11.5px;
    color: var(--ink-500);
    margin-top: 2px;
}

.fail-reason {
    color: var(--rose-500, #f43f5e);
}

.tracks-wrap {
    padding: 4px 2px 0;
    min-height: 100px;
}

.track-status {
    font-size: 13px;
    color: var(--ink-900);
    font-weight: 500;
}

.track-pos {
    font-size: 12px;
    color: var(--ink-500);
    margin-top: 2px;
}
</style>
