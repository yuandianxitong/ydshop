<template>
  <view v-if="goodsList.length > 0" class="diy-seckill">
    <view class="diy-seckill__content">
      <view class="diy-seckill__header">
        <view class="diy-seckill__head-left">
          <text class="diy-seckill__title">限时秒杀</text>
          <view v-if="sessionLabel" class="diy-seckill__tag">
            <text>{{ sessionLabel }}</text>
          </view>
          <view v-if="showCountdown" class="diy-seckill__countdown">
            <text class="diy-seckill__time-block">{{ hh }}</text>
            <text class="diy-seckill__time-sep">:</text>
            <text class="diy-seckill__time-block">{{ mm }}</text>
            <text class="diy-seckill__time-sep">:</text>
            <text class="diy-seckill__time-block">{{ ss }}</text>
          </view>
        </view>
        <view class="diy-seckill__more" @tap="goMore">
          <text>查看更多</text>
          <d-icon name="arrow-right" size="20rpx" color="#999999" />
        </view>
      </view>
      <scroll-view scroll-x class="diy-seckill__scroll">
        <view class="diy-seckill__list">
          <view
            v-for="item in goodsList"
            :key="item.item_id"
            class="diy-seckill__item"
            @tap="goDetail(item)"
          >
            <view class="diy-seckill__img-wrap">
              <image
                class="diy-seckill__img"
                :src="item.cover || '/static/placeholder.png'"
                mode="aspectFill"
              />
            </view>
            <text class="diy-seckill__name">{{ item.name }}</text>
            <view class="diy-seckill__price-row">
              <text class="diy-seckill__price">¥{{ formatPrice(item.flash_price) }}</text>
              <text v-if="item.original_price > item.flash_price" class="diy-seckill__origin">
                ¥{{ formatPrice(item.original_price) }}
              </text>
            </view>
            <view class="diy-seckill__btn" @tap.stop="goDetail(item)">
              <text>立即抢购</text>
              <d-icon name="arrow-right" size="18rpx" color="#ffffff" />
            </view>
          </view>
        </view>
      </scroll-view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'

interface SeckillGoods {
  item_id: number
  sku_id: number
  spu_id: number
  name: string
  cover: string
  flash_price: number
  original_price: number
  stock: number
  sold_count: number
}
interface SeckillData {
  id: number
  name: string
  start_at: string
  end_at: string
  session_label: string
  goods: SeckillGoods[]
}

const props = defineProps<{
  activity_id?: number
  limit?: number
  seckill_data?: SeckillData | null
}>()

const goodsList = computed<SeckillGoods[]>(() => {
  const list = props.seckill_data?.goods ?? []
  const lim = props.limit ?? list.length
  return list.slice(0, lim)
})

const sessionLabel = computed(() => props.seckill_data?.session_label ?? '')

// 倒计时：DB datetime 字符串需把空格换成 'T'，否则 iOS Safari 解析失败
const endsAtMs = computed(() => {
  const s = props.seckill_data?.end_at
  if (!s) return 0
  const t = new Date(s.replace(' ', 'T')).getTime()
  return Number.isFinite(t) ? t : 0
})

const showCountdown = computed(() => endsAtMs.value > 0)

const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

const remainSec = computed(() => Math.max(0, Math.floor((endsAtMs.value - now.value) / 1000)))
const hh = computed(() => pad(Math.floor(remainSec.value / 3600)))
const mm = computed(() => pad(Math.floor((remainSec.value % 3600) / 60)))
const ss = computed(() => pad(remainSec.value % 60))

function pad(n: number) {
  return n < 10 ? `0${n}` : String(n)
}

function formatPrice(v: number) {
  return Number.isFinite(v) ? Number(v).toFixed(2).replace(/\.00$/, '') : '0'
}

watch(
  endsAtMs,
  (v) => {
    if (timer) { clearInterval(timer); timer = null }
    if (v > 0) {
      now.value = Date.now()
      timer = setInterval(() => {
        now.value = Date.now()
        // 到期后自停，避免在长驻首页上无限触发响应式重算
        if (now.value >= endsAtMs.value && timer) {
          clearInterval(timer)
          timer = null
        }
      }, 1000)
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

function goMore() {
  uni.navigateTo({ url: '/modules/marketing/pages/flash-sale' })
}

function goDetail(item: SeckillGoods) {
  if (item?.spu_id) {
    uni.navigateTo({ url: `/modules/goods/pages/detail?id=${item.spu_id}` })
  }
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-seckill {
  margin: 16rpx 24rpx;
  padding: 0;
}

.diy-seckill__content {
  padding: 20rpx 18rpx 18rpx;
  border-radius: 18rpx;
  background: #ffffff;
  box-shadow: 0 6rpx 22rpx rgba(15, 23, 42, 0.06);
}

.diy-seckill__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16rpx;
}

.diy-seckill__head-left {
  display: flex;
  align-items: center;
  min-width: 0;
}

.diy-seckill__title {
  font-size: 32rpx;
  line-height: 1;
  font-weight: 800;
  color: #1f2937;
  letter-spacing: 1rpx;
  flex-shrink: 0;
}

.diy-seckill__tag {
  margin-left: 14rpx;
  height: 34rpx;
  padding: 0 10rpx;
  border-radius: 7rpx;
  background: #ff3b30;
  display: flex;
  align-items: center;
  justify-content: center;

  text {
    font-size: 20rpx;
    font-weight: 700;
    color: #ffffff;
    white-space: nowrap;
  }
}

.diy-seckill__countdown {
  margin-left: 10rpx;
  display: flex;
  align-items: center;
  gap: 4rpx;
}

.diy-seckill__time-block {
  min-width: 28rpx;
  height: 28rpx;
  padding: 0 4rpx;
  border-radius: 5rpx;
  background: #ff3b30;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18rpx;
  font-weight: 800;
  color: #ffffff;
  line-height: 28rpx;
  font-variant-numeric: tabular-nums;
}

.diy-seckill__time-sep {
  font-size: 18rpx;
  font-weight: 800;
  color: #ff3b30;
  line-height: 28rpx;
}

.diy-seckill__more {
  display: flex;
  align-items: center;
  gap: 2rpx;
  flex-shrink: 0;

  text {
    font-size: 22rpx;
    color: $text-color-secondary;
  }
}

.diy-seckill__scroll {
  width: 100%;
  white-space: nowrap;
}

.diy-seckill__list {
  display: inline-flex;
  gap: 12rpx;
}

.diy-seckill__item {
  width: 172rpx;
  padding-bottom: 4rpx;
  flex-shrink: 0;
}

.diy-seckill__img-wrap {
  width: 172rpx;
  height: 172rpx;
  border-radius: 10rpx;
  overflow: hidden;
  background: #f5f5f5;
}

.diy-seckill__img {
  width: 100%;
  height: 100%;
  display: block;
}

.diy-seckill__name {
  display: block;
  margin-top: 10rpx;
  width: 100%;
  font-size: 22rpx;
  line-height: 1.3;
  color: #4b5563;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.diy-seckill__price-row {
  margin-top: 6rpx;
  display: flex;
  align-items: baseline;
  gap: 6rpx;
}

.diy-seckill__price {
  font-size: 26rpx;
  line-height: 1;
  color: #ff2f2f;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}

.diy-seckill__origin {
  font-size: 18rpx;
  color: #b5b5b5;
  text-decoration: line-through;
  font-variant-numeric: tabular-nums;
}

.diy-seckill__btn {
  margin-top: 8rpx;
  width: 112rpx;
  height: 34rpx;
  border-radius: 999rpx;
  background: linear-gradient(90deg, #ff4c42 0%, #ff1f2d 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2rpx;
  box-shadow: 0 4rpx 10rpx rgba(255, 47, 47, 0.22);

  text {
    font-size: 18rpx;
    font-weight: 700;
    color: #ffffff;
    white-space: nowrap;
  }
}
</style>
