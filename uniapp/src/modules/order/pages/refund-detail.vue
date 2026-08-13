<template>
  <d-page :safe-area="true">
    <view class="refund-detail">

      <!-- Loading -->
      <view v-if="loading" class="refund-detail__loading">
        <u-loading-icon size="80rpx" color="var(--color-primary, #2979ff)" />
      </view>

      <d-empty v-else-if="!refund" text="售后单不存在" />

      <template v-else>

        <!-- ===== Status card ===== -->
        <view class="status-card" :class="`status-card--${statusTone}`">
          <text class="status-card__title">{{ statusTitle }}</text>
          <text class="status-card__desc">{{ statusDesc }}</text>
        </view>

        <!-- ===== Rejected reason ===== -->
        <view v-if="refund.status === 'rejected' && refund.refuse_reason" class="section-card refuse-card">
          <view class="section-title">拒绝原因</view>
          <text class="refuse-card__text">{{ refund.refuse_reason }}</text>
        </view>

        <!-- ===== Goods info ===== -->
        <view class="section-card">
          <view class="section-title">商品信息</view>
          <view class="item-info">
            <image
              :src="appStore.getImageUrl(goods.goods_image || '')"
              mode="aspectFill"
              class="item-cover"
            />
            <view class="item-detail">
              <text class="item-name">{{ goods.goods_name || '商品信息' }}</text>
              <text v-if="goods.spec_text" class="item-spec">规格：{{ goods.spec_text }}</text>
              <view v-if="goods.price" class="item-price-row">
                <text class="item-price">¥{{ formatPrice(goods.price) }}</text>
                <text class="item-qty"> × {{ goods.quantity }}</text>
              </view>
            </view>
          </view>
        </view>

        <!-- ===== Refund info ===== -->
        <view class="section-card">
          <view class="section-title">退款信息</view>
          <view class="info-list">
            <view class="info-row">
              <text class="info-label">退款金额</text>
              <text class="info-value info-value--amount">¥{{ formatPrice(refund.refund_amount) }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">售后类型</text>
              <text class="info-value">{{ refund.type === 'refund_only' ? '仅退款' : '退货退款' }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">退款原因</text>
              <text class="info-value">{{ refund.reason || '-' }}</text>
            </view>
            <view v-if="refund.refund_no" class="info-row">
              <text class="info-label">售后单号</text>
              <text class="info-value">{{ refund.refund_no }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">订单号</text>
              <text class="info-value">{{ refund.order_no || refund.order?.order_no || '-' }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">申请时间</text>
              <text class="info-value">{{ refund.created_at?.substring(0, 16) }}</text>
            </view>
            <view v-if="refund.refund_trade_no" class="info-row">
              <text class="info-label">退款流水号</text>
              <text class="info-value">{{ refund.refund_trade_no }}</text>
            </view>
            <view v-if="refund.refunded_at" class="info-row">
              <text class="info-label">退款完成时间</text>
              <text class="info-value">{{ refund.refunded_at?.substring(0, 16) }}</text>
            </view>
          </view>
        </view>

        <!-- ===== Return logistics form（approved + 退货退款：待买家寄回） ===== -->
        <view v-if="needLogisticsForm" class="section-card">
          <view class="section-title">填写退货物流</view>
          <view class="form-field">
            <text class="form-label">快递公司</text>
            <input
              v-model="logisticsForm.company"
              class="form-input"
              placeholder="请输入快递公司名称"
              placeholder-class="input-placeholder"
              :maxlength="50"
            />
          </view>
          <view class="form-field">
            <text class="form-label">快递单号</text>
            <input
              v-model="logisticsForm.express_no"
              class="form-input"
              placeholder="请输入快递单号"
              placeholder-class="input-placeholder"
              :maxlength="50"
            />
          </view>
          <u-button
            :loading="submitting"
            :disabled="!canSubmitLogistics"
            :customStyle="{ width: '100%' }"
            class="submit-btn"
            @click="handleSubmitLogistics"
          >
            提交物流信息
          </u-button>
        </view>

        <!-- ===== Return logistics readonly（已寄回） ===== -->
        <view v-else-if="refund.return_express_no" class="section-card">
          <view class="section-title">退货物流</view>
          <view class="info-list">
            <view class="info-row">
              <text class="info-label">快递公司</text>
              <text class="info-value">{{ refund.return_express_company || '-' }}</text>
            </view>
            <view class="info-row">
              <text class="info-label">快递单号</text>
              <text class="info-value">{{ refund.return_express_no }}</text>
            </view>
          </view>
          <view v-if="refund.status === 'returning'" class="logistics-hint">
            <text>退货已寄出，等待商家确认收货</text>
          </view>
        </view>

      </template>
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { orderApi, type RefundItem, type RefundStatus, type OrderItemRow } from '@/api/order'
import { useAppStore } from '@/store/app.store'

const appStore = useAppStore()

// ---- state ----
const refund = ref<RefundItem | null>(null)
const loading = ref(true)
const submitting = ref(false)
let refundId = ''

const logisticsForm = reactive({
  company: '',
  express_no: '',
})

// ---- status ----
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

const isRefundOnly = computed(() => refund.value?.type === 'refund_only')

const statusTitle = computed(() => {
  const r = refund.value
  if (!r) return ''
  // 仅退款无退货环节，approved 直接进入退款流程
  if (r.status === 'approved' && isRefundOnly.value) return '退款处理中'
  return STATUS_TEXT[r.status] ?? '未知状态'
})

const statusTone = computed(() => {
  const r = refund.value
  if (!r) return 'default'
  return STATUS_TONE[r.status] ?? 'default'
})

const statusDesc = computed(() => {
  const r = refund.value
  if (!r) return ''
  switch (r.status) {
    case 'pending':
      return '售后申请已提交，请耐心等待商家审核'
    case 'approved':
      return isRefundOnly.value
        ? '商家已同意退款，退款处理中，无需退货'
        : '商家已同意退货退款，请尽快填写退货物流信息'
    case 'returning':
      return '退货已寄出，等待商家确认收货'
    case 'received':
      return '商家已确认收货，退款处理中'
    case 'refunding':
      return '退款处理中，款项将原路退回，请耐心等待'
    case 'retryable_failed':
      return '退款遇到异常，系统将自动重试，如有疑问请联系客服'
    case 'manual_review':
      return '退款正在人工处理中，请耐心等待'
    case 'refunded':
      return '退款已完成，款项已原路退回'
    case 'rejected':
      return '售后申请未通过商家审核'
    default:
      return ''
  }
})

// ---- goods ----
// 商品信息优先取关联 order_item（后端 with(['orderItem'])），兼容平铺字段
const goods = computed<Partial<OrderItemRow>>(() => {
  const r = refund.value
  if (!r) return {}
  return r.order_item ?? (r as any)
})

// ---- logistics form ----
const needLogisticsForm = computed(() => {
  const r = refund.value
  return !!r && r.status === 'approved' && r.type !== 'refund_only'
})

const canSubmitLogistics = computed(() => {
  return !!logisticsForm.company.trim() && !!logisticsForm.express_no.trim() && !submitting.value
})

async function handleSubmitLogistics() {
  const r = refund.value
  if (!r) return
  const company = logisticsForm.company.trim()
  const expressNo = logisticsForm.express_no.trim()
  if (!company) {
    uni.showToast({ title: '请输入快递公司', icon: 'none' })
    return
  }
  if (!expressNo) {
    uni.showToast({ title: '请输入快递单号', icon: 'none' })
    return
  }

  submitting.value = true
  try {
    await orderApi.submitRefundLogistics(r.id, { company, express_no: expressNo })
    uni.showToast({ title: '提交成功', icon: 'success' })
    await loadDetail()
  } catch {
    // handled by request interceptor
  } finally {
    submitting.value = false
  }
}

// ---- helpers ----
function formatPrice(val: string | number | undefined): string {
  if (val == null) return '0.00'
  return Number(val).toFixed(2)
}

// ---- fetch ----
async function loadDetail() {
  if (!refundId) {
    loading.value = false
    return
  }
  loading.value = true
  try {
    refund.value = await orderApi.getRefundDetail(refundId)
  } catch {
    refund.value = null
  } finally {
    loading.value = false
  }
}

// ---- lifecycle ----
onLoad((options: any) => {
  refundId = String(options?.id ?? '')
  loadDetail()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.refund-detail {
  padding: 0;

  &__loading {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
  }
}

/* ===== Status card ===== */
.status-card {
  border-radius: 16rpx;
  padding: 36rpx 32rpx;
  margin-bottom: 24rpx;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  background: var(--color-primary, #{$primary-color});

  &--warning { background: $warning-color; }
  &--success { background: $success-color; }
  &--danger  { background: $danger-color; }
  &--default { background: #909399; }

  &__title {
    font-size: 36rpx;
    font-weight: 600;
    color: #ffffff;
  }

  &__desc {
    font-size: 25rpx;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.6;
  }
}

/* ===== Section card（与 refund.vue 保持一致） ===== */
.section-card {
  background: var(--color-white, #ffffff);
  border-radius: 16rpx;
  overflow: hidden;
  margin-bottom: 24rpx;
  padding: 28rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: var(--color-text, #{$text-color});
  margin-bottom: 24rpx;
}

/* ===== Refuse reason ===== */
.refuse-card {
  &__text {
    font-size: 26rpx;
    color: $danger-color;
    line-height: 1.6;
  }
}

/* ===== Goods info ===== */
.item-info {
  display: flex;
  align-items: flex-start;
  gap: 20rpx;
}

.item-cover {
  width: 120rpx;
  height: 120rpx;
  border-radius: 12rpx;
  flex-shrink: 0;
  background: var(--color-bg, #{$bg-color});
}

.item-detail {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.item-name {
  font-size: 28rpx;
  font-weight: 500;
  color: var(--color-text, #{$text-color});
  line-height: 1.4;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.item-spec {
  font-size: 24rpx;
  color: var(--color-text-2, #{$text-color-secondary});
}

.item-price-row {
  display: flex;
  align-items: baseline;
  gap: 4rpx;
  margin-top: 4rpx;
}

.item-price {
  font-size: 28rpx;
  font-weight: 600;
  color: var(--color-primary, #{$primary-color});
}

.item-qty {
  font-size: 24rpx;
  color: var(--color-text-2, #{$text-color-secondary});
}

/* ===== Info list ===== */
.info-list {
  display: flex;
  flex-direction: column;
}

.info-row {
  display: flex;
  align-items: flex-start;
  padding: 14rpx 0;
  border-bottom: 1rpx solid var(--color-border, #{$border-color});

  &:last-child {
    border-bottom: none;
  }
}

.info-label {
  font-size: 26rpx;
  color: var(--color-text-2, #{$text-color-secondary});
  width: 200rpx;
  flex-shrink: 0;
}

.info-value {
  font-size: 26rpx;
  color: var(--color-text, #{$text-color});
  flex: 1;
  word-break: break-all;

  &--amount {
    font-size: 30rpx;
    font-weight: 600;
    color: $danger-color;
  }
}

/* ===== Logistics form ===== */
.form-field {
  display: flex;
  align-items: center;
  border: 2rpx solid var(--color-border, #{$border-color});
  border-radius: 16rpx;
  padding: 20rpx 24rpx;
  margin-bottom: 20rpx;
  gap: 16rpx;
}

.form-label {
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
  width: 160rpx;
  flex-shrink: 0;
}

.form-input {
  flex: 1;
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
  min-width: 0;
}

.submit-btn {
  border-radius: 16rpx !important;
  height: 88rpx !important;
  font-size: 30rpx !important;
  margin-top: 8rpx;
}

.input-placeholder {
  color: var(--color-text-3, #c0c4cc);
  font-size: 28rpx;
}

/* ===== Logistics hint ===== */
.logistics-hint {
  margin-top: 20rpx;
  padding: 16rpx 20rpx;
  background: var(--color-bg, #{$bg-color});
  border-radius: 12rpx;

  text {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
  }
}
</style>
