<template>
  <view class="order-detail">

    <!-- Loading -->
    <view v-if="loading" class="order-detail__loading">
      <u-loading-icon size="80rpx" color="var(--color-primary, #2979ff)" />
    </view>

    <!-- Not found -->
    <d-empty v-else-if="!order" text="订单不存在" />

    <template v-else>
      <scroll-view scroll-y class="order-detail__scroll">

        <!-- ===== Status banner ===== -->
        <view class="order-detail__status-card">
          <view class="order-detail__status-row">
            <text class="order-detail__status-text" :class="statusClass">{{ statusText }}</text>
            <text class="order-detail__order-no">{{ order.order_no }}</text>
          </view>

          <!-- Step progress (only for non-cancelled orders) -->
          <view v-if="order.status !== 'cancelled' && order.status !== 'closed'" class="order-detail__steps">
            <template v-for="(step, idx) in steps" :key="step.status">
              <view class="order-detail__step">
                <view
                  class="order-detail__step-dot"
                  :class="{ 'order-detail__step-dot--done': isStepDone(step.status) }"
                >
                  <text class="order-detail__step-num">{{ idx + 1 }}</text>
                </view>
                <text
                  class="order-detail__step-label"
                  :class="{ 'order-detail__step-label--done': isStepDone(step.status) }"
                >
                  {{ step.label }}
                </text>
              </view>
              <view
                v-if="idx < steps.length - 1"
                class="order-detail__step-line"
                :class="{ 'order-detail__step-line--done': isStepLineActive(idx) }"
              />
            </template>
          </view>
        </view>

        <!-- ===== Self-pickup banner ===== -->
        <view v-if="order.delivery_type === 'pickup'" class="order-detail__section order-detail__pickup-section">
          <!-- Pickup steps: 已下单 → 待自提 → 已自提 -->
          <view class="order-detail__pickup-steps">
            <view class="order-detail__pickup-step">
              <view
                class="order-detail__pickup-dot"
                :class="{ 'order-detail__pickup-dot--active': true }"
              />
              <text class="order-detail__pickup-step-label order-detail__pickup-step-label--active">已下单</text>
            </view>
            <view
              class="order-detail__pickup-line"
              :class="{ 'order-detail__pickup-line--active': order.status !== 'pending' && order.status !== 'cancelled' && order.status !== 'closed' }"
            />
            <view class="order-detail__pickup-step">
              <view
                class="order-detail__pickup-dot"
                :class="{ 'order-detail__pickup-dot--active': order.pickup_status === 'pending' || order.pickup_status === 'picked' }"
              />
              <text
                class="order-detail__pickup-step-label"
                :class="{ 'order-detail__pickup-step-label--active': order.pickup_status === 'pending' || order.pickup_status === 'picked' }"
              >待自提</text>
            </view>
            <view
              class="order-detail__pickup-line"
              :class="{ 'order-detail__pickup-line--active': order.pickup_status === 'picked' }"
            />
            <view class="order-detail__pickup-step">
              <view
                class="order-detail__pickup-dot"
                :class="{ 'order-detail__pickup-dot--active': order.pickup_status === 'picked' }"
              />
              <text
                class="order-detail__pickup-step-label"
                :class="{ 'order-detail__pickup-step-label--active': order.pickup_status === 'picked' }"
              >已自提</text>
            </view>
          </view>

          <!-- Pickup code banner (shown when order is paid and awaiting pickup) -->
          <d-pickup-banner
            v-if="order.pickup_code && order.pickup_status === 'pending' && pickupStoreInfo"
            :code="order.pickup_code"
            :store="pickupStoreInfo"
          />
        </view>

        <!-- ===== Address (hidden for pickup orders) ===== -->
        <view v-if="order.address_snapshot && order.delivery_type !== 'pickup'" class="order-detail__section">
          <view class="order-detail__section-header">
            <text class="order-detail__section-title">收货地址</text>
          </view>
          <view class="order-detail__address">
            <view class="order-detail__address-row">
              <text class="order-detail__address-name">{{ order.address_snapshot.name }}</text>
              <text class="order-detail__address-mobile">{{ order.address_snapshot.phone || order.address_snapshot.mobile }}</text>
            </view>
            <text class="order-detail__address-full">
              {{ order.address_snapshot.province }}{{ order.address_snapshot.city }}{{ order.address_snapshot.district }}{{ order.address_snapshot.detail }}
            </text>
          </view>
        </view>

        <!-- ===== Goods list ===== -->
        <view class="order-detail__section">
          <view class="order-detail__section-header">
            <text class="order-detail__section-title">商品信息</text>
            <view
              v-if="orderRefunds.length > 0"
              class="order-detail__link"
              @tap="goRefundList"
            >
              <text>查看售后</text>
              <u-icon name="arrow-right" size="24rpx" color="var(--color-primary, #2979ff)" />
            </view>
          </view>
          <view
            v-for="item in order.items"
            :key="item.id"
            class="order-detail__goods-item"
          >
            <image
              class="order-detail__goods-cover"
              :src="getImageUrl(item.goods_image)"
              mode="aspectFill"
              lazy-load
            />
            <view class="order-detail__goods-info">
              <text class="order-detail__goods-name">{{ item.goods_name }}</text>
              <text v-if="item.spec_text" class="order-detail__goods-spec">{{ item.spec_text }}</text>
              <view class="order-detail__goods-bottom">
                <text class="order-detail__goods-price">¥{{ formatPrice(item.price) }}</text>
                <text class="order-detail__goods-qty">× {{ item.quantity }}</text>
              </view>
            </view>
            <view class="order-detail__goods-actions">
              <view
                v-if="canApplyRefund"
                class="order-detail__link"
                @tap="handleRefund(item)"
              >
                <text>退款</text>
              </view>
              <view
                v-if="order.status === 'completed'"
                class="order-detail__link"
                @tap="handleReview(item)"
              >
                <text>评价</text>
              </view>
            </view>
          </view>
        </view>

        <!-- ===== Amount breakdown ===== -->
        <view class="order-detail__section">
          <view class="order-detail__section-header">
            <text class="order-detail__section-title">费用明细</text>
          </view>
          <view class="order-detail__amount-list">
            <view class="order-detail__amount-row">
              <text class="order-detail__amount-label">商品金额</text>
              <text class="order-detail__amount-value">¥{{ formatPrice(order.goods_amount) }}</text>
            </view>
            <view class="order-detail__amount-row">
              <text class="order-detail__amount-label">运费</text>
              <text class="order-detail__amount-value">¥0.00</text>
            </view>
            <view class="order-detail__amount-row order-detail__amount-row--total">
              <text class="order-detail__amount-label">实付金额</text>
              <text class="order-detail__amount-value order-detail__amount-value--primary">¥{{ formatPrice(order.pay_amount) }}</text>
            </view>
          </view>
        </view>

        <!-- ===== 同城配送 进度（merchant 单专用） ===== -->
        <view v-if="order.delivery_type === 'merchant'" class="order-detail__section">
          <view class="order-detail__section-header">
            <text class="order-detail__section-title">配送进度</text>
            <text class="order-detail__merchant-status" :class="`order-detail__merchant-status--${merchantStatus}`">
              {{ merchantStatusText }}
            </text>
          </view>
          <view class="order-detail__merchant">
            <view class="order-detail__merchant-timeline">
              <view v-for="(step, idx) in merchantSteps" :key="step.key" class="order-detail__merchant-step">
                <view class="order-detail__merchant-dot" :class="{ 'order-detail__merchant-dot--active': step.active }" />
                <text class="order-detail__merchant-step-label" :class="{ 'order-detail__merchant-step-label--active': step.active }">
                  {{ step.label }}
                </text>
                <view v-if="idx < merchantSteps.length - 1" class="order-detail__merchant-line" :class="{ 'order-detail__merchant-line--active': merchantSteps[idx + 1].active }" />
              </view>
            </view>
            <view v-if="order.delivery_order?.staff" class="order-detail__merchant-staff">
              <text class="order-detail__merchant-staff-label">骑手</text>
              <text class="order-detail__merchant-staff-value">
                {{ order.delivery_order.staff.name }}
                <text v-if="order.delivery_order.staff.phone" class="order-detail__merchant-staff-phone" @tap="callStaff">
                  · {{ order.delivery_order.staff.phone }}
                </text>
              </text>
            </view>
          </view>
        </view>

        <!-- ===== Logistics info（仅快递订单） ===== -->
        <view v-if="hasLogistics && order.delivery_type !== 'merchant'" class="order-detail__section">
          <view class="order-detail__section-header">
            <text class="order-detail__section-title">物流信息</text>
            <view class="order-detail__link" @tap="goLogistics">
              <text>查看详情</text>
              <u-icon name="arrow-right" size="24rpx" color="var(--color-primary, #2979ff)" />
            </view>
          </view>
          <view class="order-detail__logistics">
            <view class="order-detail__logistics-row">
              <text class="order-detail__logistics-label">快递公司</text>
              <text class="order-detail__logistics-value">{{ (order as any).express_company }}</text>
            </view>
            <view class="order-detail__logistics-row">
              <text class="order-detail__logistics-label">快递单号</text>
              <text class="order-detail__logistics-value" @tap="copyTrackNo">{{ (order as any).express_no }}</text>
            </view>
          </view>
        </view>

        <!-- ===== Order info ===== -->
        <view class="order-detail__section">
          <view class="order-detail__section-header">
            <text class="order-detail__section-title">订单信息</text>
          </view>
          <view class="order-detail__info-list">
            <view class="order-detail__info-row">
              <text class="order-detail__info-label">订单编号</text>
              <text class="order-detail__info-value">{{ order.order_no }}</text>
            </view>
            <view class="order-detail__info-row">
              <text class="order-detail__info-label">下单时间</text>
              <text class="order-detail__info-value">{{ order.created_at?.substring(0, 16) }}</text>
            </view>
            <view v-if="order.paid_at" class="order-detail__info-row">
              <text class="order-detail__info-label">付款时间</text>
              <text class="order-detail__info-value">{{ order.paid_at?.substring(0, 16) }}</text>
            </view>
            <view v-if="order.buyer_remark" class="order-detail__info-row">
              <text class="order-detail__info-label">买家备注</text>
              <text class="order-detail__info-value">{{ order.buyer_remark }}</text>
            </view>
          </view>
        </view>

        <view style="height: 120rpx;" />
      </scroll-view>

      <!-- ===== Bottom action bar ===== -->
      <view class="order-detail__bottom-bar">
        <view class="order-detail__bottom-inner">
          <template v-if="order.status === 'pending'">
            <view
              class="order-detail__action-btn order-detail__action-btn--outline"
              @tap="showCancelSheet"
            >
              <text>取消订单</text>
            </view>
            <view
              class="order-detail__action-btn order-detail__action-btn--primary"
              @tap="handlePay"
            >
              <text>立即付款</text>
            </view>
          </template>
          <template v-else-if="order.status === 'shipped'">
            <view
              class="order-detail__action-btn order-detail__action-btn--primary"
              @tap="handleConfirm"
            >
              <text>确认收货</text>
            </view>
          </template>
        </view>
      </view>
    </template>

    <!-- Cancel action sheet -->
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
import { ref, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { orderApi, type OrderItem, type OrderGoodsItem, type RefundItem } from '@/api/order'
import { storeApi, type Store } from '@/api/store'
import type { PayChannel } from '@/api/payment'
import { usePayment } from '@/composables/usePayment'

const { pay } = usePayment()
import { useAppStore } from '@/store/app.store'

const appStore = useAppStore()

// ---- state ----
const order = ref<OrderItem | null>(null)
const loading = ref(true)
const pickupStoreInfo = ref<Store | null>(null)
// 本订单的售后记录（进行中/已完成），用于「查看售后」入口展示
const orderRefunds = ref<RefundItem[]>([])

// ---- status ----（与后端 OrderOrder 状态枚举对齐）
const STATUS_TEXT: Record<string, string> = {
  pending:   '待付款',
  paid:      '待发货',
  shipped:   '待收货',
  completed: '已完成',
  cancelled: '已取消',
  closed:    '已关闭',
}

const STATUS_CLASS: Record<string, string> = {
  pending:   'status--warning',
  paid:      'status--primary',
  shipped:   'status--info',
  completed: 'status--success',
  cancelled: 'status--danger',
  closed:    'status--default',
}

const statusText = computed(() => STATUS_TEXT[order.value?.status ?? ''] ?? '未知')
const statusClass = computed(() => STATUS_CLASS[order.value?.status ?? ''] ?? 'status--default')

// ---- steps ----
const STATUS_ORDER: Record<string, number> = {
  pending: 1, paid: 2, shipped: 3, completed: 4,
}

const steps = [
  { status: 'pending',   label: '待付款' },
  { status: 'paid',      label: '待发货' },
  { status: 'shipped',   label: '待收货' },
  { status: 'completed', label: '已完成' },
]

function isStepDone(stepStatus: string): boolean {
  const s = order.value?.status
  if (!s || s === 'cancelled' || s === 'closed') return false
  return (STATUS_ORDER[s] ?? 0) >= (STATUS_ORDER[stepStatus] ?? 0)
}

function isStepLineActive(idx: number): boolean {
  return isStepDone(steps[idx + 1]?.status ?? '__none')
}

// ---- computed ----
const canApplyRefund = computed(() => {
  const s = order.value?.status
  return s === 'paid' || s === 'shipped' || s === 'completed'
})

const hasLogistics = computed(() => {
  const s = order.value?.status
  return order.value && (s === 'shipped' || s === 'completed') && (order.value as any).express_company
})

// 同城配送进度：4 步时间轴，依据 delivery_order.status 推进
const MERCHANT_STATUS_TEXT: Record<string, string> = {
  pending:   '待派单',
  assigned:  '已派单',
  picking:   '配送中',
  completed: '已送达',
  cancelled: '已取消',
}
const merchantStatus = computed(() => order.value?.delivery_order?.status ?? 'pending')
const merchantStatusText = computed(() => MERCHANT_STATUS_TEXT[merchantStatus.value] ?? '—')

const merchantSteps = computed(() => {
  const rank: Record<string, number> = { pending: 0, assigned: 1, picking: 2, completed: 3 }
  const cur = rank[merchantStatus.value] ?? 0
  return [
    { key: 'pending',   label: '待派单',   active: cur >= 0 },
    { key: 'assigned',  label: '已派单',   active: cur >= 1 },
    { key: 'picking',   label: '配送中',   active: cur >= 2 },
    { key: 'completed', label: '已送达',   active: cur >= 3 },
  ]
})

function callStaff() {
  const phone = order.value?.delivery_order?.staff?.phone
  if (phone) uni.makePhoneCall({ phoneNumber: phone })
}

// ---- pickup store ----
watch(() => order.value?.pickup_store_id, async (id) => {
  if (id && order.value?.delivery_type === 'pickup') {
    try {
      pickupStoreInfo.value = await storeApi.detail(id)
    } catch {
      // silent fail — banner simply hidden
    }
  }
}, { immediate: true })

// ---- helpers ----
function getImageUrl(url: string): string {
  if (!url) return ''
  return appStore.getImageUrl(url)
}

// 后端 price/pay_amount 是 decimal 字符串（元），不再除 100
function formatPrice(val: string | number | undefined): string {
  if (val == null) return '0.00'
  return Number(val).toFixed(2)
}

function copyTrackNo() {
  const no = (order.value as any)?.express_no
  if (!no) return
  uni.setClipboardData({ data: no, success: () => uni.showToast({ title: '已复制', icon: 'success' }) })
}

// ---- fetch ----
async function fetchOrder(id: string) {
  loading.value = true
  try {
    order.value = await orderApi.getOrderDetail(id)
    loadRefunds(id)
  } catch {
    order.value = null
  } finally {
    loading.value = false
  }
}

// 查询本订单的售后记录（接口失败时静默隐藏入口；order_id 参数后端未支持时前端二次过滤）
async function loadRefunds(orderId: string | number) {
  try {
    const res = await orderApi.getRefundList({ page: 1, limit: 50, order_id: orderId })
    orderRefunds.value = (res.list ?? []).filter(r => String(r.order_id) === String(orderId))
  } catch {
    orderRefunds.value = []
  }
}

// ---- pay popup ----
const payPopupVisible = ref(false)
const paying = ref(false)
const payAmountFen = computed(() => Math.round(Number(order.value?.pay_amount ?? 0) * 100))

function handlePay() {
  if (!order.value) return
  payPopupVisible.value = true
}

async function onPayConfirm(channel: PayChannel) {
  if (!order.value) return
  const orderNo = order.value.order_no
  paying.value = true
  try {
    const ok = await pay({ order_no: orderNo, channel })
    payPopupVisible.value = false
    if (ok) {
      uni.navigateTo({
        url: `/modules/payment/pages/pay-result?order_no=${orderNo}&status=success`,
      })
    }
  } finally {
    paying.value = false
  }
}

async function handleConfirm() {
  uni.showModal({
    title: '确认收货',
    content: '确认已收到商品？',
    success: async (res) => {
      if (!res.confirm || !order.value) return
      try {
        await orderApi.confirmReceive(order.value.id)
        uni.showToast({ title: '确认收货成功', icon: 'success' })
        await fetchOrder(String(order.value.id))
      } catch {
        // handled
      }
    },
  })
}

function handleRefund(item: OrderGoodsItem) {
  if (!order.value) return
  uni.navigateTo({ url: `/modules/order/pages/refund?order_id=${order.value.id}&goods_id=${item.id}` })
}

function handleReview(item: OrderGoodsItem) {
  if (!order.value) return
  uni.navigateTo({ url: `/modules/order/pages/review?order_id=${order.value.id}&goods_id=${item.id}` })
}

function goLogistics() {
  if (!order.value) return
  uni.navigateTo({ url: `/modules/order/pages/logistics?id=${order.value.id}` })
}

function goRefundList() {
  if (!order.value) return
  uni.navigateTo({ url: `/modules/order/pages/refund-list?order_id=${order.value.id}` })
}

// ---- cancel ----
const cancelSheetVisible = ref(false)
const cancelReasons = ['暂时不想买了', '商品信息填写有误', '重复下单', '不想要了', '其他原因']
const cancelActions = cancelReasons.map(r => ({ name: r }))

function showCancelSheet() {
  cancelSheetVisible.value = true
}

async function onCancelReasonSelect(item: { name: string }) {
  cancelSheetVisible.value = false
  if (!order.value) return
  try {
    await orderApi.cancelOrder(order.value.id, { reason: item.name })
    uni.showToast({ title: '订单已取消', icon: 'success' })
    await fetchOrder(String(order.value.id))
  } catch {
    // handled
  }
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

.order-detail {
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

  &__status-card {
    background: var(--color-primary, #{$primary-color});
    padding: 40rpx 32rpx 36rpx;
    margin-bottom: 16rpx;
  }

  &__status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32rpx;
  }

  &__status-text {
    font-size: 36rpx;
    font-weight: 600;
    color: #ffffff;

    &.status--warning { color: #fffbe6; }
    &.status--primary { color: #ffffff; }
    &.status--info { color: #e0f2fe; }
    &.status--success { color: #dcfce7; }
    &.status--danger { color: #fee2e2; }
    &.status--default { color: rgba(255,255,255,0.7); }
  }

  &__order-no {
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.7);
  }

  &__steps {
    display: flex;
    align-items: center;
  }

  &__step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
  }

  &__step-dot {
    width: 48rpx;
    height: 48rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8rpx;

    &--done {
      background: #ffffff;
    }
  }

  &__step-num {
    font-size: 24rpx;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);

    .order-detail__step-dot--done & {
      color: var(--color-primary, #{$primary-color});
    }
  }

  &__step-label {
    font-size: 20rpx;
    color: rgba(255, 255, 255, 0.6);
    white-space: nowrap;

    &--done {
      color: #ffffff;
      font-weight: 500;
    }
  }

  &__step-line {
    flex: 1;
    height: 2rpx;
    background: rgba(255, 255, 255, 0.3);
    margin: 0 8rpx;
    margin-bottom: 32rpx;

    &--done {
      background: rgba(255, 255, 255, 0.9);
    }
  }

  &__section {
    background: var(--color-bg-white, #ffffff);
    margin-bottom: 16rpx;
  }

  &__section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28rpx 32rpx 0;
  }

  &__section-title {
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

  &__link {
    display: flex;
    align-items: center;
    gap: 4rpx;

    text {
      font-size: 24rpx;
      color: var(--color-primary, #{$primary-color});
    }
  }

  &__address {
    padding: 20rpx 32rpx 28rpx;
  }

  &__address-row {
    display: flex;
    align-items: center;
    gap: 24rpx;
    margin-bottom: 8rpx;
  }

  &__address-name {
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__address-mobile {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
  }

  &__address-full {
    font-size: 26rpx;
    color: $text-color-secondary;
    line-height: 1.6;
  }

  &__goods-item {
    display: flex;
    align-items: flex-start;
    gap: 20rpx;
    padding: 24rpx 32rpx;
    border-bottom: 1rpx solid $border-color;

    &:last-child {
      border-bottom: none;
    }
  }

  &__goods-cover {
    width: 140rpx;
    height: 140rpx;
    border-radius: var(--radius-sm, 8rpx);
    flex-shrink: 0;
    background: var(--color-bg, #{$bg-color});
  }

  &__goods-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
  }

  &__goods-name {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__goods-spec {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__goods-bottom {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-top: auto;
  }

  &__goods-price {
    font-size: 28rpx;
    color: $danger-color;
    font-weight: 500;
  }

  &__goods-qty {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__goods-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12rpx;
    flex-shrink: 0;

    .order-detail__link {
      text {
        font-size: 22rpx;
      }
    }
  }

  &__amount-list {
    padding: 16rpx 32rpx 28rpx;
  }

  &__amount-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10rpx 0;

    &--total {
      border-top: 1rpx solid $border-color;
      margin-top: 8rpx;
      padding-top: 18rpx;
    }
  }

  &__amount-label {
    font-size: 28rpx;
    color: $text-color-secondary;

    .order-detail__amount-row--total & {
      color: var(--color-text, #{$text-color});
      font-weight: 500;
    }
  }

  &__amount-value {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});

    &--primary {
      font-size: 34rpx;
      font-weight: 700;
      color: $danger-color;
    }
  }

  &__logistics {
    padding: 20rpx 32rpx 28rpx;
  }

  &__logistics-row {
    display: flex;
    align-items: center;
    padding: 10rpx 0;
  }

  &__logistics-label {
    font-size: 26rpx;
    color: $text-color-secondary;
    width: 140rpx;
    flex-shrink: 0;
  }

  &__logistics-value {
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    flex: 1;
  }

  &__info-list {
    padding: 16rpx 32rpx 28rpx;
  }

  &__info-row {
    display: flex;
    align-items: center;
    padding: 10rpx 0;
    border-bottom: 1rpx solid $border-color;

    &:last-child {
      border-bottom: none;
    }
  }

  &__info-label {
    font-size: 26rpx;
    color: $text-color-secondary;
    width: 160rpx;
    flex-shrink: 0;
  }

  &__info-value {
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    flex: 1;
    word-break: break-all;
  }

  &__bottom-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--color-bg-white, #ffffff);
    border-top: 1rpx solid $border-color;
    z-index: 99;
    padding-bottom: constant(safe-area-inset-bottom);
    padding-bottom: env(safe-area-inset-bottom);
  }

  &__bottom-inner {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 20rpx;
    padding: 16rpx 28rpx;
  }

  &__action-btn {
    height: 72rpx;
    padding: 0 40rpx;
    border-radius: 36rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28rpx;

    &--outline {
      border: 1rpx solid $border-color;

      text {
        color: var(--color-text, #{$text-color});
      }
    }

    &--primary {
      background: var(--color-primary, #{$primary-color});

      text {
        color: #ffffff;
        font-weight: 500;
      }
    }
  }

  // ===== Pickup section =====
  &__pickup-section {
    padding: 28rpx 32rpx 32rpx;
  }

  &__pickup-steps {
    display: flex;
    align-items: center;
    margin-bottom: 28rpx;
  }

  &__pickup-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    gap: 8rpx;
  }

  &__pickup-dot {
    width: 20rpx;
    height: 20rpx;
    border-radius: 50%;
    background: $border-color;

    &--active {
      background: var(--color-primary, #{$primary-color});
    }
  }

  &__pickup-step-label {
    font-size: 22rpx;
    color: $text-color-secondary;
    white-space: nowrap;

    &--active {
      color: var(--color-primary, #{$primary-color});
      font-weight: 500;
    }
  }

  &__pickup-line {
    flex: 1;
    height: 2rpx;
    background: $border-color;
    margin: 0 12rpx;
    margin-bottom: 30rpx;

    &--active {
      background: var(--color-primary, #{$primary-color});
    }
  }
}

/* ── 同城配送 进度时间轴 ── */
.order-detail__merchant-status {
  font-size: 26rpx;
  color: var(--color-text-secondary, #71717a);
  &--assigned, &--picking { color: var(--color-warning, #ff9900); }
  &--completed { color: var(--color-success, #19be6b); }
  &--cancelled { color: var(--color-text-tertiary, #aaa); }
}

.order-detail__merchant {
  padding: 24rpx 0 0;
}

.order-detail__merchant-timeline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20rpx 24rpx 28rpx;
}

.order-detail__merchant-step {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  min-width: 100rpx;
}

.order-detail__merchant-dot {
  width: 24rpx;
  height: 24rpx;
  border-radius: 50%;
  background: #e0e0e0;
  margin-bottom: 12rpx;

  &--active {
    background: var(--color-primary, #{$primary-color});
    box-shadow: 0 0 0 6rpx rgba(41, 121, 255, 0.15);
  }
}

.order-detail__merchant-step-label {
  font-size: 24rpx;
  color: var(--color-text-tertiary, #aaa);
  &--active {
    color: var(--color-primary, #{$primary-color});
    font-weight: 500;
  }
}

.order-detail__merchant-line {
  position: absolute;
  top: 12rpx;
  left: 60%;
  width: 80%;
  height: 4rpx;
  background: #e0e0e0;
  z-index: -1;
  &--active {
    background: var(--color-primary, #{$primary-color});
  }
}

.order-detail__merchant-staff {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 20rpx 24rpx;
  background: var(--color-bg, #{$bg-color});
  border-radius: 16rpx;
  margin: 0 24rpx 8rpx;
}

.order-detail__merchant-staff-label {
  font-size: 26rpx;
  color: var(--color-text-secondary, #71717a);
}

.order-detail__merchant-staff-value {
  flex: 1;
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
  font-weight: 500;
}

.order-detail__merchant-staff-phone {
  color: var(--color-primary, #{$primary-color});
  font-weight: normal;
}
</style>
