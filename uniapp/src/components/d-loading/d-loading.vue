<template>
  <view class="d-loading" :class="`d-loading--${size}`">
    <view class="d-loading__spinner" />
    <text v-if="text" class="d-loading__text">{{ text }}</text>
  </view>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    size?: 'sm' | 'md' | 'lg'
    text?: string
  }>(),
  { size: 'md', text: '' }
)
</script>

<style lang="scss" scoped>

@keyframes d-loading-spin {
  to { transform: rotate(360deg); }
}

.d-loading {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--space-1);

  &__spinner {
    border: 4rpx solid var(--color-bg-3);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: d-loading-spin 0.8s linear infinite;
  }
  &--sm .d-loading__spinner { width: 32rpx; height: 32rpx; border-width: 3rpx; }
  &--md .d-loading__spinner { width: 56rpx; height: 56rpx; }
  &--lg .d-loading__spinner { width: 96rpx; height: 96rpx; border-width: 6rpx; }

  &__text {
    font-size: var(--font-sm);
    color: var(--color-text-2);
  }
}
</style>
