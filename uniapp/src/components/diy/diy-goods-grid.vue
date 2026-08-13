<template>
  <view
    v-if="(goods_list?.length ?? 0) > 0"
    class="diy-goods-grid"
    :style="{ gridTemplateColumns: `repeat(${columns || 2}, 1fr)` }"
  >
    <navigator
      v-for="item in goods_list"
      :key="item.id"
      :url="`/modules/goods/pages/detail?id=${item.id}`"
      class="diy-goods-grid__item"
    >
      <image
        :src="appStore.getImageUrl(item.images?.[0] || '')"
        mode="aspectFill"
        class="diy-goods-grid__img"
      />
      <view class="diy-goods-grid__info">
        <text class="diy-goods-grid__name">{{ item.name }}</text>
        <text class="diy-goods-grid__price">¥{{ item.min_price }}</text>
      </view>
    </navigator>
  </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/store/app.store'
const appStore = useAppStore()
defineProps<{
  title?: string
  source?: string
  goods_ids?: number[]
  category_id?: number
  tag?: string
  limit?: number
  columns?: number
  goods_list?: any[]
}>()
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-goods-grid {
  display: grid;
  gap: 16rpx;
  padding: 0 24rpx 24rpx;
}
.diy-goods-grid__item {
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
}
.diy-goods-grid__img {
  width: 100%;
  aspect-ratio: 1;
}
.diy-goods-grid__info {
  padding: 16rpx;
}
.diy-goods-grid__name {
  font-size: 26rpx;
  color: $text-color;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.diy-goods-grid__price {
  font-size: 30rpx;
  color: $danger-color;
  font-weight: 600;
  margin-top: 8rpx;
}
</style>
