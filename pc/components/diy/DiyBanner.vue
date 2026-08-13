<template>
  <Swiper v-if="items && items.length" :modules="[SwiperAutoplay, SwiperPagination]" :autoplay="autoplay !== false ? { delay: interval || 3000 } : false" :pagination="{ clickable: true }" loop class="diy-banner" :style="{ height: `${height || 400}px` }">
    <SwiperSlide v-for="(item, i) in items" :key="i">
      <NuxtLink v-if="item.url" :to="item.url" class="diy-banner__link">
        <img :src="item.image" class="diy-banner__img" />
      </NuxtLink>
      <img v-else :src="item.image" class="diy-banner__img" />
    </SwiperSlide>
  </Swiper>
  <div v-else class="diy-banner diy-banner--empty" :style="{ height: `${height || 400}px` }">轮播图</div>
</template>
<script setup lang="ts">
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay as SwiperAutoplay, Pagination as SwiperPagination } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/pagination'
defineProps<{ items?: any[]; autoplay?: boolean; interval?: number; height?: number }>()
</script>
<style scoped>
.diy-banner { width: 100%; border-radius: 8px; overflow: hidden; }
.diy-banner--empty { display: flex; align-items: center; justify-content: center; background: #f0f2f5; color: #c0c4cc; font-size: 16px; }
.diy-banner__link, .diy-banner__img { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>
