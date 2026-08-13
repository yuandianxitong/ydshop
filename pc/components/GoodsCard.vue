<template>
  <NuxtLink :to="`/goods/${goods.id}`" class="goods-card block group">
    <div class="relative overflow-hidden rounded-t-sm bg-gray-100" style="aspect-ratio: 1/1;">
      <img
        :src="cover || defaultImage"
        :alt="goods.name"
        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        @error="onImgError"
      />
    </div>
    <div class="p-3 bg-white rounded-b-sm">
      <p class="text-sm text-gray-800 line-clamp-2 leading-snug min-h-[2.5rem]">{{ goods.name }}</p>
      <p v-if="goods.subtitle" class="mt-0.5 text-xs text-gray-400 line-clamp-1">{{ goods.subtitle }}</p>
      <div class="mt-2 flex items-end justify-between">
        <span class="text-base font-semibold text-[var(--color-primary)]">{{ priceText }}</span>
        <span class="text-xs text-gray-400">已售 {{ goods.sales_count ?? 0 }}</span>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { GoodsItem } from '~/api/goods'

const props = defineProps<{
  goods: GoodsItem
}>()

// 后端 list shape：images[] + min_price/max_price。统一兜底成数字 / 取首张图。
const cover = computed(() => props.goods?.images?.[0] ?? '')
const minPrice = computed(() => Number(props.goods?.min_price ?? 0))
const maxPrice = computed(() => Number(props.goods?.max_price ?? 0))
const priceText = computed(() => {
  const lo = minPrice.value
  const hi = maxPrice.value
  if (hi > lo) return `¥${lo.toFixed(2)} ~ ¥${hi.toFixed(2)}`
  return `¥${lo.toFixed(2)}`
})

const defaultImage = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect fill="%23f3f4f6" width="200" height="200"/><text x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="14">暂无图片</text></svg>'

function onImgError(e: Event) {
  const img = e.target as HTMLImageElement
  img.src = defaultImage
}
</script>

<style scoped>
.goods-card {
  border-radius: 4px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  transition: box-shadow 0.2s, transform 0.2s;
  overflow: hidden;
  background: #fff;
}
.goods-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}
</style>
