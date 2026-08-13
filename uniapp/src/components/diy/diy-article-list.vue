<template>
  <view v-if="(article_list?.length ?? 0) > 0" class="diy-article-list">
      <!-- Big image layout -->
      <template v-if="layout === 'big-image'">
        <navigator
          v-for="item in article_list"
          :key="item.id"
          :url="`/modules/content/pages/article-detail?id=${item.id}`"
          class="diy-article-list__item-big"
        >
          <image
            v-if="item.cover"
            :src="appStore.getImageUrl(item.cover)"
            mode="aspectFill"
            class="diy-article-list__big-img"
          />
          <view class="diy-article-list__big-info">
            <text class="diy-article-list__title">{{ item.title }}</text>
            <text v-if="showSummary !== false && item.summary" class="diy-article-list__summary">{{ item.summary }}</text>
            <view class="diy-article-list__meta">
              <text v-if="showDate !== false">{{ (item.publish_at || '').slice(0, 10) }}</text>
              <text v-if="showViewCount !== false">{{ item.view_count || 0 }}阅读</text>
            </view>
          </view>
        </navigator>
      </template>
      <!-- Text only layout -->
      <template v-else-if="layout === 'text-only'">
        <navigator
          v-for="item in article_list"
          :key="item.id"
          :url="`/modules/content/pages/article-detail?id=${item.id}`"
          class="diy-article-list__item-text"
        >
          <text class="diy-article-list__title">{{ item.title }}</text>
          <text v-if="showSummary !== false && item.summary" class="diy-article-list__summary">{{ item.summary }}</text>
          <view class="diy-article-list__meta">
            <text v-if="showDate !== false">{{ (item.publish_at || '').slice(0, 10) }}</text>
            <text v-if="showViewCount !== false">{{ item.view_count || 0 }}阅读</text>
          </view>
        </navigator>
      </template>
      <!-- Left image layout (default) -->
      <template v-else>
        <navigator
          v-for="item in article_list"
          :key="item.id"
          :url="`/modules/content/pages/article-detail?id=${item.id}`"
          class="diy-article-list__item"
        >
          <image
            v-if="item.cover"
            :src="appStore.getImageUrl(item.cover)"
            mode="aspectFill"
            class="diy-article-list__img"
          />
          <view class="diy-article-list__info">
            <text class="diy-article-list__title">{{ item.title }}</text>
            <text v-if="showSummary !== false && item.summary" class="diy-article-list__summary">{{ item.summary }}</text>
            <view class="diy-article-list__meta">
              <text v-if="showDate !== false">{{ (item.publish_at || '').slice(0, 10) }}</text>
              <text v-if="showViewCount !== false">{{ item.view_count || 0 }}阅读</text>
            </view>
          </view>
        </navigator>
      </template>
  </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/store/app.store'
const appStore = useAppStore()
defineProps<{
  source?: string
  category_id?: number
  limit?: number
  layout?: string
  showSummary?: boolean
  showViewCount?: boolean
  showDate?: boolean
  article_list?: any[]
}>()
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-article-list {
  padding: 0 24rpx;
}
.diy-article-list__item {
  display: flex;
  gap: 20rpx;
  padding: 20rpx 0;
  border-bottom: 1rpx solid $border-color;
}
.diy-article-list__img {
  width: 160rpx;
  height: 120rpx;
  border-radius: 12rpx;
  flex-shrink: 0;
}
.diy-article-list__info {
  flex: 1;
  min-width: 0;
}
.diy-article-list__title {
  font-size: 28rpx;
  font-weight: 600;
  color: $text-color;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  display: block;
}
.diy-article-list__summary {
  font-size: 24rpx;
  color: $text-color-secondary;
  margin-top: 8rpx;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  display: block;
}
.diy-article-list__meta {
  font-size: 22rpx;
  color: #ccc;
  margin-top: 8rpx;
  display: flex;
  gap: 24rpx;
}
.diy-article-list__item-big {
  margin-bottom: 24rpx;
  display: block;
}
.diy-article-list__big-img {
  width: 100%;
  height: 240rpx;
  border-radius: 12rpx;
}
.diy-article-list__big-info {
  padding: 16rpx 0;
}
.diy-article-list__item-text {
  padding: 20rpx 0;
  border-bottom: 1rpx solid $border-color;
  display: block;
}
</style>
