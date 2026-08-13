<template>
  <view class="d-skeleton" :class="`d-skeleton--${variant}`">
    <!-- goods-card：图 + 两行文字 + 价格 -->
    <view v-if="variant === 'goods-card'" class="d-skeleton-goods-card">
      <view class="d-skeleton__bone d-skeleton-goods-card__cover" />
      <view class="d-skeleton__bone d-skeleton-goods-card__line d-skeleton-goods-card__line--1" />
      <view class="d-skeleton__bone d-skeleton-goods-card__line d-skeleton-goods-card__line--2" />
      <view class="d-skeleton__bone d-skeleton-goods-card__price" />
    </view>

    <!-- list：N 行行项 -->
    <view v-else-if="variant === 'list'" class="d-skeleton-list">
      <view v-for="i in count" :key="i" class="d-skeleton-list__item">
        <view class="d-skeleton__bone d-skeleton-list__avatar" />
        <view class="d-skeleton-list__body">
          <view class="d-skeleton__bone d-skeleton-list__line d-skeleton-list__line--1" />
          <view class="d-skeleton__bone d-skeleton-list__line d-skeleton-list__line--2" />
        </view>
      </view>
    </view>

    <!-- goods-detail：详情页骨架 -->
    <view v-else-if="variant === 'goods-detail'" class="d-skeleton-detail">
      <view class="d-skeleton__bone d-skeleton-detail__cover" />
      <view class="d-skeleton-detail__pad">
        <view class="d-skeleton__bone d-skeleton-detail__price" />
        <view class="d-skeleton__bone d-skeleton-detail__title" />
        <view class="d-skeleton__bone d-skeleton-detail__title d-skeleton-detail__title--2" />
      </view>
      <view class="d-skeleton__bone d-skeleton-detail__cell" v-for="i in 4" :key="i" />
    </view>

    <!-- block：通用块（高度可定制） -->
    <view v-else class="d-skeleton__bone d-skeleton-block" :style="{ height }" />
  </view>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'goods-card' | 'list' | 'goods-detail' | 'block'
    count?: number  // 仅 list variant
    height?: string // 仅 block variant
  }>(),
  {
    variant: 'block',
    count: 5,
    height: '200rpx',
  }
)
</script>

<style lang="scss" scoped>

@keyframes d-skeleton-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.d-skeleton__bone {
  background: var(--color-bg-3);
  border-radius: var(--radius-sm);
  animation: d-skeleton-pulse 1.4s ease-in-out infinite;
}

// goods-card
.d-skeleton-goods-card {
  background: var(--color-bg-1);
  padding: var(--space-2);
  border-radius: var(--radius-md);

  &__cover { width: 100%; aspect-ratio: 1; border-radius: var(--radius-sm); }
  &__line { height: 28rpx; margin-top: var(--space-2); }
  &__line--1 { width: 90%; }
  &__line--2 { width: 60%; }
  &__price { width: 40%; height: 36rpx; margin-top: var(--space-2); }
}

// list
.d-skeleton-list__item {
  display: flex;
  padding: var(--space-3) 0;
  align-items: center;
  & + & { border-top: 1rpx solid var(--color-border-2); }
}
.d-skeleton-list__avatar { width: 88rpx; height: 88rpx; border-radius: 50%; margin-right: var(--space-2); }
.d-skeleton-list__body { flex: 1; }
.d-skeleton-list__line { height: 24rpx; }
.d-skeleton-list__line--1 { width: 80%; }
.d-skeleton-list__line--2 { width: 50%; margin-top: var(--space-1); }

// goods-detail
.d-skeleton-detail {
  &__cover { width: 100%; aspect-ratio: 1; border-radius: 0; }
  &__pad { padding: var(--space-4); }
  &__price { width: 30%; height: 56rpx; }
  &__title { height: 32rpx; margin-top: var(--space-2); width: 100%; }
  &__title--2 { width: 60%; }
  &__cell { margin: var(--space-2) var(--space-4); height: 64rpx; }
}

// block
.d-skeleton-block { width: 100%; }
</style>
