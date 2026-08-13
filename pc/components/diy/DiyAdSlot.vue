<template>
  <div v-if="hasAds" class="diy-ad-slot" :style="wrapperStyle">
    <Swiper
      v-if="isCarousel && ads.length > 1"
      :modules="[SwiperAutoplay, SwiperPagination]"
      :autoplay="{ delay: 4000, disableOnInteraction: false }"
      :pagination="{ clickable: true }"
      loop
      class="diy-ad-slot__swiper"
    >
      <SwiperSlide v-for="ad in ads" :key="ad.id">
        <AdLink :ad="ad" />
      </SwiperSlide>
    </Swiper>
    <AdLink v-else :ad="ads[0]" />
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, resolveComponent } from 'vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay as SwiperAutoplay, Pagination as SwiperPagination } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/pagination'

interface AdItem { id: number; image: string; link: string }
interface AdListData {
  position?: { is_carousel?: number; recommended_width?: number; recommended_height?: number } | null
  ads?: AdItem[]
}

const props = defineProps<{
  position_code?: string
  height?: number
  ad_list?: AdListData
}>()

const data = computed<AdListData>(() => props.ad_list || { position: null, ads: [] })
const ads = computed(() => data.value.ads || [])
const hasAds = computed(() => ads.value.length > 0)
const isCarousel = computed(() => (data.value.position?.is_carousel ?? 0) === 1)
const wrapperStyle = computed(() => (props.height ? { height: `${props.height}px` } : {}))

const isExternal = (p: string) => /^https?:\/\//.test(p)

// 内联 AdLink 子组件，处理 link 三分支：外链 / 内链 / 无链接
const AdLink = defineComponent({
  name: 'AdLink',
  props: { ad: { type: Object as () => AdItem, required: true } },
  setup(p) {
    return () => {
      const img = h('img', {
        src: p.ad.image,
        class: 'diy-ad-slot__img',
        loading: 'lazy',
      })
      if (!p.ad.link) return img
      if (isExternal(p.ad.link)) {
        return h('a', { href: p.ad.link, target: '_blank', rel: 'noopener', class: 'diy-ad-slot__link' }, [img])
      }
      return h(
        resolveComponent('NuxtLink'),
        { to: p.ad.link, class: 'diy-ad-slot__link' },
        { default: () => img }
      )
    }
  },
})
</script>

<style scoped>
.diy-ad-slot { width: 100%; border-radius: 8px; overflow: hidden; }
.diy-ad-slot__swiper { width: 100%; height: 100%; }
.diy-ad-slot :deep(.diy-ad-slot__link) { display: block; width: 100%; height: 100%; }
.diy-ad-slot :deep(.diy-ad-slot__img) { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>
