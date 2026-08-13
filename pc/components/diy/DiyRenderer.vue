<template>
  <div class="mx-auto max-w-1200px px-4 diy-page">
    <!-- 第一屏（通常是 banner）：包一层 relative 容器，便于父级注入侧边栏等浮层 -->
    <div v-if="firstComponent" class="relative">
      <div :style="getStyle(firstComponent)">
        <component :is="componentMap[firstComponent.type]" v-bind="firstComponent.props" />
      </div>
      <slot name="firstOverlay" />
    </div>
    <!-- 其余组件 -->
    <div v-for="comp in restComponents" :key="comp.id" :style="getStyle(comp)">
      <component :is="componentMap[comp.type]" v-bind="comp.props" />
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import DiyBanner from './DiyBanner.vue'
import DiyImageAd from './DiyImageAd.vue'
import DiyImageCube from './DiyImageCube.vue'
import DiyRichText from './DiyRichText.vue'
import DiyTitleBar from './DiyTitleBar.vue'
import DiyDivider from './DiyDivider.vue'
import DiyGoodsGrid from './DiyGoodsGrid.vue'
import DiyCategoryNav from './DiyCategoryNav.vue'
import DiySearchBar from './DiySearchBar.vue'
import DiyCouponList from './DiyCouponList.vue'
import DiyAdSlot from './DiyAdSlot.vue'
import DiySeckill from './DiySeckill.vue'
import DiyNotice from './DiyNotice.vue'
import DiyNavGrid from './DiyNavGrid.vue'
import DiyFloatButton from './DiyFloatButton.vue'
import DiyArticleList from './DiyArticleList.vue'
import DiyVideo from './DiyVideo.vue'
import { componentStyleToCss } from './styleUtils'

const componentMap: Record<string, any> = {
    'banner': DiyBanner, 'image-ad': DiyImageAd, 'image-cube': DiyImageCube, 'rich-text': DiyRichText,
    'title-bar': DiyTitleBar, 'divider': DiyDivider, 'goods-grid': DiyGoodsGrid,
    'category-nav': DiyCategoryNav, 'search-bar': DiySearchBar, 'coupon-list': DiyCouponList,
    'ad-slot': DiyAdSlot, 'seckill': DiySeckill, 'notice': DiyNotice, 'nav-grid': DiyNavGrid, 'float-button': DiyFloatButton,
    'article-list': DiyArticleList, 'video': DiyVideo,
}
const props = defineProps<{ components: any[] }>()
const visibleComponents = computed(() => (props.components || []).filter((c: any) => !c?.hidden))
const firstComponent = computed(() => visibleComponents.value[0])
const restComponents = computed(() => visibleComponents.value.slice(1))
function getStyle(comp: any) { return componentStyleToCss(comp.props?.componentStyle, 'pc') }
</script>
