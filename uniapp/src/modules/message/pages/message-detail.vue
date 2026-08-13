<template>
  <d-page :safe-area="true">
    <view v-if="message" class="detail">
      <view class="detail__meta">
        <view class="detail__type">
          <view class="detail__icon" :class="`detail__icon--${message.type || 'system'}`">
            <d-icon :name="getTypeIcon(message.type)" size="32rpx" :color="getTypeColor(message.type)" />
          </view>
          <text class="detail__type-text">{{ getTypeLabel(message.type) }}</text>
        </view>
        <text class="detail__time">{{ formatTime(message.created_at) }}</text>
      </view>

      <text class="detail__title">{{ message.title }}</text>
      <view class="detail__divider" />
      <text class="detail__content">{{ message.content }}</text>

      <view v-if="actionPath" class="detail__action" @tap="goAction">
        <text class="detail__action-text">{{ actionLabel }}</text>
        <d-icon name="arrow-right" size="28rpx" color="#ffffff" />
      </view>
    </view>

    <view v-else-if="loading" class="detail-skeleton">
      <view class="detail-skeleton__head">
        <view class="detail-skeleton__icon" />
        <view class="detail-skeleton__line detail-skeleton__line--short" />
      </view>
      <view class="detail-skeleton__line detail-skeleton__line--title" />
      <view class="detail-skeleton__line" />
      <view class="detail-skeleton__line" />
      <view class="detail-skeleton__line detail-skeleton__line--mid" />
    </view>

    <d-empty v-else text="消息不存在或已失效" />
  </d-page>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { messageApi, type NotificationInfo } from '@/api/message'
import { formatRelativeTime } from '@/utils/time'

const message = ref<NotificationInfo | null>(null)
const loading = ref(true)

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

function formatTime(value: string): string {
  if (!value) return ''
  // 详情页优先展示相对时间，解析失败则回退原文
  try {
    return formatRelativeTime(value)
  } catch {
    return value.replace('T', ' ').slice(0, 16)
  }
}

const actionPath = computed(() => {
  const item = message.value
  if (!item) return ''
  const configuredPath = item.extra?.uniapp_path || item.extra?.path
  if (typeof configuredPath === 'string' && configuredPath.startsWith('/') && !configuredPath.startsWith('//')) {
    return configuredPath
  }
  if (item.type === 'order' && Number(item.biz_id || 0) > 0) {
    return `/modules/order/pages/detail?id=${item.biz_id}`
  }
  if (item.type === 'feedback') {
    return `/modules/feedback/pages/feedback?tab=history&id=${item.biz_id || ''}`
  }
  return ''
})

const actionLabel = computed(() => {
  if (message.value?.type === 'order') return '查看订单'
  if (message.value?.type === 'feedback') return '查看反馈记录'
  if (message.value?.type === 'payment') return '查看账户明细'
  return '查看相关内容'
})

function goAction() {
  const url = actionPath.value
  if (!url) return
  const pathname = url.split('?')[0]
  const tabPages = ['/pages/index/index', '/pages/category/index', '/pages/cart/index', '/pages/my/index']
  if (tabPages.includes(pathname)) {
    uni.switchTab({ url: pathname })
  } else {
    uni.navigateTo({ url })
  }
}

onLoad(async (options) => {
  const id = Number.parseInt(String(options?.id || '0'), 10)
  if (id > 0) {
    try {
      message.value = await messageApi.getDetail(id)
      if (!message.value.is_read) {
        messageApi.markAsRead([id]).then(() => {
          if (message.value) message.value.is_read = true
        }).catch(() => {})
      }
    } catch {
      message.value = null
    }
  }
  loading.value = false
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

$radius: 12rpx;

.detail {
  background: #ffffff;
  border: 1rpx solid $border-color;
  border-radius: $radius;
  padding: 28rpx 24rpx 32rpx;

  &__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 24rpx;
  }

  &__type {
    display: flex;
    align-items: center;
    gap: 12rpx;
    min-width: 0;
  }

  &__icon {
    width: 56rpx;
    height: 56rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: $radius;
    background: #f4f6f8;
    flex-shrink: 0;

    &--system { background: rgba(41, 121, 255, 0.1); }
    &--order { background: rgba(7, 193, 96, 0.1); }
    &--payment { background: rgba(245, 158, 11, 0.12); }
    &--feedback { background: rgba(99, 102, 241, 0.1); }
    &--activity { background: rgba(239, 68, 68, 0.1); }
  }

  &__type-text {
    font-size: 26rpx;
    color: $text-color-secondary;
    font-weight: 500;
  }

  &__time {
    flex-shrink: 0;
    font-size: 24rpx;
    color: #b0b6c0;
  }

  &__title {
    display: block;
    font-size: 34rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
    margin-bottom: 24rpx;
    line-height: 1.45;
  }

  &__divider {
    height: 1rpx;
    background: #f0f2f5;
    margin-bottom: 28rpx;
  }

  &__content {
    display: block;
    font-size: 28rpx;
    color: #3f4c5f;
    line-height: 1.8;
    word-break: break-word;
    white-space: pre-wrap;
  }

  &__action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    margin-top: 36rpx;
    height: 88rpx;
    background: var(--color-primary, #{$primary-color});
    border-radius: $radius;
    box-shadow: 0 8rpx 20rpx -8rpx rgba(41, 121, 255, 0.45);

    &:active {
      opacity: 0.92;
    }
  }

  &__action-text {
    font-size: 30rpx;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: 1rpx;
  }
}

.detail-skeleton {
  padding: 28rpx 24rpx;
  background: #fff;
  border: 1rpx solid $border-color;
  border-radius: $radius;

  &__head {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 28rpx;
  }

  &__icon {
    width: 56rpx;
    height: 56rpx;
    border-radius: $radius;
    background: #f1f2f4;
  }

  &__line {
    height: 28rpx;
    margin-bottom: 20rpx;
    background: linear-gradient(90deg, #f1f2f4 25%, #fafafa 50%, #f1f2f4 75%);
    background-size: 200% 100%;
    border-radius: 8rpx;
    animation: shimmer 1.3s infinite;

    &--short { width: 28%; margin-bottom: 0; }
    &--title { width: 78%; height: 40rpx; margin: 8rpx 0 36rpx; }
    &--mid { width: 62%; }
  }
}

@keyframes shimmer {
  to { background-position: -200% 0; }
}
</style>
