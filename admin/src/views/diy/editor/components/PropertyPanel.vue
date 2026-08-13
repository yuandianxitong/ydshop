<template>
  <div class="property-panel">
    <template v-if="selectedComponent">
      <div class="property-panel__header">
        <span>{{ getLabel(selectedComponent.type) }}</span>
      </div>
      <el-tabs v-model="activeTab" stretch class="property-panel__tabs">
        <el-tab-pane label="内容" name="content">
          <div class="property-panel__body">
            <component
              :is="getPropsComponent(selectedComponent.type)"
              :model-value="selectedComponent.props"
              @update:model-value="onUpdate"
            />
          </div>
        </el-tab-pane>
        <el-tab-pane label="样式" name="style">
          <div class="property-panel__body">
            <StyleConfig
              :model-value="selectedComponent.props.componentStyle || {}"
              @update:model-value="onStyleUpdate"
            />
          </div>
        </el-tab-pane>
        <el-tab-pane label="高级" name="advanced">
          <div class="property-panel__body">
            <component
              v-if="getAdvancedComponent(selectedComponent.type)"
              :is="getAdvancedComponent(selectedComponent.type)"
              :model-value="selectedComponent.props"
              @update:model-value="onUpdate"
            />
            <el-empty v-else description="无高级设置" :image-size="60" />
          </div>
        </el-tab-pane>
      </el-tabs>
    </template>
    <template v-else-if="pageSettingsActive">
      <div class="property-panel__header">
        <span>页面设置</span>
      </div>
      <div class="property-panel__body">
        <PageSettingsPanel />
      </div>
    </template>
    <div v-else class="property-panel__empty">
      <el-empty description="请选择一个组件" :image-size="80" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { componentDefs, useEditor } from '../useEditor'
import StyleConfig from './StyleConfig.vue'
import PageSettingsPanel from './PageSettingsPanel.vue'

import PropsBanner from '../properties/PropsBanner.vue'
import PropsImageAd from '../properties/PropsImageAd.vue'
import PropsRichText from '../properties/PropsRichText.vue'
import PropsTitleBar from '../properties/PropsTitleBar.vue'
import PropsDivider from '../properties/PropsDivider.vue'
import PropsGoodsGrid from '../properties/PropsGoodsGrid.vue'
import PropsCategoryNav from '../properties/PropsCategoryNav.vue'
import PropsSearchBar from '../properties/PropsSearchBar.vue'
import PropsCouponList from '../properties/PropsCouponList.vue'
import PropsSeckill from '../properties/PropsSeckill.vue'
import PropsNotice from '../properties/PropsNotice.vue'
import PropsNavGrid from '../properties/PropsNavGrid.vue'
import PropsFloatButton from '../properties/PropsFloatButton.vue'
import PropsVideo from '../properties/PropsVideo.vue'
import PropsImageCube from '../properties/PropsImageCube.vue'
import PropsArticleList from '../properties/PropsArticleList.vue'
import PropsAdSlot from '../properties/PropsAdSlot.vue'

import AdvancedBanner from '../advanced/AdvancedBanner.vue'
import AdvancedImageAd from '../advanced/AdvancedImageAd.vue'
import AdvancedTitleBar from '../advanced/AdvancedTitleBar.vue'
import AdvancedDivider from '../advanced/AdvancedDivider.vue'
import AdvancedGoodsGrid from '../advanced/AdvancedGoodsGrid.vue'
import AdvancedCategoryNav from '../advanced/AdvancedCategoryNav.vue'
import AdvancedSearchBar from '../advanced/AdvancedSearchBar.vue'
import AdvancedCouponList from '../advanced/AdvancedCouponList.vue'
import AdvancedSeckill from '../advanced/AdvancedSeckill.vue'
import AdvancedNotice from '../advanced/AdvancedNotice.vue'
import AdvancedNavGrid from '../advanced/AdvancedNavGrid.vue'
import AdvancedFloatButton from '../advanced/AdvancedFloatButton.vue'
import AdvancedVideo from '../advanced/AdvancedVideo.vue'
import AdvancedImageCube from '../advanced/AdvancedImageCube.vue'
import AdvancedArticleList from '../advanced/AdvancedArticleList.vue'

const advancedMap: Record<string, any> = {
    'banner': AdvancedBanner, 'image-ad': AdvancedImageAd,
    'title-bar': AdvancedTitleBar, 'divider': AdvancedDivider,
    'goods-grid': AdvancedGoodsGrid, 'category-nav': AdvancedCategoryNav,
    'search-bar': AdvancedSearchBar, 'coupon-list': AdvancedCouponList,
    'seckill': AdvancedSeckill, 'notice': AdvancedNotice,
    'nav-grid': AdvancedNavGrid, 'float-button': AdvancedFloatButton,
    'video': AdvancedVideo,
    'image-cube': AdvancedImageCube,
    'article-list': AdvancedArticleList,
}

const { selectedComponent, updateProps, pageSettingsActive } = useEditor()
const activeTab = ref('content')

const propsMap: Record<string, any> = {
    'banner': PropsBanner, 'image-ad': PropsImageAd, 'rich-text': PropsRichText,
    'title-bar': PropsTitleBar, 'divider': PropsDivider, 'goods-grid': PropsGoodsGrid,
    'category-nav': PropsCategoryNav, 'search-bar': PropsSearchBar, 'coupon-list': PropsCouponList,
    'seckill': PropsSeckill, 'notice': PropsNotice, 'nav-grid': PropsNavGrid, 'float-button': PropsFloatButton,
    'video': PropsVideo,
    'image-cube': PropsImageCube,
    'article-list': PropsArticleList,
    'ad-slot': PropsAdSlot,
}

function getLabel(type: string) { return componentDefs.find(d => d.type === type)?.label || type }
function getPropsComponent(type: string) { return propsMap[type] || null }
function getAdvancedComponent(type: string) { return advancedMap[type] || null }

function onUpdate(props: Record<string, any>) {
    if (selectedComponent.value) updateProps(selectedComponent.value.id, props)
}

function onStyleUpdate(style: any) {
    if (selectedComponent.value) updateProps(selectedComponent.value.id, { componentStyle: style })
}
</script>

<style lang="scss" scoped>
.property-panel {
    display: flex;
    flex-direction: column;
    height: 100%;

    &__header {
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 500;
        border-bottom: 1px solid #e4e7ed;
        flex-shrink: 0;
    }

    &__tabs {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;

        :deep(.el-tabs__header) {
            padding: 0;
            margin-bottom: 0;
        }

        :deep(.el-tabs__nav-wrap::after) {
            height: 1px;
        }

        :deep(.el-tabs__content) {
            flex: 1;
            overflow-y: auto;
        }
    }

    &__body { padding: 16px; }

    &__empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
    }
}
</style>
