<template>
  <view v-if="src" class="diy-video" :style="{ aspectRatio: aspectRatio || '16 / 9' }">
    <video
      class="diy-video__player"
      :src="src"
      :poster="poster"
      :autoplay="autoplay === true"
      :loop="loop === true"
      :muted="muted === true"
      :object-fit="objectFit || 'contain'"
      :style="{ borderRadius: `${borderRadius ?? 12}rpx` }"
    />
  </view>
</template>

<script setup lang="ts">
defineProps<{
  src?: string
  poster?: string
  autoplay?: boolean
  loop?: boolean
  muted?: boolean
  aspectRatio?: string
  borderRadius?: number
  objectFit?: string
}>()
</script>

<style lang="scss" scoped>
.diy-video {
  width: 100%;
  padding: 16rpx 24rpx;
  &__player {
    width: 100%;
    height: 100%;
    overflow: hidden;
  }
}
</style>
