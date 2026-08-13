<template>
  <view class="d-bottom-bar" :class="{ 'd-bottom-bar--fixed': fixed }">
    <view v-if="hasIcons" class="d-bottom-bar__icons">
      <slot name="icons" />
    </view>
    <view class="d-bottom-bar__actions">
      <slot name="actions" />
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'

withDefaults(defineProps<{ fixed?: boolean }>(), { fixed: true })

const slots = useSlots()
const hasIcons = computed(() => !!slots.icons)
</script>

<style lang="scss" scoped>

.d-bottom-bar {
  display: flex;
  align-items: center;
  height: 112rpx;
  padding: 0 var(--space-3);
  // content-box：112rpx 内容区 + 底部安全区，避免 border-box 把安全区从内容高度里抠掉导致按钮贴底
  padding-bottom: constant(safe-area-inset-bottom);
  padding-bottom: env(safe-area-inset-bottom);
  background: var(--color-bg-1);
  border-top: 1rpx solid var(--color-border-2);
  box-sizing: content-box;

  &--fixed {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100;
  }

  &__icons {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex-shrink: 0;
  }
  &__actions {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-2);
    margin-left: var(--space-2);
  }
}
</style>
