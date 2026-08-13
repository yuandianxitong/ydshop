<template>
  <view v-if="title || subtitle" class="diy-title-bar" :style="{ textAlign: (align || 'left') as any }">
    <view class="diy-title-bar__left">
      <text class="diy-title-bar__title">{{ title }}</text>
      <text v-if="subtitle" class="diy-title-bar__subtitle">{{ subtitle }}</text>
    </view>
    <navigator v-if="more_url" :url="more_url" class="diy-title-bar__more">
      <text>{{ more_text || '更多' }}</text>
      <text class="diy-title-bar__arrow">›</text>
    </navigator>
  </view>
</template>

<script setup lang="ts">
defineProps<{
  title?: string
  subtitle?: string
  align?: string
  more_url?: string
  more_text?: string
}>()
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-title-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx;
}
.diy-title-bar__title {
  font-size: 32rpx;
  font-weight: 600;
  color: $text-color;
}
.diy-title-bar__subtitle {
  font-size: 24rpx;
  color: $text-color-secondary;
  margin-left: 12rpx;
}
.diy-title-bar__more {
  display: flex;
  align-items: center;
  font-size: 24rpx;
  color: $text-color-secondary;
}
.diy-title-bar__arrow {
  margin-left: 6rpx;
  font-size: 28rpx;
  line-height: 1;
}
</style>
