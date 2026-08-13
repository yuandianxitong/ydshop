<template>
  <view class="d-goods-row-card" @tap="goDetail">
    <view class="d-goods-row-card__image-wrap">
      <image
        class="d-goods-row-card__image"
        :src="coverUrl"
        mode="aspectFill"
        lazy-load
      />
    </view>
    <view class="d-goods-row-card__body">
      <view class="d-goods-row-card__main">
        <text class="d-goods-row-card__name">{{ goods.name }}</text>
        <text v-if="goods.subtitle" class="d-goods-row-card__sub">{{ goods.subtitle }}</text>
      </view>
      <view class="d-goods-row-card__foot">
        <d-price :price="goods.min_price" />
        <view class="d-goods-row-card__add" @tap.stop="$emit('add-cart', goods)">
          <d-icon name="add" size="36rpx" color="#ffffff" />
        </view>
      </view>
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

defineEmits<{
  'add-cart': [goods: GoodsItem]
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

.d-goods-row-card {
  display: flex;
  background: #ffffff;
  border-radius: 16rpx;
  padding: 20rpx;
  margin-bottom: 16rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

  &__image-wrap {
    width: 200rpx;
    height: 200rpx;
    border-radius: 12rpx;
    overflow: hidden;
    background: #f8f8f8;
    flex-shrink: 0;
  }

  &__image {
    width: 100%;
    height: 100%;
  }

  &__body {
    flex: 1;
    margin-left: 20rpx;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
  }

  &__main {
    display: flex;
    flex-direction: column;
  }

  &__name {
    font-size: 28rpx;
    font-weight: 500;
    color: var(--color-text, #{$text-color});
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__sub {
    font-size: 22rpx;
    color: $text-color-secondary;
    margin-top: 8rpx;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 12rpx;
  }

  &__add {
    width: 56rpx;
    height: 56rpx;
    border-radius: 50%;
    background: var(--color-primary, #{$primary-color});
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
}
</style>
