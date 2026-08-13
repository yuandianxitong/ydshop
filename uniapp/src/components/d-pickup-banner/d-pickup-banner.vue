<template>
  <view class="d-pickup-banner">
    <view class="d-pickup-banner__title">自提码</view>
    <view class="d-pickup-banner__code">{{ formattedCode }}</view>
    <view v-if="qrSrc" class="d-pickup-banner__qr">
      <image :src="qrSrc" mode="aspectFit" />
    </view>
    <view class="d-pickup-banner__store">
      <d-store-card :store="store" :readonly="true" />
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Store } from '@/api/store'

const props = defineProps<{
  code: string
  store: Store
  qrSrc?: string
}>()

const formattedCode = computed(() => (props.code ?? '').replace(/(\d{3})(\d{3})/, '$1 $2') || '-')
</script>

<style lang="scss" scoped>
.d-pickup-banner {
  background: var(--color-bg, #fff);
  padding: 32rpx 28rpx;
  border-radius: 16rpx;
  display: flex;
  flex-direction: column;
  gap: 24rpx;
  align-items: center;

  &__title {
    font-size: 26rpx;
    color: var(--color-text-2, #71717a);
  }

  &__code {
    font-size: 64rpx;
    font-weight: 700;
    letter-spacing: 12rpx;
    color: var(--color-primary, #2979ff);
    font-family: monospace;
  }

  &__qr {
    image {
      width: 280rpx;
      height: 280rpx;
    }
  }

  &__store {
    width: 100%;
  }
}
</style>
