<template>
  <view
    v-if="(category_list?.length ?? 0) > 0"
    class="diy-category-nav"
    :style="{ gridTemplateColumns: `repeat(${columns || 5}, 1fr)` }"
  >
    <navigator
      v-for="(item, i) in category_list"
      :key="item.id || i"
      :url="getItemUrl(item)"
      class="diy-category-nav__item"
    >
      <view class="diy-category-nav__content">
        <image
          v-if="item.icon"
          :src="appStore.getImageUrl(item.icon)"
          class="diy-category-nav__icon"
          mode="aspectFill"
        />
        <view v-else class="diy-category-nav__icon diy-category-nav__icon--empty"></view>
        <text class="diy-category-nav__name">{{ item.name || item.title }}</text>
      </view>
    </navigator>
  </view>
</template>
<script setup lang="ts">
import { useAppStore } from '@/store/app.store'
const appStore = useAppStore()
defineProps<{ style?: string; category_ids?: number[]; rows?: number; columns?: number; category_list?: any[] }>()

function getItemUrl(item: any): string {
  if (item.link) return item.link
  if (item.id) return `/modules/goods/pages/list?category_id=${item.id}`
  return ''
}
</script>
<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-category-nav {
  display: grid;
  gap: 16rpx;
  padding: 24rpx;
  text-align: center;
}
.diy-category-nav__item {
  display: block;
}
.diy-category-nav__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8rpx;
}
.diy-category-nav__icon {
  width: 88rpx;
  height: 88rpx;
  border-radius: 50%;
}
.diy-category-nav__icon--empty {
  background: $bg-color;
}
.diy-category-nav__name {
  font-size: 24rpx;
  color: $text-color-secondary;
}
</style>
