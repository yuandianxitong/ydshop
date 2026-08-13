<template>
  <view class="d-cell-group">
    <view v-if="title" class="d-cell-group__title">{{ title }}</view>
    <view class="d-cell-group__body">
      <slot />
    </view>
  </view>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ title?: string }>(), { title: '' })
</script>

<style lang="scss" scoped>

.d-cell-group {
  &__title {
    padding: var(--space-2) var(--space-4);
    font-size: var(--font-sm);
    color: var(--color-text-3);
    background: var(--color-bg-2);
  }
  &__body {
    background: var(--color-bg-1);
    border-radius: var(--radius-md);
    overflow: hidden;
    // 自动处理最后一个 d-cell 的底边框（兼容用户忘记加 is-last）
    :deep(.d-cell:last-child) { border-bottom: none; }
  }
}
</style>
