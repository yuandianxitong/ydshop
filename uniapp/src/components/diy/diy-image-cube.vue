<template>
  <view v-if="(items?.length ?? 0) > 0" class="diy-image-cube">
    <view
      v-for="(row, ri) in chunkedRows"
      :key="ri"
      class="diy-image-cube__row"
      :style="{ gap: `${gap ?? 8}rpx`, marginTop: ri === 0 ? 0 : `${gap ?? 8}rpx` }"
    >
      <navigator
        v-for="(item, ci) in row"
        :key="ci"
        :url="item.url || item.link || ''"
        class="diy-image-cube__item"
        :style="{ borderRadius: `${borderRadius ?? 12}rpx` }"
      >
        <image :src="appStore.getImageUrl(item.image)" mode="widthFix" class="diy-image-cube__img" />
      </navigator>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app.store'
const appStore = useAppStore()
const props = defineProps<{
  rows?: number
  cols?: number
  gap?: number
  borderRadius?: number
  items?: any[]
}>()

const chunkedRows = computed(() => {
    const c = props.cols || 2
    const arr = props.items || []
    const out: any[][] = []
    for (let i = 0; i < arr.length; i += c) out.push(arr.slice(i, i + c))
    return out
})
</script>

<style lang="scss" scoped>
.diy-image-cube {
  padding: 16rpx 24rpx;
  &__row {
    display: flex;
    align-items: flex-start;
  }
  &__item {
    flex: 1;
    min-width: 0;
    overflow: hidden;
  }
  &__img {
    width: 100%;
    display: block;
  }
}
</style>
