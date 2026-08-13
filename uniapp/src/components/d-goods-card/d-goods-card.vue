<template>
  <view class="d-goods-card" @tap="goDetail">
    <view class="d-goods-card__image-wrap">
      <image
        class="d-goods-card__image"
        :src="coverUrl"
        mode="aspectFill"
        lazy-load
      />
    </view>
    <view class="d-goods-card__body">
      <text class="d-goods-card__name">{{ goods.name }}</text>
      <view class="d-goods-card__price-row">
        <d-price :price="goods.min_price" />
      </view>
      <text class="d-goods-card__sales">已售 {{ goods.sales_count || 0 }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app.store'
import type { GoodsItem } from '@/api/goods'

const props = defineProps<{
  goods: GoodsItem
}>()

const appStore = useAppStore()

const coverUrl = computed(() => {
  const img = props.goods.images?.[0]
  if (!img) return ''
  return appStore.getImageUrl(img)
})

function goDetail() {
  uni.navigateTo({ url: `/modules/goods/pages/detail?id=${props.goods.id}` })
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.d-goods-card {
  background: #ffffff;
  border-radius: 16rpx;
  overflow: hidden;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;

  &__image-wrap {
    width: 100%;
    padding-top: 100%; // 1:1 aspect ratio
    position: relative;
    overflow: hidden;
    background: #f8f8f8;
  }

  &__image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }

  &__body {
    padding: 16rpx;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  &__name {
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 10rpx;
    flex: 1;
  }

  &__price-row {
    margin-bottom: 8rpx;
  }

  &__sales {
    font-size: 22rpx;
    color: $text-color-secondary;
  }
}
</style>
