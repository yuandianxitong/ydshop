<template>
  <d-page>
    <view v-if="detail" class="detail">
      <text class="detail__title">{{ detail.title }}</text>
      <view class="detail__meta">
        <text class="detail__type" :class="`type-${detail.type}`">{{ typeLabel(detail.type) }}</text>
        <text class="detail__time">{{ formatTime(detail.publish_at) }}</text>
      </view>
      <view class="detail__divider" />
      <view class="detail__content">
        <rich-text :nodes="detail.content" />
      </view>
    </view>
    <view v-else-if="loading" class="detail-skeleton">
      <view class="detail-skeleton__title" />
      <view class="detail-skeleton__meta" />
      <view v-for="i in 5" :key="i" class="detail-skeleton__line" />
    </view>
    <d-empty v-else-if="!loading" text="公告不存在" />
  </d-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { announcementApi, type AnnouncementItem } from '@/api/announcement'

const detail = ref<AnnouncementItem | null>(null)
const loading = ref(true)

function typeLabel(type: number): string {
  return ({ 1: '通知', 2: '更新', 3: '活动' } as Record<number, string>)[type] || '公告'
}

function formatTime(value: string): string {
  return value ? value.replace('T', ' ').slice(0, 16) : ''
}

onLoad(async (options) => {
  const id = parseInt(options?.id || '0', 10)
  if (id > 0) {
    try {
      detail.value = await announcementApi.getDetail(id)
    } catch {
      detail.value = null
    }
  }
  loading.value = false
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.detail {
  background: #ffffff;
  border-radius: 16rpx;
  padding: 40rpx 32rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

  &__title {
    display: block;
    font-size: 36rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
    line-height: 1.5;
    margin-bottom: 20rpx;
  }

  &__meta {
    display: flex;
    align-items: center;
    gap: 16rpx;
    margin-bottom: 24rpx;
  }

  &__time {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__type {
    padding: 7rpx 15rpx;
    font-size: 21rpx;
    border-radius: 22rpx;
  }

  &__divider {
    height: 1rpx;
    background: $border-color;
    margin-bottom: 30rpx;
  }

  &__content {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.8;
  }
}

.type-1 { color: #175cd3; background: #eff8ff; }
.type-2 { color: #b54708; background: #fff4e8; }
.type-3 { color: #067647; background: #ecfdf3; }

.detail-skeleton {
  padding: 40rpx 32rpx;
  background: #fff;
  border-radius: 16rpx;

  &__title, &__meta, &__line {
    background: linear-gradient(90deg, #f1f2f4 25%, #fafafa 50%, #f1f2f4 75%);
    background-size: 200% 100%;
    border-radius: 8rpx;
    animation: shimmer 1.3s infinite;
  }
  &__title { width: 76%; height: 42rpx; }
  &__meta { width: 38%; height: 26rpx; margin: 28rpx 0 50rpx; }
  &__line { height: 26rpx; margin-bottom: 24rpx; }
}

@keyframes shimmer { to { background-position: -200% 0; } }
</style>
