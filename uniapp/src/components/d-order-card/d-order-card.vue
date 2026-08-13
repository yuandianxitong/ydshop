<template>
  <view class="d-order-card" @tap="handleDetail">
    <!-- Header -->
    <view class="d-order-card__header">
      <text class="d-order-card__no">订单号：{{ order.order_no }}</text>
      <view class="d-order-card__header-right">
        <d-tag v-if="order.delivery_type === 'pickup'" text="自提" variant="primary" size="sm" :plain="true" />
        <d-tag v-else-if="order.delivery_type === 'merchant'" text="同城配送" variant="warning" size="sm" :plain="true" />
        <text class="d-order-card__status" :class="statusClass">{{ statusText }}</text>
      </view>
    </view>

    <!-- Item preview -->
    <view class="d-order-card__body">
      <view class="d-order-card__item">
        <image
          class="d-order-card__cover"
          :src="coverUrl"
          mode="aspectFill"
          lazy-load
        />
        <view class="d-order-card__item-info">
          <text class="d-order-card__item-name">{{ firstItem?.goods_name }}</text>
          <text v-if="firstItem?.spec_text" class="d-order-card__item-spec">{{ firstItem.spec_text }}</text>
          <view class="d-order-card__item-price-row">
            <text class="d-order-card__item-price">¥{{ formatPrice(firstItem?.price) }}</text>
            <text class="d-order-card__item-qty">× {{ firstItem?.quantity }}</text>
          </view>
        </view>
      </view>

      <view v-if="extraCount > 0" class="d-order-card__more">
        <text class="d-order-card__more-text">等 {{ extraCount }} 件商品</text>
      </view>
    </view>

    <!-- Footer -->
    <view class="d-order-card__footer">
      <view class="d-order-card__total">
        <text class="d-order-card__total-label">共 {{ order.items?.length ?? 0 }} 件，合计：</text>
        <text class="d-order-card__total-amount">¥{{ formatPrice(order.pay_amount) }}</text>
      </view>
      <view class="d-order-card__actions" @tap.stop>
        <!-- pending: cancel + pay -->
        <template v-if="order.status === 'pending'">
          <view class="d-order-card__btn d-order-card__btn--outline" @tap.stop="emit('action', 'cancel', order)">
            <text>取消</text>
          </view>
          <view class="d-order-card__btn d-order-card__btn--primary" @tap.stop="emit('action', 'pay', order)">
            <text>付款</text>
          </view>
        </template>
        <!-- shipped: confirm receive -->
        <template v-else-if="order.status === 'shipped'">
          <view class="d-order-card__btn d-order-card__btn--primary" @tap.stop="emit('action', 'confirm', order)">
            <text>确认收货</text>
          </view>
        </template>
        <!-- completed: review -->
        <template v-else-if="order.status === 'completed'">
          <view class="d-order-card__btn d-order-card__btn--outline" @tap.stop="emit('action', 'review', order)">
            <text>评价</text>
          </view>
        </template>
        <!-- detail always -->
        <view class="d-order-card__btn d-order-card__btn--outline" @tap.stop="emit('action', 'detail', order)">
          <text>详情</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app.store'
import type { OrderItem } from '@/api/order'

const props = defineProps<{
  order: OrderItem
}>()

const emit = defineEmits<{
  action: [type: string, order: OrderItem]
}>()

const appStore = useAppStore()

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
  cancelled: 'status--default',
  closed:    'status--default',
}

const statusText = computed(() => STATUS_TEXT[props.order.status] ?? '未知')
const statusClass = computed(() => STATUS_CLASS[props.order.status] ?? 'status--default')

const firstItem = computed(() => props.order.items?.[0])
const extraCount = computed(() => Math.max(0, (props.order.items?.length ?? 0) - 1))

const coverUrl = computed(() => {
  const url = firstItem.value?.goods_image
  if (!url) return ''
  return appStore.getImageUrl(url)
})

// 后端 price/pay_amount 是 decimal 字符串（元），不再除 100
function formatPrice(val: string | number | undefined): string {
  if (val == null) return '0.00'
  return Number(val).toFixed(2)
}

function handleDetail() {
  emit('action', 'detail', props.order)
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.d-order-card {
  background: var(--color-bg-white, #ffffff);
  border-radius: var(--radius-lg, 16rpx);
  margin-bottom: 20rpx;
  overflow: hidden;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24rpx 28rpx 16rpx;
    border-bottom: 1rpx solid $border-color;
  }

  &__header-right {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-shrink: 0;
    margin-left: 16rpx;
  }

  &__no {
    font-size: 24rpx;
    color: $text-color-secondary;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__status {
    font-size: 24rpx;
    font-weight: 500;
    flex-shrink: 0;

    &.status--warning { color: #d97706; }
    &.status--primary { color: var(--color-primary, #{$primary-color}); }
    &.status--info { color: #0284c7; }
    &.status--success { color: $success-color; }
    &.status--default { color: $text-color-secondary; }
  }

  &__body {
    padding: 20rpx 28rpx;
  }

  &__item {
    display: flex;
    align-items: flex-start;
    gap: 20rpx;
  }

  &__cover {
    width: 120rpx;
    height: 120rpx;
    border-radius: var(--radius-sm, 8rpx);
    flex-shrink: 0;
    background: var(--color-bg, #{$bg-color});
  }

  &__item-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 120rpx;
  }

  &__item-name {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__item-spec {
    font-size: 22rpx;
    color: $text-color-secondary;
    margin-top: 6rpx;
  }

  &__item-price-row {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-top: auto;
  }

  &__item-price {
    font-size: 28rpx;
    color: $danger-color;
    font-weight: 500;
  }

  &__item-qty {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__more {
    padding: 12rpx 0 0 140rpx;
  }

  &__more-text {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__footer {
    border-top: 1rpx solid $border-color;
    padding: 18rpx 28rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__total {
    display: flex;
    align-items: baseline;
    flex: 1;
    min-width: 0;
  }

  &__total-label {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__total-amount {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__actions {
    display: flex;
    align-items: center;
    gap: 14rpx;
    flex-shrink: 0;
    margin-left: 16rpx;
  }

  &__btn {
    height: 56rpx;
    padding: 0 24rpx;
    border-radius: 28rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;

    &--outline {
      border: 1rpx solid $border-color;
      color: var(--color-text, #{$text-color});
      background: var(--color-bg-white, #fff);

      text {
        color: var(--color-text, #{$text-color});
      }
    }

    &--primary {
      background: var(--color-primary, #{$primary-color});
      border: 1rpx solid var(--color-primary, #{$primary-color});

      text {
        color: #fff;
      }
    }
  }
}
</style>
