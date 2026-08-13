<template>
  <view class="d-store-card" :class="{ 'd-store-card--readonly': readonly }" @tap="onTap">
    <view class="d-store-card__head">
      <view class="d-store-card__name">{{ store.name }}</view>
      <view class="d-store-card__badge" :class="store.is_open_now ? 'is-open' : 'is-closed'">
        {{ store.is_open_now ? '营业中' : '已歇业' }}
      </view>
    </view>
    <view class="d-store-card__addr">
      <d-icon name="location" size="24rpx" :color="textSec" />
      <text class="d-store-card__addr-text">{{ store.address }}</text>
    </view>
    <view class="d-store-card__meta">
      <view v-if="store.phone" class="d-store-card__phone" @tap.stop="onCall">
        <d-icon name="phone-call" size="24rpx" :color="textSec" />
        <text>{{ store.phone }}</text>
      </view>
      <text v-if="store.distance != null" class="d-store-card__dist">{{ store.distance }} km</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import type { Store } from '@/api/store'

const props = defineProps<{
  store: Store
  readonly?: boolean
}>()

const emit = defineEmits<{ select: [store: Store] }>()

const textSec = 'var(--color-text-2, #71717a)'

function onTap() {
  if (props.readonly) return
  emit('select', props.store)
}

function onCall() {
  if (props.store.phone) uni.makePhoneCall({ phoneNumber: props.store.phone })
}
</script>

<style lang="scss" scoped>
.d-store-card {
  background: var(--color-bg, #fff);
  padding: 28rpx;
  border-radius: 16rpx;
  display: flex;
  flex-direction: column;
  gap: 12rpx;

  &__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  &__name {
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #18181b);
  }

  &__badge {
    font-size: 22rpx;
    padding: 2rpx 12rpx;
    border-radius: 8rpx;

    &.is-open {
      background: rgba(16, 185, 129, 0.1);
      color: var(--color-success, #10b981);
    }

    &.is-closed {
      background: var(--color-bg-3, #f4f4f5);
      color: var(--color-text-3, #a1a1aa);
    }
  }

  &__addr {
    font-size: 26rpx;
    color: var(--color-text-2, #71717a);
    display: flex;
    align-items: flex-start;
    gap: 8rpx;
  }

  &__addr-text {
    flex: 1;
    line-height: 1.4;
  }

  &__meta {
    display: flex;
    justify-content: space-between;
    font-size: 24rpx;
    color: var(--color-text-2, #71717a);
  }

  &__phone {
    display: flex;
    align-items: center;
    gap: 6rpx;
  }

  &__dist {
    color: var(--color-primary, #2979ff);
  }
}
</style>
