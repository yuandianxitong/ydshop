<template>
  <view
    v-if="(items?.length ?? 0) > 0"
    class="diy-nav-grid"
    :style="{ gridTemplateColumns: `repeat(${columns || 4}, 1fr)` }"
  >
    <navigator
      v-for="(item, i) in items"
      :key="i"
      :url="item.url || ''"
      class="diy-nav-grid__item"
    >
      <image
        v-if="item.icon"
        :src="appStore.getImageUrl(item.icon)"
        class="diy-nav-grid__icon"
        mode="aspectFill"
      />
      <view v-else class="diy-nav-grid__icon diy-nav-grid__icon--empty"></view>
      <text class="diy-nav-grid__title">{{ item.title }}</text>
    </navigator>
  </view>
</template>
<script setup lang="ts">
import { useAppStore } from '@/store/app.store'
const appStore = useAppStore()
defineProps<{ items?: any[]; columns?: number; rows?: number }>()
</script>
<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-nav-grid {
  display: grid;
  gap: 16rpx;
  padding: 24rpx;
  text-align: center;
}
.diy-nav-grid__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
}
.diy-nav-grid__icon {
  width: 88rpx;
  height: 88rpx;
  border-radius: 50%;
}
.diy-nav-grid__icon--empty {
  background: $bg-color;
}
.diy-nav-grid__title {
  font-size: 24rpx;
  color: $text-color-secondary;
}
</style>
