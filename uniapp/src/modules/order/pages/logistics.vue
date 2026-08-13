<template>
  <view class="logistics">

    <!-- Loading -->
    <view v-if="loading" class="logistics__loading">
      <u-loading-icon size="80rpx" color="var(--color-primary, #2979ff)" />
    </view>

    <d-empty v-else-if="!order" text="订单不存在" />

    <scroll-view v-else scroll-y class="logistics__scroll">

      <!-- ===== Self-pickup branch ===== -->
      <view v-if="(order as any).delivery_type === 'pickup'" class="pickup-section">
        <!-- Pickup code banner (shown when awaiting pickup) -->
        <d-pickup-banner
          v-if="(order as any).pickup_code && (order as any).pickup_status === 'pending' && pickupStoreInfo"
          :code="(order as any).pickup_code"
          :store="pickupStoreInfo"
        />

        <!-- Pickup progress timeline -->
        <view class="pickup-section__timeline">
          <view class="timeline__node is-done">
            <view class="timeline__dot" />
            <view class="timeline__line" />
            <text class="timeline__label">已下单</text>
          </view>
          <view
            class="timeline__node"
            :class="{
              'is-done': (order as any).pickup_status === 'picked',
              'is-active': (order as any).pickup_status === 'pending',
            }"
          >
            <view class="timeline__dot" />
            <view class="timeline__line" />
            <text class="timeline__label">待自提</text>
          </view>
          <view
            class="timeline__node"
            :class="{ 'is-done': (order as any).pickup_status === 'picked' }"
          >
            <view class="timeline__dot" />
            <text class="timeline__label">已自提</text>
          </view>
        </view>
      </view>

      <!-- ===== Express delivery branch ===== -->
      <view v-else class="logistics-section">

        <!-- Express info card -->
        <view class="logistics__express-card">
          <view class="logistics__express-row">
            <text class="logistics__express-label">快递公司</text>
            <text class="logistics__express-value">{{ expressCompany || '暂未获取' }}</text>
          </view>
          <view class="logistics__express-row">
            <text class="logistics__express-label">快递单号</text>
            <view class="logistics__express-no-wrap">
              <text class="logistics__express-value">{{ expressNo || '-' }}</text>
              <view v-if="expressNo" class="logistics__copy-btn" @tap="copyTrackNo">
                <text>复制</text>
              </view>
            </view>
          </view>
        </view>

        <!-- Recipient address -->
        <view v-if="order.address_snapshot" class="logistics__address-card">
          <view class="logistics__address-icon">
            <u-icon name="location" size="40rpx" color="var(--color-primary, #2979ff)" />
          </view>
          <view class="logistics__address-info">
            <view class="logistics__address-row">
              <text class="logistics__address-name">{{ order.address_snapshot.name }}</text>
              <text class="logistics__address-mobile">{{ order.address_snapshot.phone || order.address_snapshot.mobile }}</text>
            </view>
            <text class="logistics__address-full">
              {{ order.address_snapshot.province }}{{ order.address_snapshot.city }}{{ order.address_snapshot.district }}{{ order.address_snapshot.detail }}
            </text>
          </view>
        </view>

        <!-- Tracking timeline -->
        <view class="logistics__timeline-card">
          <view class="logistics__timeline-header">
            <text class="logistics__timeline-title">物流轨迹</text>
          </view>

          <view v-if="tracks.length > 0" class="logistics__timeline">
            <view
              v-for="(track, idx) in tracks"
              :key="idx"
              class="logistics__track-item"
              :class="{ 'logistics__track-item--latest': idx === 0 }"
            >
              <view class="logistics__track-left">
                <view
                  class="logistics__track-dot"
                  :class="{ 'logistics__track-dot--latest': idx === 0 }"
                />
                <view v-if="idx < tracks.length - 1" class="logistics__track-line" />
              </view>
              <view class="logistics__track-content">
                <text class="logistics__track-time">{{ track.time }}</text>
                <text class="logistics__track-desc">{{ track.description }}</text>
              </view>
            </view>
          </view>

          <!-- No track records -->
          <view v-else class="logistics__no-track">
            <view class="logistics__no-track-icon">
              <u-icon name="cart" size="80rpx" color="var(--color-border, #d1d5db)" />
            </view>
            <text class="logistics__no-track-text">暂无物流轨迹</text>
            <text class="logistics__no-track-hint">快递单号：{{ expressNo || '待发货' }}</text>
          </view>
        </view>

      </view>

      <view style="height: 40rpx;" />
    </scroll-view>

  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { orderApi, type OrderItem, type TrackingResult } from '@/api/order'
import { storeApi, type Store } from '@/api/store'

interface TrackRecord {
  time: string
  description: string
}

// ---- state ----
const order = ref<OrderItem | null>(null)
const loading = ref(true)
const tracks = ref<TrackRecord[]>([])
const pickupStoreInfo = ref<Store | null>(null)
// 真实物流查询结果中的 order_logistics 记录（补充快递公司/单号展示）
const trackingLogistics = ref<TrackingResult['logistics']>(null)

// ---- computed ----
const expressCompany = computed(() =>
  (order.value as any)?.express_company || trackingLogistics.value?.express_company || ''
)
const expressNo = computed(() =>
  (order.value as any)?.express_no || trackingLogistics.value?.express_no || ''
)

// ---- pickup store ----
watch(() => (order.value as any)?.pickup_store_id, async (id) => {
  if (id && (order.value as any)?.delivery_type === 'pickup') {
    try {
      pickupStoreInfo.value = await storeApi.detail(id)
    } catch {
      // silent fail — banner simply hidden
    }
  }
}, { immediate: true })

// ---- helpers ----
function copyTrackNo() {
  const no = expressNo.value
  if (!no) return
  uni.setClipboardData({
    data: no,
    success: () => uni.showToast({ title: '已复制', icon: 'success' }),
  })
}

// ---- fetch ----
async function fetchOrder(id: string) {
  loading.value = true
  try {
    order.value = await orderApi.getOrderDetail(id)
    // 优先取真实物流轨迹（快递鸟）；失败或为空时降级为订单节点合成轨迹
    const gotReal = await fetchTracking(id)
    if (!gotReal) {
      buildTracks()
    }
  } catch {
    order.value = null
  } finally {
    loading.value = false
  }
}

/**
 * 真实物流轨迹：GET /api/order/{id}/tracking → { logistics, traces: [{time, desc}] }
 * traces 为时间升序，UI 最新在前，需倒序。
 * 返回是否成功拿到非空轨迹。
 */
async function fetchTracking(id: string): Promise<boolean> {
  try {
    const res = await orderApi.getTracking(id)
    trackingLogistics.value = res?.logistics ?? null
    const traces = res?.traces ?? []
    if (!Array.isArray(traces) || traces.length === 0) return false
    tracks.value = [...traces]
      .reverse()
      .map((t: any) => ({
        time: t.time || '',
        description: t.desc || t.description || '',
      }))
      .filter(t => t.description)
    return tracks.value.length > 0
  } catch {
    // 接口未上线或查询失败：静默降级
    return false
  }
}

/**
 * Build timeline from order timestamps.
 * The server may return express_tracks or we synthesize from known timestamps.
 */
function buildTracks() {
  if (!order.value) return

  const rawTracks = (order.value as any).express_tracks
  if (Array.isArray(rawTracks) && rawTracks.length > 0) {
    tracks.value = rawTracks.map((t: any) => ({
      time: t.time || t.created_at || '',
      description: t.description || t.content || '',
    }))
    return
  }

  // Synthesize from order milestones
  const list: TrackRecord[] = []
  const o = order.value

  if (o.status === 'completed' && (o as any).completed_at) {
    list.push({ time: (o as any).completed_at, description: '买家已确认收货，交易完成' })
  }
  if (['shipped', 'completed'].includes(o.status) && (o as any).shipped_at) {
    list.push({ time: (o as any).shipped_at, description: `商品已由${expressCompany.value || '快递'}揽收，运单号 ${expressNo.value}` })
  }
  if (['paid', 'shipped', 'completed'].includes(o.status) && o.paid_at) {
    list.push({ time: (o as any).paid_at, description: '订单支付成功，等待商家发货' })
  }
  if (o.created_at) {
    list.push({ time: o.created_at, description: '买家提交订单，等待付款' })
  }

  tracks.value = list
}

// ---- lifecycle ----
onLoad(async (options: any) => {
  const id = options?.id
  if (!id) {
    loading.value = false
    return
  }
  await fetchOrder(id)
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.logistics {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});

  &__loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
  }

  &__scroll {
    height: 100vh;
  }

  &__express-card {
    background: var(--color-white, #ffffff);
    border-radius: 16rpx;
    padding: 28rpx 32rpx;
    margin: 24rpx 24rpx 16rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
  }

  &__express-row {
    display: flex;
    align-items: center;
    padding: 12rpx 0;
    border-bottom: 1rpx solid var(--color-border, #{$border-color});

    &:last-child {
      border-bottom: none;
    }
  }

  &__express-label {
    font-size: 26rpx;
    color: var(--color-text-2, #{$text-color-secondary});
    width: 140rpx;
    flex-shrink: 0;
  }

  &__express-value {
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    flex: 1;
  }

  &__express-no-wrap {
    display: flex;
    align-items: center;
    flex: 1;
    gap: 16rpx;
  }

  &__copy-btn {
    background: var(--color-primary, #{$primary-color});
    border-radius: 24rpx;
    padding: 4rpx 20rpx;
    flex-shrink: 0;

    text {
      font-size: 22rpx;
      color: var(--color-white, #ffffff);
    }
  }

  &__address-card {
    background: var(--color-white, #ffffff);
    border-radius: 16rpx;
    padding: 24rpx 32rpx;
    margin: 0 24rpx 16rpx;
    display: flex;
    align-items: flex-start;
    gap: 16rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
  }

  &__address-icon {
    flex-shrink: 0;
    margin-top: 4rpx;
  }

  &__address-info {
    flex: 1;
    min-width: 0;
  }

  &__address-row {
    display: flex;
    align-items: center;
    gap: 20rpx;
    margin-bottom: 8rpx;
  }

  &__address-name {
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__address-mobile {
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
  }

  &__address-full {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
    line-height: 1.6;
  }

  &__timeline-card {
    background: var(--color-white, #ffffff);
    border-radius: 16rpx;
    margin: 0 24rpx 16rpx;
    padding-bottom: 16rpx;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
  }

  &__timeline-header {
    padding: 28rpx 32rpx 16rpx;
    border-bottom: 1rpx solid var(--color-border, #{$border-color});
    margin-bottom: 8rpx;
  }

  &__timeline-title {
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});

    &::before {
      content: '';
      display: inline-block;
      width: 6rpx;
      height: 32rpx;
      background: var(--color-primary, #{$primary-color});
      border-radius: 3rpx;
      margin-right: 12rpx;
      vertical-align: middle;
    }
  }

  &__timeline {
    padding: 16rpx 32rpx;
  }

  &__track-item {
    display: flex;
    align-items: flex-start;
    gap: 20rpx;
  }

  &__track-left {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 24rpx;
  }

  &__track-dot {
    width: 16rpx;
    height: 16rpx;
    border-radius: 50%;
    background: var(--color-border, #d1d5db);
    margin-top: 6rpx;
    flex-shrink: 0;

    &--latest {
      width: 24rpx;
      height: 24rpx;
      background: var(--color-primary, #{$primary-color});
      margin-top: 2rpx;
    }
  }

  &__track-line {
    width: 2rpx;
    flex: 1;
    min-height: 40rpx;
    background: var(--color-border, #e5e7eb);
    margin: 6rpx 0;
  }

  &__track-content {
    flex: 1;
    min-width: 0;
    padding-bottom: 32rpx;
  }

  &__track-time {
    display: block;
    font-size: 22rpx;
    color: var(--color-text-2, #{$text-color-secondary});
    margin-bottom: 6rpx;
  }

  &__track-desc {
    display: block;
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.6;

    .logistics__track-item--latest & {
      color: var(--color-primary, #{$primary-color});
      font-weight: 500;
    }
  }

  &__no-track {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60rpx 32rpx;
  }

  &__no-track-icon {
    margin-bottom: 20rpx;
  }

  &__no-track-text {
    font-size: 28rpx;
    color: var(--color-text-2, #{$text-color-secondary});
    margin-bottom: 10rpx;
  }

  &__no-track-hint {
    font-size: 24rpx;
    color: var(--color-text-3, #d1d5db);
  }
}

/* ===== Pickup section ===== */
.pickup-section {
  padding: 24rpx;
  display: flex;
  flex-direction: column;
  gap: 24rpx;

  &__timeline {
    background: var(--color-white, #ffffff);
    border-radius: 16rpx;
    padding: 32rpx 40rpx;
    display: flex;
    align-items: flex-start;
    gap: 0;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
  }
}

.timeline__node {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;

  &:not(:last-child) {
    /* The connecting line grows from the dot rightwards */
    .timeline__line {
      display: block;
    }
  }

  &:last-child {
    .timeline__line {
      display: none;
    }
  }
}

.timeline__dot {
  width: 24rpx;
  height: 24rpx;
  border-radius: 50%;
  background: var(--color-border, #d1d5db);
  border: 4rpx solid var(--color-border, #e5e7eb);
  flex-shrink: 0;
  z-index: 1;

  .timeline__node.is-done & {
    background: var(--color-primary, #{$primary-color});
    border-color: var(--color-primary, #{$primary-color});
  }

  .timeline__node.is-active & {
    background: var(--color-white, #ffffff);
    border-color: var(--color-primary, #{$primary-color});
    box-shadow: 0 0 0 4rpx rgba(41, 121, 255, 0.2);
  }
}

.timeline__line {
  position: absolute;
  top: 12rpx;
  left: 50%;
  width: 100%;
  height: 4rpx;
  background: var(--color-border, #e5e7eb);
  z-index: 0;

  .timeline__node.is-done & {
    background: var(--color-primary, #{$primary-color});
  }
}

.timeline__label {
  font-size: 22rpx;
  color: var(--color-text-2, #{$text-color-secondary});
  margin-top: 12rpx;
  text-align: center;

  .timeline__node.is-done &,
  .timeline__node.is-active & {
    color: var(--color-primary, #{$primary-color});
    font-weight: 500;
  }
}
</style>
