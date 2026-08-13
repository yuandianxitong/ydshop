<template>
  <page-meta
    :page-style="themePageStyle"
    :navigation-bar-background-color="navBg"
    :navigation-bar-text-style="navText"
  />
  <view class="home-page">
    <!-- 加载中：尚无 DIY 数据 -->
    <view v-if="showLoading" class="home-state" :style="{ paddingTop: statusBarHeight + 'px' }">
      <u-loading-icon size="60rpx" />
      <text class="home-state__text">加载中...</text>
    </view>

    <!-- DIY 页面 -->
    <view
      v-else-if="pageComponents.length"
      :style="[{ paddingTop: statusBarHeight + 'px' }, pageContainerStyle]"
    >
      <view
        v-if="pageSettings.show_header !== false && (pageTitle || pageSettings.background_color)"
        class="diy-page-titlebar"
        :style="{ backgroundColor: pageSettings.background_color || '' }"
      >
        <text class="diy-page-titlebar__text">{{ pageTitle }}</text>
      </view>
      <DiyRenderer :components="pageComponents" />
      <DiyPopupAd :ad="pageSettings.popup_ad" page-key="home_uniapp" />
    </view>

    <!-- 已结束加载但无装修内容 -->
    <view v-else class="home-state" :style="{ paddingTop: statusBarHeight + 'px' }">
      <d-empty :text="loadFailed ? '首页加载失败，下拉重试' : '首页暂未装修'" />
    </view>

    <d-tabbar current-path="/pages/index/index" />
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow, onPullDownRefresh } from '@dcloudio/uni-app'
import { storeToRefs } from 'pinia'
import { useAppStore } from '@/store/app.store'
import { useDiyStore } from '@/store/diy.store'
import DiyRenderer from '@/components/diy/DiyRenderer.vue'
import DiyPopupAd from '@/components/diy/DiyPopupAd.vue'
import { useThemePageStyle } from '@/composables/useThemePageStyle'

const DIY_TYPE = 'home'
const DIY_PLATFORM = 'uniapp'
const DIY_KEY = `${DIY_TYPE}:${DIY_PLATFORM}`

const { themePageStyle, navBg, navText } = useThemePageStyle()
const appStore = useAppStore()
const diyStore = useDiyStore()
const { pages, loading, failed } = storeToRefs(diyStore)

const statusBarHeight = ref(0)
try {
  statusBarHeight.value = uni.getSystemInfoSync().statusBarHeight || 0
} catch {
  statusBarHeight.value = 44
}

const diyPage = computed(() => pages.value[DIY_KEY] || null)
const pageComponents = computed(() => diyPage.value?.components || [])
const pageTitle = computed(() => diyPage.value?.title || '')
const pageSettings = computed(() => diyPage.value?.page_settings || {})
const diyLoading = computed(() => !!loading.value[DIY_KEY])
const loadFailed = computed(() => !!failed.value[DIY_KEY])
/** 无缓存/无数据且正在请求时展示加载态，避免先闪默认排版 */
const showLoading = computed(() => diyLoading.value && pageComponents.value.length === 0)

const pageContainerStyle = computed(() => {
  const s = pageSettings.value
  const style: Record<string, string> = {}
  if (s.background_color) style.backgroundColor = s.background_color
  if (s.background_image) {
    style.backgroundImage = `url(${appStore.getImageUrl(s.background_image)})`
    style.backgroundSize = 'cover'
    style.backgroundPosition = 'center'
  }
  return style
})

async function loadDiyPage() {
  await diyStore.loadPage(DIY_TYPE, DIY_PLATFORM)
}

onPullDownRefresh(async () => {
  await loadDiyPage()
  uni.stopPullDownRefresh()
})

onShow(() => {
  loadDiyPage()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.home-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});
  padding-bottom: calc(100rpx + env(safe-area-inset-bottom));
}

.home-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 24rpx;

  &__text {
    font-size: 26rpx;
    color: $text-color-secondary;
  }
}

.diy-page-titlebar {
  height: 88rpx;
  display: flex;
  align-items: center;
  justify-content: center;

  &__text {
    font-size: 32rpx;
    font-weight: 500;
    color: var(--color-text, #{$text-color});
  }
}
</style>
