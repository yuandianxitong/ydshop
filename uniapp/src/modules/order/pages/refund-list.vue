<template>
  <view class="refund-list">

    <scroll-view
      scroll-y
      class="refund-list__scroll"
      refresher-enabled
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <view class="refund-list__content">

        <!-- Skeleton loading -->
        <view v-if="loading && list.length === 0" class="refund-list__skeleton">
          <view v-for="i in 3" :key="i" class="refund-list__skeleton-item" />
        </view>

        <!-- Refund cards -->
        <template v-else-if="list.length > 0">
          <view
            v-for="item in list"
            :key="item.id"
            class="refund-card"
            @tap="goDetail(item)"
          >
            <view class="refund-card__header">
              <text class="refund-card__order-no">订单号：{{ orderNoOf(item) }}</text>
              <text class="refund-card__status" :class="`refund-card__status--${statusTone(item.status)}`">
                {{ statusText(item.status) }}
              </text>
            </view>

            <view class="refund-card__body">
              <image
                class="refund-card__cover"
                :src="appStore.getImageUrl(goodsOf(item).goods_image || '')"
                mode="aspectFill"
                lazy-load
              />
              <view class="refund-card__info">
                <text class="refund-card__name">{{ goodsOf(item).goods_name || '商品信息' }}</text>
                <text v-if="goodsOf(item).spec_text" class="refund-card__spec">{{ goodsOf(item).spec_text }}</text>
                <text class="refund-card__type">{{ item.type === 'refund_only' ? '仅退款' : '退货退款' }}</text>
              </view>
            </view>

            <view class="refund-card__footer">
              <text class="refund-card__time">{{ item.created_at?.substring(0, 16) }}</text>
              <view class="refund-card__amount-wrap">
                <text class="refund-card__amount-label">退款金额</text>
                <text class="refund-card__amount">¥{{ formatPrice(item.refund_amount) }}</text>
              </view>
            </view>
          </view>
        </template>

        <!-- List loader / empty -->
        <d-list-loader
          :loading="loading && list.length > 0"
          :finished="finished"
          :total="displayTotal"
          empty-text="暂无售后记录"
        />
      </view>
    </scroll-view>

  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { orderApi, type RefundItem, type RefundStatus, type OrderItemRow } from '@/api/order'
import { useAppStore } from '@/store/app.store'

const appStore = useAppStore()

// ---- status maps ----
const STATUS_TEXT: Record<RefundStatus, string> = {
  pending: '待审核',
  approved: '待退货',
  returning: '退货中',
  received: '已收货',
  refunding: '退款中',
  retryable_failed: '退款异常',
  manual_review: '人工处理中',
  refunded: '已退款',
  rejected: '已拒绝',
}

const STATUS_TONE: Record<RefundStatus, string> = {
  pending: 'warning',
  approved: 'primary',
  returning: 'primary',
  received: 'primary',
  refunding: 'primary',
  retryable_failed: 'danger',
  manual_review: 'warning',
  refunded: 'success',
  rejected: 'default',
}

function statusText(status: RefundStatus): string {
  return STATUS_TEXT[status] ?? '未知状态'
}

function statusTone(status: RefundStatus): string {
  return STATUS_TONE[status] ?? 'default'
}

// ---- helpers ----
// 商品信息优先取关联 order_item（后端 with(['orderItem'])），兼容平铺字段
function goodsOf(item: RefundItem): Partial<OrderItemRow> {
  return item.order_item ?? (item as any)
}

function orderNoOf(item: RefundItem): string {
  return item.order_no || item.order?.order_no || '-'
}

function formatPrice(val: string | number | undefined): string {
  if (val == null) return '0.00'
  return Number(val).toFixed(2)
}

// ---- list state ----
const list = ref<RefundItem[]>([])
const loading = ref(false)
const refreshing = ref(false)
const finished = ref(false)
const total = ref(0)
const page = ref(1)
const PAGE_SIZE = 10

// 详情页入口带 order_id 时按订单过滤（后端未支持该参数时前端二次过滤兜底）
let orderIdFilter = ''
// 后端忽略 order_id 参数时，前端过滤会让 list.length < total，
// finished 判断需按原始已加载条数计算
let rawLoaded = 0

const displayTotal = computed(() => (orderIdFilter ? list.value.length : total.value))

async function fetchList(reset = false) {
  if (loading.value && !reset) return
  if (finished.value && !reset) return

  loading.value = true
  if (reset) {
    page.value = 1
    list.value = []
    finished.value = false
    rawLoaded = 0
  }

  try {
    const params: Record<string, any> = {
      page: page.value,
      limit: PAGE_SIZE,
    }
    if (orderIdFilter) params.order_id = orderIdFilter

    const result = await orderApi.getRefundList(params)
    let newList = result.list ?? []
    total.value = result.pagination?.total ?? result.total ?? 0
    rawLoaded += newList.length

    if (orderIdFilter) {
      newList = newList.filter(i => String(i.order_id) === orderIdFilter)
    }

    list.value = reset ? newList : [...list.value, ...newList]
    finished.value = rawLoaded >= total.value
    page.value += 1
  } catch {
    // handled by request layer
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

async function onRefresh() {
  refreshing.value = true
  await fetchList(true)
}

function loadMore() {
  if (!finished.value && !loading.value) {
    fetchList()
  }
}

function goDetail(item: RefundItem) {
  uni.navigateTo({ url: `/modules/order/pages/refund-detail?id=${item.id}` })
}

// ---- lifecycle ----
let loadedOnce = false

onLoad((options: any) => {
  if (options?.order_id) {
    orderIdFilter = String(options.order_id)
  }
  loadedOnce = true
  fetchList(true)
})

onShow(() => {
  // 从详情页返回时刷新（填写物流后状态会变化）
  if (loadedOnce && list.value.length > 0) {
    fetchList(true)
  }
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.refund-list {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});
  display: flex;
  flex-direction: column;

  &__scroll {
    flex: 1;
    height: 100vh;
  }

  &__content {
    padding: 20rpx 20rpx 40rpx;
  }

  &__skeleton {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
  }

  &__skeleton-item {
    height: 280rpx;
    background: var(--color-bg-white, #ffffff);
    border-radius: var(--radius-lg, 16rpx);
    animation: pulse 1.5s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }
}

.refund-card {
  background: var(--color-bg-white, #ffffff);
  border-radius: var(--radius-lg, 16rpx);
  margin-bottom: 20rpx;
  padding: 24rpx 28rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20rpx;
    border-bottom: 1rpx solid var(--color-border, #{$border-color});
  }

  &__order-no {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-right: 16rpx;
  }

  &__status {
    font-size: 26rpx;
    font-weight: 500;
    flex-shrink: 0;

    &--warning { color: $warning-color; }
    &--primary { color: var(--color-primary, #{$primary-color}); }
    &--success { color: $success-color; }
    &--danger  { color: $danger-color; }
    &--default { color: var(--color-text-2, #{$text-color-secondary}); }
  }

  &__body {
    display: flex;
    align-items: flex-start;
    gap: 20rpx;
    padding: 24rpx 0;
  }

  &__cover {
    width: 140rpx;
    height: 140rpx;
    border-radius: var(--radius-sm, 8rpx);
    flex-shrink: 0;
    background: var(--color-bg, #{$bg-color});
  }

  &__info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
  }

  &__name {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__spec {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
  }

  &__type {
    font-size: 22rpx;
    color: var(--color-text-2, #{$text-color-secondary});
    background: var(--color-bg, #{$bg-color});
    border-radius: 8rpx;
    padding: 4rpx 12rpx;
    align-self: flex-start;
  }

  &__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 20rpx;
    border-top: 1rpx solid var(--color-border, #{$border-color});
  }

  &__time {
    font-size: 22rpx;
    color: var(--color-text-2, #{$text-color-secondary});
  }

  &__amount-wrap {
    display: flex;
    align-items: baseline;
    gap: 8rpx;
  }

  &__amount-label {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
  }

  &__amount {
    font-size: 30rpx;
    font-weight: 600;
    color: $danger-color;
  }
}
</style>
