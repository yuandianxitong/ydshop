<template>
  <view
    class="d-page"
    :class="[{ 'safe-area-bottom': safeArea }, padding ? 'd-page--padding' : '']"
  >
    <slot />
  </view>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  safeArea?: boolean
  padding?: boolean   // 是否给容器加默认 page padding（默认 true 兼容存量页）
}>(), {
  safeArea: true,
  padding: true,
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.d-page {
  min-height: 100vh;
  background-color: var(--color-bg, #{$bg-color});
  box-sizing: border-box;

  &--padding {
    padding: $page-padding;
  }
}
</style>
