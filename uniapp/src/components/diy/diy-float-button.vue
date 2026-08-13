<template>
  <view
    v-if="(items?.length ?? 0) > 0"
    class="diy-float-btn"
    :class="`pos-${position || 'right-bottom'}`"
  >
    <navigator
      v-for="(item, i) in items"
      :key="i"
      :url="item.url || ''"
      class="diy-float-btn__item"
    >
      <text>{{ item.label }}</text>
    </navigator>
  </view>
</template>
<script setup lang="ts">
defineProps<{ items?: any[]; position?: string }>()
</script>
<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-float-btn {
  position: fixed;
  z-index: 99;
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}
.pos-right-bottom {
  right: 24rpx;
  bottom: 200rpx;
}
.pos-right-center {
  right: 24rpx;
  top: 50%;
  transform: translateY(-50%);
}
.diy-float-btn__item {
  width: 88rpx;
  height: 88rpx;
  border-radius: 50%;
  background: var(--color-primary, #{$primary-color});
  color: #fff;
  font-size: 20rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.15);
}
</style>
