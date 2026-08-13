<template>
  <view class="order-list">

    <!-- Status tabs -->
    <view class="order-list__tabs">
      <scroll-view scroll-x class="order-list__tabs-scroll" enhanced>
        <view class="order-list__tabs-inner">
          <view
            v-for="tab in tabs"
            :key="tab.value"
            class="order-list__tab"
            :class="{ 'order-list__tab--active': activeTab === tab.value }"
            @tap="setTab(tab.value)"
          >
            <text class="order-list__tab-text">{{ tab.label }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- Order list with scroll -->
    <scroll-view
      scroll-y
      class="order-list__scroll"
      refresher-enabled
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <view class="order-list__content">

        <!-- Skeleton loading -->
        <view v-if="loading && list.length === 0" class="order-list__skeleton">
          <view v-for="i in 3" :key="i" class="order-list__skeleton-item" />
        </view>

        <!-- Order cards -->
        <template v-else-if="list.length > 0">
          <d-order-card
            v-for="order in list"
            :key="order.id"
            :order="order"
            @action="handleAction"
          />
        </template>

        <!-- List loader / empty -->
        <d-list-loader
          :loading="loading && list.length > 0"
          :finished="finished"
          :total="total"
          :empty-text="`暂无${activeTabLabel}订单`"
        />
      </view>
    </scroll-view>

    <!-- Cancel dialog -->
    <u-action-sheet
      :show="cancelSheetVisible"
      :actions="cancelActions"
      cancelText="取消"
      @select="onCancelReasonSelect"
      @close="cancelSheetVisible = false"
    />

    <!-- 支付方式选择 -->
    <d-payment-popup
      v-model="payPopupVisible"
      :amount="payAmountFen"
      :loading="paying"
      @pay="onPayConfirm"
    />

  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { orderApi, type OrderItem } from '@/api/order'
import { usePayment } from '@/composables/usePayment'
import type { PayChannel } from '@/api/payment'

const { pay } = usePayment()

const payPopupVisible = ref(false)
const paying = ref(false)
const payTargetOrder = ref<OrderItem | null>(null)
const payAmountFen = computed(() => Math.round(Number(payTargetOrder.value?.pay_amount ?? 0) * 100))

function handlePayOrder(order: OrderItem) {
  payTargetOrder.value = order
  payPopupVisible.value = true
}

async function onPayConfirm(channel: PayChannel) {
  const order = payTargetOrder.value
  if (!order) return
  paying.value = true
  try {
    const ok = await pay({ order_no: order.order_no, channel })
    payPopupVisible.value = false
    if (ok) {
      uni.navigateTo({
        url: `/modules/payment/pages/pay-result?order_no=${order.order_no}&status=success`,
      })
    }
  } finally {
    paying.value = false
  }
}

// ---- tabs ----（与后端 OrderOrder 状态枚举对齐；refunding 是虚拟状态，按 order_refunds 关联过滤）
const tabs = [
  { label: '全部', value: '' },
  { label: '待付款', value: 'pending' },
  { label: '待发货', value: 'paid' },
  { label: '待收货', value: 'shipped' },
  { label: '已完成', value: 'completed' },
  { label: '退款/售后', value: 'refunding' },
]

// my 页旧版传 number（0=全部 1=待付款 2=待发货 3=待收货 4=已完成 5=退款）的兼容映射
const STATUS_NUM_MAP: Record<string, string> = {
  '0': '',
  '1': 'pending',
  '2': 'paid',
  '3': 'shipped',
  '4': 'completed',
  '5': 'refunding',
}

const activeTab = ref('')
const activeTabLabel = computed(() => tabs.find(t => t.value === activeTab.value)?.label ?? '')

// ---- list state ----
const list = ref<OrderItem[]>([])
const loading = ref(false)
const refreshing = ref(false)
const finished = ref(false)
const total = ref(0)
const page = ref(1)
const PAGE_SIZE = 10

async function fetchList(reset = false) {
  if (loading.value && !reset) return
  if (finished.value && !reset) return

  loading.value = true
  if (reset) {
    page.value = 1
    list.value = []
    finished.value = false
  }

  try {
    const params: Record<string, any> = {
      page_no: page.value,
      page_size: PAGE_SIZE,
    }
    if (activeTab.value) params.status = activeTab.value

    const result = await orderApi.getOrderList(params)
    const newList = result.list ?? []
    total.value = result.pagination?.total ?? 0

    if (reset) {
      list.value = newList
    } else {
      list.value = [...list.value, ...newList]
    }

    const loaded = list.value.length
    finished.value = loaded >= total.value
    page.value += 1
  } catch {
    // handled by request layer
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

function setTab(value: string) {
  activeTab.value = value
  fetchList(true)
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

// ---- actions ----
const cancelSheetVisible = ref(false)
const cancelTargetOrder = ref<OrderItem | null>(null)

const cancelReasons = [
  '暂时不想买了',
  '商品信息填写有误',
  '重复下单',
  '不想要了',
  '其他原因',
]

const cancelActions = cancelReasons.map(r => ({ name: r }))

function handleAction(type: string, order: OrderItem) {
  if (type === 'detail') {
    uni.navigateTo({ url: `/modules/order/pages/detail?id=${order.id}` })
  } else if (type === 'pay') {
    handlePayOrder(order)
  } else if (type === 'cancel') {
    cancelTargetOrder.value = order
    cancelSheetVisible.value = true
  } else if (type === 'confirm') {
    handleConfirmReceive(order)
  } else if (type === 'review') {
    uni.navigateTo({ url: `/modules/order/pages/detail?id=${order.id}&action=review` })
  }
}

async function onCancelReasonSelect(item: { name: string }) {
  cancelSheetVisible.value = false
  if (!cancelTargetOrder.value) return
  try {
    await orderApi.cancelOrder(cancelTargetOrder.value.id, { reason: item.name })
    uni.showToast({ title: '订单已取消', icon: 'success' })
    fetchList(true)
  } catch {
    // handled
  }
}

async function handleConfirmReceive(order: OrderItem) {
  uni.showModal({
    title: '确认收货',
    content: '确认已收到商品？',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await orderApi.confirmReceive(order.id)
        uni.showToast({ title: '确认收货成功', icon: 'success' })
        fetchList(true)
      } catch {
        // handled
      }
    },
  })
}

// ---- lifecycle ----
onLoad((query: any) => {
  if (query?.status !== undefined && query.status !== '') {
    const raw = String(query.status)
    // 兼容老入口：先 number→string 映射，再校验
    const mapped = STATUS_NUM_MAP[raw] ?? raw
    if (tabs.some(t => t.value === mapped)) {
      activeTab.value = mapped
    }
  }
  fetchList(true)
})

onShow(() => {
  // Refresh when coming back from detail page
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.order-list {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});
  display: flex;
  flex-direction: column;

  &__tabs {
    background: var(--color-bg-white, #ffffff);
    border-bottom: 1rpx solid $border-color;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  &__tabs-scroll {
    white-space: nowrap;
  }

  &__tabs-inner {
    display: flex;
    padding: 0 8rpx;
  }

  &__tab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 24rpx 28rpx;
    border-bottom: 4rpx solid transparent;
    flex-shrink: 0;

    &--active {
      border-bottom-color: var(--color-primary, #{$primary-color});

      .order-list__tab-text {
        color: var(--color-primary, #{$primary-color});
        font-weight: 500;
      }
    }
  }

  &__tab-text {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    white-space: nowrap;
  }

  &__scroll {
    flex: 1;
    height: calc(100vh - 96rpx);
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
    height: 240rpx;
    background: var(--color-bg-white, #ffffff);
    border-radius: var(--radius-lg, 16rpx);
    animation: pulse 1.5s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }
}
</style>
