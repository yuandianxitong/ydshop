<template>
  <view class="diy-page">
    <view v-for="comp in list" :key="comp.id" :style="getStyle(comp)">
        <diy-banner v-if="comp.type === 'banner'"
          :items="comp.props?.items" :autoplay="comp.props?.autoplay"
          :interval="comp.props?.interval" :height="comp.props?.height" />
        <diy-image-ad v-else-if="comp.type === 'image-ad'"
          :items="comp.props?.items" :layout="comp.props?.layout" />
        <diy-rich-text v-else-if="comp.type === 'rich-text'"
          :content="comp.props?.content" />
        <diy-title-bar v-else-if="comp.type === 'title-bar'"
          :title="comp.props?.title" :subtitle="comp.props?.subtitle" :align="comp.props?.align"
          :more_url="comp.props?.more_url" :more_text="comp.props?.more_text" />
        <diy-divider v-else-if="comp.type === 'divider'"
          :type="comp.props?.type" :height="comp.props?.height" :color="comp.props?.color" />
        <diy-goods-grid v-else-if="comp.type === 'goods-grid'"
          :title="comp.props?.title" :source="comp.props?.source" :goods_ids="comp.props?.goods_ids"
          :category_id="comp.props?.category_id" :tag="comp.props?.tag" :limit="comp.props?.limit"
          :columns="comp.props?.columns" :goods_list="comp.props?.goods_list" />
        <diy-category-nav v-else-if="comp.type === 'category-nav'"
          :style="comp.props?.style" :category_ids="comp.props?.category_ids"
          :rows="comp.props?.rows" :columns="comp.props?.columns" :category_list="comp.props?.category_list" />
        <diy-search-bar v-else-if="comp.type === 'search-bar'"
          :placeholder="comp.props?.placeholder" :border_radius="comp.props?.border_radius" :bg_color="comp.props?.bg_color" />
        <diy-coupon-list v-else-if="comp.type === 'coupon-list'"
          :coupon_ids="comp.props?.coupon_ids" :style="comp.props?.style"
          :coupon_list="comp.props?.coupon_list" />
        <diy-ad-slot v-else-if="comp.type === 'ad-slot'"
          :position_code="comp.props?.position_code" :height="comp.props?.height"
          :ad_list="comp.props?.ad_list" />
        <diy-seckill v-else-if="comp.type === 'seckill'"
          :activity_id="comp.props?.activity_id" :limit="comp.props?.limit"
          :seckill_data="comp.props?.seckill_data" />
        <diy-notice v-else-if="comp.type === 'notice'"
          :source="comp.props?.source" :items="comp.props?.items" :speed="comp.props?.speed" />
        <diy-nav-grid v-else-if="comp.type === 'nav-grid'"
          :items="comp.props?.items" :columns="comp.props?.columns" :rows="comp.props?.rows" />
        <diy-float-button v-else-if="comp.type === 'float-button'"
          :items="comp.props?.items" :position="comp.props?.position" />
        <diy-video v-else-if="comp.type === 'video'"
          :src="comp.props?.src" :poster="comp.props?.poster" :autoplay="comp.props?.autoplay"
          :loop="comp.props?.loop" :muted="comp.props?.muted" :aspectRatio="comp.props?.aspectRatio"
          :borderRadius="comp.props?.borderRadius" :objectFit="comp.props?.objectFit" />
        <diy-image-cube v-else-if="comp.type === 'image-cube'"
          :rows="comp.props?.rows" :cols="comp.props?.cols" :gap="comp.props?.gap"
          :borderRadius="comp.props?.borderRadius" :items="comp.props?.items" />
        <diy-article-list v-else-if="comp.type === 'article-list'"
          :source="comp.props?.source" :category_id="comp.props?.category_id"
          :limit="comp.props?.limit" :layout="comp.props?.layout"
          :showSummary="comp.props?.showSummary" :showViewCount="comp.props?.showViewCount"
          :showDate="comp.props?.showDate" :article_list="comp.props?.article_list" />
    </view>
  </view>
</template>
<script setup lang="ts">
import { computed } from 'vue'
import { componentStyleToCss } from './styleUtils'
const props = defineProps<{ components: any[] }>()
// "components" is a Vue reserved option name; alias to avoid MP compilation issues
const list = computed(() => (props.components || []).filter((c: any) => c && c.props && !c.hidden))
function getStyle(comp: any) { return componentStyleToCss(comp.props?.componentStyle, 'uniapp') }
</script>
