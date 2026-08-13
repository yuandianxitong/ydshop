<template>
  <view class="d-price" :class="`d-price--${sizeFinal}`">
    <text class="d-price__symbol">¥</text>
    <text class="d-price__integer">{{ integer }}</text>
    <text class="d-price__decimal">.{{ decimal }}</text>
    <text v-if="originalText" class="d-price__original">¥{{ originalText }}</text>
    <view v-if="tag" class="d-price__tag">{{ tag }}</view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  // 新 API（推荐）
  main?: number
  original?: number
  tag?: string
  size?: 'sm' | 'md' | 'lg' | 'xl'

  // 旧 API（deprecated，保留兼容 d-goods-card / cart / checkout 等存量调用）
  price?: number
  originalPrice?: number
}>()

const sizeFinal = computed(() => props.size ?? 'md')

const mainValue = computed(() => props.main ?? props.price ?? 0)
const originalValue = computed(() => props.original ?? props.originalPrice)

const priceText = computed(() => Number(mainValue.value).toFixed(2))
const integer = computed(() => priceText.value.split('.')[0])
const decimal = computed(() => priceText.value.split('.')[1])
const originalText = computed(() =>
  originalValue.value != null ? Number(originalValue.value).toFixed(2) : ''
)
</script>

<style lang="scss" scoped>

.d-price {
  display: inline-flex;
  align-items: baseline;
  color: var(--color-price);

  &__symbol { font-weight: 600; }
  &__integer { font-weight: 800; line-height: 1; }
  &__decimal { font-weight: 600; }

  &__original {
    margin-left: var(--space-1);
    font-size: var(--font-xs);
    color: var(--color-text-3);
    text-decoration: line-through;
  }

  &__tag {
    margin-left: var(--space-1);
    padding: 2rpx 8rpx;
    background: rgba(239, 68, 68, 0.1);
    color: var(--color-price);
    font-size: 18rpx;
    border-radius: var(--radius-sm);
    align-self: center;
  }

  // size variants
  &--sm {
    .d-price__symbol { font-size: 18rpx; }
    .d-price__integer { font-size: 26rpx; }
    .d-price__decimal { font-size: 18rpx; }
  }
  &--md {
    .d-price__symbol { font-size: 22rpx; }
    .d-price__integer { font-size: 32rpx; }
    .d-price__decimal { font-size: 22rpx; }
  }
  &--lg {
    .d-price__symbol { font-size: 24rpx; }
    .d-price__integer { font-size: 40rpx; }
    .d-price__decimal { font-size: 24rpx; }
  }
  &--xl {
    .d-price__symbol { font-size: 28rpx; align-self: flex-start; margin-top: 8rpx; }
    .d-price__integer { font-size: 56rpx; letter-spacing: -1rpx; }
    .d-price__decimal { font-size: 28rpx; }
  }
}
</style>
