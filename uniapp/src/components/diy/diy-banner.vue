<template>
  <swiper
    v-if="(items?.length ?? 0) > 0"
    class="diy-banner"
    :style="{ height: `${height || 360}rpx` }"
    :autoplay="autoplay !== false"
    :interval="interval || 3000"
    circular
    indicator-dots
  >
    <swiper-item v-for="(item, i) in items" :key="i">
      <navigator v-if="item.url" :url="item.url" class="diy-banner__link">
        <image :src="appStore.getImageUrl(item.image)" mode="aspectFill" class="diy-banner__img" />
      </navigator>
      <image v-else :src="appStore.getImageUrl(item.image)" mode="aspectFill" class="diy-banner__img" />
    </swiper-item>
  </swiper>
</template>

<script setup lang="ts">
import { useAppStore } from '@/store/app.store'
const appStore = useAppStore()
defineProps<{
  items?: any[]
  autoplay?: boolean
  interval?: number
  height?: number
}>()
</script>

<style lang="scss" scoped>
.diy-banner {
  width: 100%;
  &__link,
  &__img {
    width: 100%;
    height: 100%;
  }
}
</style>
