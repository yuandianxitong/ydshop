<template>
  <view class="discover-page">
    <!-- Category Tabs -->
    <u-tabs
      :list="tabList"
      :current="tabCurrent"
      @click="handleTabClick"
    />

    <!-- Article List -->
    <view class="article-list">
      <view
        v-for="(item, index) in list"
        :key="item.id"
        class="article-card"
        :class="{ 'article-card--large': index === 0 && item.cover }"
        @tap="goDetail(item.id)"
      >
        <!-- First item: large cover card -->
        <template v-if="index === 0 && item.cover">
          <image
            class="article-card__cover-large"
            :src="appStore.getImageUrl(item.cover)"
            mode="aspectFill"
          />
          <view class="article-card__body">
            <text class="article-card__title">{{ item.title }}</text>
            <view class="article-card__meta">
              <text v-if="item.category_name" class="article-card__tag">{{ item.category_name }}</text>
              <text class="article-card__date">{{ formatDate(item.published_at || item.created_at) }}</text>
            </view>
          </view>
        </template>

        <!-- Other items: horizontal card (cover left, text right) -->
        <template v-else>
          <image
            v-if="item.cover"
            class="article-card__thumb"
            :src="appStore.getImageUrl(item.cover)"
            mode="aspectFill"
          />
          <view class="article-card__content">
            <text class="article-card__title">{{ item.title }}</text>
            <text v-if="item.summary" class="article-card__summary">{{ item.summary }}</text>
            <view class="article-card__meta">
              <text v-if="item.category_name" class="article-card__tag">{{ item.category_name }}</text>
              <text class="article-card__date">{{ formatDate(item.published_at || item.created_at) }}</text>
            </view>
          </view>
        </template>
      </view>
    </view>

    <d-list-loader
      :loading="loading"
      :finished="finished"
      :total="total"
      empty-text="暂无文章"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'
import { articleApi, type ArticleItem, type ArticleCategory } from '@/api/article'
import { usePaging } from '@/hooks/usePaging'

const appStore = useAppStore()
const categories = ref<ArticleCategory[]>([])
const tabCurrent = ref(0)

const tabList = computed(() => {
  const items: { name: string; id: number }[] = [{ name: '全部', id: 0 }]
  categories.value.forEach(cat => items.push({ name: cat.name, id: cat.id }))
  return items
})

const filterParams = reactive<{ category_id?: number }>({})

const { list, loading, finished, refreshing, total, getList, refresh } = usePaging<ArticleItem>({
  fetchFun: (params) =>
    articleApi.getList({
      ...params,
      ...(filterParams.category_id ? { category_id: filterParams.category_id } : {}),
    }),
})

function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  return dateStr.substring(0, 10)
}

function handleTabClick(item: { index: number }) {
  tabCurrent.value = item.index
  const tab = tabList.value[item.index]
  if (tab.id === 0) {
    filterParams.category_id = undefined
  } else {
    filterParams.category_id = tab.id
  }
  refresh()
}

function handleRefresh() {
  refresh()
}

function goDetail(id: number) {
  uni.navigateTo({ url: `/modules/article/pages/article-detail?id=${id}` })
}

// Load categories
function loadCategories() {
  articleApi
    .getCategoryList()
    .then((res) => {
      categories.value = res
    })
    .catch(() => {})
}

onShow(() => {
  if (categories.value.length === 0) {
    loadCategories()
  }
  if (list.value.length === 0) {
    getList()
  }
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.discover-page {
  min-height: 100vh;
  background-color: var(--color-bg, #{$bg-color});
}

.article-list {
  padding: 20rpx $page-padding;
  padding-bottom: env(safe-area-inset-bottom);
}

.article-card {
  background: #ffffff;
  border-radius: 16rpx;
  margin-bottom: 20rpx;
  overflow: hidden;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

  &--large {
    .article-card__cover-large {
      width: 100%;
      height: 340rpx;
    }

    .article-card__body {
      padding: 24rpx;
    }
  }

  &:not(&--large) {
    display: flex;
    align-items: center;
    padding: 24rpx;
  }

  &__content {
    flex: 1;
    min-width: 0;
    margin-left: 20rpx;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  &__title {
    display: block;
    font-size: 28rpx;
    font-weight: 500;
    color: var(--color-text, #{$text-color});
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 8rpx;
  }

  &__summary {
    display: block;
    font-size: 24rpx;
    color: $text-color-secondary;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 8rpx;
  }

  &__meta {
    display: flex;
    align-items: center;
    gap: 16rpx;
  }

  &__tag {
    font-size: 22rpx;
    color: var(--color-primary, #{$primary-color});
    background: rgba($primary-color, 0.1);
    padding: 4rpx 14rpx;
    border-radius: 8rpx;
  }

  &__date {
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__thumb {
    width: 180rpx;
    height: 120rpx;
    border-radius: 12rpx;
    flex-shrink: 0;
  }
}
</style>
