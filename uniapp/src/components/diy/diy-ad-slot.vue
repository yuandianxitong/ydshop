<template>
  <view v-if="hasAds" class="diy-ad-slot" :style="wrapperStyle">
    <swiper
      v-if="isCarousel && ads.length > 1"
      :autoplay="true"
      :interval="4000"
      circular
      class="diy-ad-slot__swiper"
    >
      <swiper-item v-for="ad in ads" :key="ad.id" class="diy-ad-slot__item">
        <view @tap="handleClick(ad.link)" class="diy-ad-slot__link">
          <image :src="ad.image" mode="aspectFill" class="diy-ad-slot__img" />
        </view>
      </swiper-item>
    </swiper>
    <view v-else class="diy-ad-slot__single" @tap="handleClick(ads[0].link)">
      <image :src="ads[0].image" mode="aspectFill" class="diy-ad-slot__img" />
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface AdItem {
  id: number
  image: string
  link: string
}

interface AdListData {
  position?: { is_carousel?: number } | null
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
const wrapperStyle = computed(() => (props.height ? `height:${props.height}px` : ''))

function handleClick(link: string) {
  if (!link) return
  if (/^https?:\/\//.test(link)) {
    // #ifdef H5
    window.location.href = link
    // #endif
    // #ifdef MP-WEIXIN || APP-PLUS
    uni.navigateTo({ url: `/pages/webview/index?url=${encodeURIComponent(link)}`, fail: () => {} })
    // #endif
    return
  }
  uni.navigateTo({ url: link, fail: () => {} })
}
</script>

<style scoped lang="scss">
.diy-ad-slot {
  width: 100%;
  overflow: hidden;
}

.diy-ad-slot__swiper,
.diy-ad-slot__single {
  width: 100%;
  height: 100%;
}

.diy-ad-slot__item,
.diy-ad-slot__link {
  width: 100%;
  height: 100%;
}

.diy-ad-slot__img {
  width: 100%;
  height: 100%;
  display: block;
}
</style>
