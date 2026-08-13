<template>
  <view class="d-cart-action-bar">
    <view class="d-cart-action-bar__cart" @tap="$emit('tap-cart')">
      <d-icon name="cart" size="44rpx" color="#ffffff" />
      <view v-if="count > 0" class="d-cart-action-bar__badge">
        {{ count > 99 ? '99+' : count }}
      </view>
    </view>

    <view class="d-cart-action-bar__amounts">
      <view class="d-cart-action-bar__total">
        <text class="d-cart-action-bar__total-label">合计</text>
        <text class="d-cart-action-bar__total-value">¥{{ goodsAmount }}</text>
      </view>
      <text v-if="hasDiscount" class="d-cart-action-bar__discount">
        优惠减 ¥{{ discountAmount }}
      </text>
    </view>

    <view class="d-cart-action-bar__checkout" @tap="$emit('tap-checkout')">
      去结算<text v-if="count > 0">({{ count > 99 ? '99+' : count }})</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  count: number
  goodsAmount?: string
  discountAmount?: string
}>(), {
  goodsAmount: '0.00',
  discountAmount: '0.00',
})

defineEmits<{
  'tap-cart': []
  'tap-checkout': []
}>()

const hasDiscount = computed(() => Number(props.discountAmount || 0) > 0)
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.d-cart-action-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: calc(100rpx + env(safe-area-inset-bottom));
  height: 96rpx;
  background: #ffffff;
  border-top: 1rpx solid #f0f0f0;
  display: flex;
  align-items: center;
  padding: 0 24rpx;
  z-index: 99;

  &__cart {
    width: 80rpx;
    height: 80rpx;
    border-radius: 50%;
    background: var(--color-primary, #{$primary-color});
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    flex-shrink: 0;
  }

  &__badge {
    position: absolute;
    top: -6rpx;
    right: -6rpx;
    min-width: 32rpx;
    height: 32rpx;
    padding: 0 8rpx;
    border-radius: 16rpx;
    background: #ffffff;
    color: var(--color-primary, #{$primary-color});
    border: 2rpx solid var(--color-primary, #{$primary-color});
    font-size: 20rpx;
    font-weight: 600;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
  }

  &__amounts {
    flex: 1;
    margin-left: 20rpx;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
  }

  &__total {
    display: flex;
    align-items: baseline;
    gap: 6rpx;
  }

  &__total-label {
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__total-value {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--color-primary, #{$primary-color});
  }

  &__discount {
    font-size: 20rpx;
    color: $text-color-secondary;
    margin-top: 2rpx;
  }

  &__checkout {
    flex-shrink: 0;
    height: 72rpx;
    padding: 0 28rpx;
    background: var(--color-primary, #{$primary-color});
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 36rpx;
  }
}
</style>
