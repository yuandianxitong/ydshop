<template>
  <view class="d-tag" :class="[`d-tag--${variant}`, `d-tag--${size}`, { 'd-tag--plain': plain }]">
    <slot>{{ text }}</slot>
  </view>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    text?: string
    variant?: 'primary' | 'auxiliary' | 'success' | 'warning' | 'danger' | 'info' | 'neutral'
    size?: 'sm' | 'md'
    plain?: boolean   // 描边款
  }>(),
  {
    text: '',
    variant: 'primary',
    size: 'sm',
    plain: false,
  }
)
</script>

<style lang="scss" scoped>

.d-tag {
  display: inline-flex;
  align-items: center;
  border-radius: var(--radius-sm);
  font-weight: 500;
  line-height: 1;

  // sizes
  &--sm { padding: 4rpx 12rpx; font-size: var(--font-xs); }
  &--md { padding: 8rpx 16rpx; font-size: var(--font-sm); }

  // variants（实心款）
  &--primary    { background: var(--color-primary); color: #fff; }
  &--auxiliary  { background: var(--color-auxiliary); color: #fff; }
  &--success    { background: var(--color-success); color: #fff; }
  &--warning    { background: var(--color-warning); color: #fff; }
  &--danger     { background: var(--color-danger); color: #fff; }
  &--info       { background: var(--color-info); color: #fff; }
  &--neutral    { background: var(--color-bg-3); color: var(--color-text-2); }

  // plain（描边款）
  &--plain {
    background: transparent !important;
    border: 1rpx solid currentColor;
    &.d-tag--primary    { color: var(--color-primary); }
    &.d-tag--auxiliary  { color: var(--color-auxiliary); }
    &.d-tag--success    { color: var(--color-success); }
    &.d-tag--warning    { color: var(--color-warning); }
    &.d-tag--danger     { color: var(--color-danger); }
    &.d-tag--info       { color: var(--color-info); }
    &.d-tag--neutral    { color: var(--color-text-2); border-color: var(--color-border-1); }
  }
}
</style>
