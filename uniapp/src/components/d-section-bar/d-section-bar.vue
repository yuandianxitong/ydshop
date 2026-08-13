<template>
  <view class="d-section-bar" :style="{ height }" />
</template>

<script setup lang="ts">
withDefaults(defineProps<{ height?: string }>(), { height: '16rpx' })
</script>

<style lang="scss" scoped>

.d-section-bar {
  width: 100%;
  background: var(--color-bg-3);
}
</style>
