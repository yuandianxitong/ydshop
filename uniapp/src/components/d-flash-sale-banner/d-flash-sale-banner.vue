<template>
  <view v-if="active" class="d-flash-sale-banner">
    <view class="d-flash-sale-banner__left">
      <view class="d-flash-sale-banner__price">
        <text class="currency">¥</text>
        <text class="value">{{ Number(matchedItem?.flash_price ?? 0).toFixed(2) }}</text>
      </view>
      <view class="d-flash-sale-banner__original">¥{{ Number(matchedItem?.sku?.price ?? 0).toFixed(2) }}</view>
    </view>

    <view class="d-flash-sale-banner__right">
      <view class="d-flash-sale-banner__tag">秒杀</view>
      <view class="d-flash-sale-banner__countdown">
        距结束 {{ countdown }}
      </view>
      <view class="d-flash-sale-banner__progress">
        <view class="d-flash-sale-banner__progress-bar" :style="{ width: progressWidth + '%' }" />
        <text class="d-flash-sale-banner__progress-text">已抢 {{ progressWidth }}%</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import type { FlashSaleMatched } from '@/api/marketing'

const props = defineProps<{ sale: FlashSaleMatched | null }>()
const emit = defineEmits<{ (e: 'expired'): void }>()

const now = ref(Date.now())
let timer: any = null

// iOS Safari / 部分 mp 不接受 "YYYY-MM-DD HH:mm:ss" 格式，转 ISO 8601
function parseDate(s?: string): number {
  if (!s) return 0
  return new Date(String(s).replace(' ', 'T')).getTime() || 0
}

const active = computed(() => {
  if (!props.sale) return false
  const end = parseDate(props.sale.end_at)
  return end > 0 && now.value < end
})

const matchedItem = computed(() => props.sale?.matched_item as any)

const countdown = computed(() => {
  if (!props.sale) return '00:00:00'
  const end = parseDate(props.sale.end_at)
  let diff = Math.max(0, end - now.value)
  const h = Math.floor(diff / 3600000); diff -= h * 3600000
  const m = Math.floor(diff / 60000); diff -= m * 60000
  const s = Math.floor(diff / 1000)
  return [h, m, s].map(n => String(n).padStart(2, '0')).join(':')
})

const progressWidth = computed(() => {
  if (!matchedItem.value) return 0
  const stock = Number(matchedItem.value.flash_stock ?? 0)
  const sold = Number(matchedItem.value.sold_count ?? 0)
  if (stock + sold <= 0) return 0
  const pct = (sold / (stock + sold)) * 100
  return Math.max(0, Math.min(99, Math.round(pct)))
})

onMounted(() => {
  timer = setInterval(() => {
    now.value = Date.now()
    const end = props.sale ? parseDate(props.sale.end_at) : 0
    if (end > 0 && Date.now() >= end) {
      emit('expired')
      if (timer) { clearInterval(timer); timer = null }
    }
  }, 1000)
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.d-flash-sale-banner {
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, #ff5252 0%, #ff8a8a 100%);
  border-radius: 16rpx;
  padding: 24rpx;
  margin: 16rpx 0;
  color: #fff;

  &__left {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-right: 32rpx;
  }
  &__price {
    display: flex;
    align-items: baseline;
    .currency { font-size: 28rpx; }
    .value { font-size: 60rpx; font-weight: 800; line-height: 1; margin-left: 4rpx; }
  }
  &__original {
    font-size: 22rpx;
    text-decoration: line-through;
    opacity: 0.7;
    margin-top: 8rpx;
  }

  &__right {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8rpx;
  }
  &__tag {
    background: #fff;
    color: #ff5252;
    font-size: 22rpx;
    font-weight: 700;
    padding: 4rpx 14rpx;
    border-radius: 8rpx;
  }
  &__countdown {
    font-size: 26rpx;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
  }
  &__progress {
    position: relative;
    width: 280rpx;
    height: 24rpx;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 12rpx;
    overflow: hidden;
    &-bar {
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      background: #fff;
    }
    &-text {
      position: absolute;
      left: 50%;
      top: 0;
      bottom: 0;
      transform: translateX(-50%);
      line-height: 24rpx;
      font-size: 18rpx;
      color: #ff5252;
      font-weight: 700;
    }
  }
}
</style>
