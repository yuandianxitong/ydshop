<template>
  <view v-if="(items?.length ?? 0) > 0" class="diy-image-ad" :class="`diy-image-ad--${layout || 'single'}`">
    <navigator
      v-for="(item, i) in items"
      :key="i"
      :url="item.url || ''"
      class="diy-image-ad__item"
    >
      <image :src="appStore.getImageUrl(item.image)" mode="aspectFill" class="diy-image-ad__img" />
    </navigator>
  </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/store/app.store'
const appStore = useAppStore()
defineProps<{
  items?: any[]
  layout?: string
}>()
</script>

<style lang="scss" scoped>
.diy-image-ad {
  display: flex;
  gap: 16rpx;
  padding: 16rpx 24rpx;
  &--single { flex-direction: column; }
  &--double { flex-direction: row; .diy-image-ad__item { flex: 1; } }
  &--triple { flex-direction: row; .diy-image-ad__item { flex: 1; } }
  &__item { display: block; }
  &__img { width: 100%; height: 200rpx; border-radius: 12rpx; }
}
</style>
