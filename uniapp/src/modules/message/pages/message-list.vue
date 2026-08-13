<template>
  <d-page :safe-area="true">
    <view class="message-page">
      <view class="toolbar">
        <view class="toolbar__left">
          <text class="toolbar__count">共 {{ total }} 条消息</text>
          <text v-if="unreadCount > 0" class="toolbar__unread">{{ unreadCount }} 条未读</text>
        </view>
        <text
          class="toolbar__action"
          :class="{ 'toolbar__action--disabled': total === 0 || unreadCount === 0 }"
          @tap="onReadAll"
        >
          全部已读
        </text>
      </view>

      <scroll-view scroll-y class="message-scroll" @scrolltolower="getList">
        <view
          v-for="item in list"
          :key="item.id"
          class="msg-card"
          :class="{ 'msg-card--unread': !item.is_read }"
          @tap="handleTap(item)"
        >
          <view class="msg-card__icon" :class="`msg-card__icon--${item.type || 'system'}`">
            <d-icon :name="getTypeIcon(item.type)" size="36rpx" :color="getTypeColor(item.type)" />
          </view>

          <view class="msg-card__body">
            <view class="msg-card__header">
              <view class="msg-card__type">
                <view v-if="!item.is_read" class="msg-card__dot" />
                <text>{{ getTypeLabel(item.type) }}</text>
              </view>
              <text class="msg-card__time">{{ formatTime(item.created_at) }}</text>
            </view>
            <text class="msg-card__title">{{ item.title }}</text>
            <text class="msg-card__content">{{ item.content }}</text>
          </view>

          <d-icon name="arrow-right" size="28rpx" color="#c0c4cc" />
        </view>

        <d-list-loader
          :loading="loading"
          :finished="finished"
          :total="total"
          empty-text="暂无消息"
        />
      </scroll-view>
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { onShow, onPullDownRefresh } from '@dcloudio/uni-app'
import { useMessageList } from '@/hooks/useMessageList'

const { list, loading, finished, total, getList, refresh, formatTime, handleTap, handleReadAll } = useMessageList()

const unreadCount = computed(() => list.value.filter(item => !item.is_read).length)

const typeMap: Record<string, string> = {
  system: '系统通知',
  order: '订单消息',
  payment: '支付通知',
  feedback: '反馈消息',
  activity: '活动消息',
}

const typeIconMap: Record<string, string> = {
  system: 'bell',
  order: 'box',
  payment: 'wallet',
  feedback: 'clipboard',
  activity: 'ticket',
}

const typeColorMap: Record<string, string> = {
  system: '#2979ff',
  order: '#07c160',
  payment: '#f59e0b',
  feedback: '#6366f1',
  activity: '#ef4444',
}

function getTypeLabel(type: string): string {
  return typeMap[type] || '通知'
}

function getTypeIcon(type: string): string {
  return typeIconMap[type] || 'bell'
}

function getTypeColor(type: string): string {
  return typeColorMap[type] || '#2979ff'
}

function onReadAll() {
  if (total.value === 0 || unreadCount.value === 0) return
  handleReadAll()
}

onShow(() => {
  refresh()
})

onPullDownRefresh(async () => {
  await refresh()
  uni.stopPullDownRefresh()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

$radius: 12rpx;

.message-page {
  display: flex;
  flex-direction: column;
  height: calc(100vh - env(safe-area-inset-bottom));
  box-sizing: border-box;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20rpx;
  padding: 4rpx 0;

  &__left {
    display: flex;
    align-items: center;
    gap: 12rpx;
    min-width: 0;
  }

  &__count {
    font-size: 26rpx;
    color: $text-color-secondary;
  }

  &__unread {
    padding: 4rpx 12rpx;
    font-size: 22rpx;
    color: var(--color-primary, #{$primary-color});
    background: rgba(41, 121, 255, 0.08);
    border-radius: $radius;
  }

  &__action {
    flex-shrink: 0;
    font-size: 26rpx;
    color: var(--color-primary, #{$primary-color});
    font-weight: 500;

    &--disabled {
      color: #c0c4cc;
    }
  }
}

.message-scroll {
  flex: 1;
  height: 0;
}

.msg-card {
  display: flex;
  align-items: center;
  gap: 20rpx;
  padding: 24rpx;
  margin-bottom: 16rpx;
  background: #ffffff;
  border: 1rpx solid $border-color;
  border-radius: $radius;
  box-sizing: border-box;

  &--unread {
    background: #f7faff;
    border-color: rgba(41, 121, 255, 0.22);
  }

  &__icon {
    flex-shrink: 0;
    width: 72rpx;
    height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: $radius;
    background: #f4f6f8;

    &--system { background: rgba(41, 121, 255, 0.1); }
    &--order { background: rgba(7, 193, 96, 0.1); }
    &--payment { background: rgba(245, 158, 11, 0.12); }
    &--feedback { background: rgba(99, 102, 241, 0.1); }
    &--activity { background: rgba(239, 68, 68, 0.1); }
  }

  &__body {
    flex: 1;
    min-width: 0;
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10rpx;
    gap: 16rpx;
  }

  &__type {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: 50%;
    background: $danger-color;
    flex-shrink: 0;
  }

  &__time {
    flex-shrink: 0;
    font-size: 22rpx;
    color: #b0b6c0;
  }

  &__title {
    display: block;
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
    margin-bottom: 8rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__content {
    display: block;
    font-size: 24rpx;
    color: $text-color-secondary;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}
</style>
