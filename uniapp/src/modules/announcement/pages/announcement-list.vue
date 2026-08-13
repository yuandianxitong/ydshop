<template>
  <d-page>
    <view class="announcement-head">
      <view>
        <text class="announcement-head__title">平台公告</text>
        <text class="announcement-head__hint">重要通知、功能更新与活动动态</text>
      </view>
      <text class="announcement-head__count">{{ total }} 条</text>
    </view>
    <scroll-view
      scroll-y
      class="announcement-scroll"
      @scrolltolower="getList"
    >
      <view
        v-for="item in list"
        :key="item.id"
        class="announcement-item"
        @tap="goDetail(item.id)"
      >
        <view class="announcement-item__header">
          <text class="announcement-item__type" :class="`type-${item.type}`">{{ typeLabel(item.type) }}</text>
          <text class="announcement-item__time">{{ formatTime(item.publish_at) }}</text>
        </view>
        <text class="announcement-item__title">{{ item.title }}</text>
        <text class="announcement-item__summary">{{ plainText(item.content) }}</text>
        <view class="announcement-item__footer">
          <text>查看详情</text>
          <d-icon name="arrow-right" size="24rpx" color="#98a2b3" />
        </view>
      </view>

      <d-list-loader
        :loading="loading"
        :finished="finished"
        :total="total"
        empty-text="暂无公告"
      />
    </scroll-view>
  </d-page>
</template>

<script setup lang="ts">
import { onPullDownRefresh } from '@dcloudio/uni-app'
import { announcementApi, type AnnouncementItem } from '@/api/announcement'
import { usePaging } from '@/hooks/usePaging'

const { list, loading, finished, total, getList, refresh } = usePaging<AnnouncementItem>({
  fetchFun: (params) => announcementApi.getList(params),
})

function goDetail(id: number) {
  uni.navigateTo({ url: `/modules/announcement/pages/announcement-detail?id=${id}` })
}

function typeLabel(type: number): string {
  return ({ 1: '通知', 2: '更新', 3: '活动' } as Record<number, string>)[type] || '公告'
}

function formatTime(value: string): string {
  return value ? value.replace('T', ' ').slice(0, 16) : ''
}

function plainText(content: string): string {
  return String(content || '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;|&#160;/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 90) || '点击查看公告详情'
}

onPullDownRefresh(async () => {
  await refresh()
  uni.stopPullDownRefresh()
})

getList()
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.announcement-scroll {
  height: calc(100vh - 220rpx);
}

.announcement-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 22rpx;
  padding: 24rpx 26rpx;
  color: #fff;
  background: linear-gradient(125deg, #246fe5, #5e8ff0);
  border-radius: 20rpx;

  &__title, &__hint { display: block; }
  &__title { font-size: 32rpx; font-weight: 650; }
  &__hint { margin-top: 6rpx; color: rgba(255,255,255,.78); font-size: 22rpx; }
  &__count { padding: 8rpx 16rpx; font-size: 22rpx; background: rgba(255,255,255,.16); border-radius: 24rpx; }
}

.announcement-item {
  background: #ffffff;
  border: 1rpx solid #e9edf2;
  border-radius: 18rpx;
  padding: 27rpx 30rpx;
  margin-bottom: 20rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16rpx;
  }

  &__time {
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__type {
    padding: 6rpx 14rpx;
    font-size: 21rpx;
    border-radius: 20rpx;
  }

  &__title {
    display: block;
    font-size: 30rpx;
    color: var(--color-text, #{$text-color});
    font-weight: 500;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__summary {
    display: -webkit-box;
    overflow: hidden;
    margin-top: 12rpx;
    color: #7b8492;
    font-size: 24rpx;
    line-height: 1.6;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
  }

  &__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4rpx;
    margin-top: 18rpx;
    padding-top: 16rpx;
    color: #98a2b3;
    font-size: 22rpx;
    border-top: 1rpx solid #f0f2f5;
  }
}

.type-1 { color: #175cd3; background: #eff8ff; }
.type-2 { color: #b54708; background: #fff4e8; }
.type-3 { color: #067647; background: #ecfdf3; }
</style>
