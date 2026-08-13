<template>
  <view class="diy-search-bar" :style="{ background: bg_color || '#f5f5f5' }">
    <navigator
      url="/modules/goods/pages/search"
      class="diy-search-bar__input"
      :style="{ borderRadius: `${border_radius ?? 999}rpx` }"
    >
      <text class="diy-search-bar__placeholder">{{ placeholder || '搜索商品' }}</text>
    </navigator>
  </view>
</template>

<script setup lang="ts">
defineProps<{
  placeholder?: string
  border_radius?: number
  bg_color?: string
}>()
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-search-bar {
  padding: 16rpx 24rpx;
}
.diy-search-bar__input {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding: 16rpx 24rpx;
  background: #fff;
}
.diy-search-bar__placeholder {
  font-size: 26rpx;
  color: $text-color-secondary;
}
</style>
