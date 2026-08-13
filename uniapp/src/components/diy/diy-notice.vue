<template>
  <view v-if="(items?.length ?? 0) > 0" class="diy-notice">
    <swiper class="diy-notice__swiper" vertical autoplay :interval="speed || 3000" circular>
      <swiper-item v-for="(item, i) in items" :key="i">
        <text class="diy-notice__text">{{ item.text }}</text>
      </swiper-item>
    </swiper>
  </view>
</template>

<script setup lang="ts">
defineProps<{
  source?: string
  items?: any[]
  speed?: number
}>()
</script>

<style lang="scss" scoped>
.diy-notice {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 16rpx 24rpx;
  background: #fffbeb;
}
.diy-notice__swiper {
  height: 40rpx;
  flex: 1;
}
.diy-notice__text {
  font-size: 26rpx;
  color: #92400e;
  line-height: 40rpx;
}
</style>
