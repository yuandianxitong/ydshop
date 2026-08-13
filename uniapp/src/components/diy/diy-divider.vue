<template>
  <view
    v-if="(type || 'solid') === 'solid'"
    :style="{ borderTop: `1rpx solid ${color || '#eee'}`, margin: `${(height || 2) / 2}rpx 24rpx` }"
  ></view>
  <view
    v-else
    :style="{ height: `${height || 20}rpx`, background: color || '#f5f5f5' }"
  ></view>
</template>

<script setup lang="ts">
defineProps<{
  type?: string
  height?: number
  color?: string
}>()
</script>
