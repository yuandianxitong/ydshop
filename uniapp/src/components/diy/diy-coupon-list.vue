<template>
  <view v-if="couponList.length > 0" class="diy-coupon-list">
    <view class="diy-coupon-list__header">
      <text class="diy-coupon-list__title">领取你的专属优惠券</text>
      <view class="diy-coupon-list__more" @tap="goMore">
        <text>全部优惠券</text>
        <d-icon name="arrow-right" size="22rpx" color="#ffffff" />
      </view>
    </view>

    <scroll-view scroll-x class="diy-coupon-list__scroll">
      <view class="diy-coupon-list__inner">
        <view
          v-for="(c, idx) in couponList"
          :key="c.id"
          class="diy-coupon-list__item"
        >
          <view
            v-if="idx < couponList.length - 1"
            class="diy-coupon-list__divider"
          />
          <text class="diy-coupon-list__amount">{{ c.amount_text }}</text>
          <text class="diy-coupon-list__desc">{{ c.threshold_text }}</text>
          <view class="diy-coupon-list__btn" @tap="onClaim(c)">
            <text>立即领取</text>
          </view>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import { marketingApi } from '@/api/marketing'

interface CouponEntry {
  id: number
  name: string
  type: string
  value: number
  min_amount: number
  amount_text: string
  threshold_text: string
  start_at: string
  end_at: string
}

const props = defineProps<{
  coupon_ids?: number[]
  style?: string
  coupon_list?: CouponEntry[]
}>()

const couponList = computed<CouponEntry[]>(() => props.coupon_list ?? [])

function goMore() {
  uni.navigateTo({ url: '/modules/marketing/pages/coupon' })
}

async function onClaim(c: CouponEntry) {
  try {
    await marketingApi.claimCoupon(c.id)
    uni.showToast({ title: '领取成功', icon: 'success' })
  } catch (e: any) {
    const msg = e?.message || '领取失败'
    // 后端把"未登录 / 已领取 / 已领完"都用 BusinessException 返回，统一 toast
    uni.showToast({ title: msg, icon: 'none' })
  }
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-coupon-list {
  margin: 16rpx 24rpx;
  padding: 24rpx 20rpx 20rpx;
  border-radius: 16rpx;
  background: linear-gradient(135deg, #ff8c6e 0%, #ff5a4a 100%);
  box-shadow: 0 8rpx 20rpx rgba(255, 90, 74, 0.22);
}

.diy-coupon-list__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 4rpx;
  margin-bottom: 18rpx;
}

.diy-coupon-list__title {
  font-size: 30rpx;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 1rpx;
}

.diy-coupon-list__more {
  display: flex;
  align-items: center;
  gap: 4rpx;
  font-size: 22rpx;
  color: #ffffff;
  opacity: 0.92;
}

.diy-coupon-list__scroll {
  width: 100%;
  white-space: nowrap;
}

.diy-coupon-list__inner {
  display: inline-flex;
  padding: 0 2rpx;
}

.diy-coupon-list__item {
  position: relative;
  flex-shrink: 0;
  width: 168rpx;
  padding: 16rpx 12rpx 12rpx;
  border-radius: 10rpx;
  background: #fff1ec;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.diy-coupon-list__item:not(:first-child) {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

.diy-coupon-list__item:not(:last-child) {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

// 相邻优惠券连接处：上下凹口 + 竖向虚线，形成联券效果。
.diy-coupon-list__divider {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  width: 0;
  border-right: 2rpx dashed #ffb9a7;
  z-index: 2;
  pointer-events: none;
}

.diy-coupon-list__divider::before,
.diy-coupon-list__divider::after {
  content: '';
  position: absolute;
  left: 50%;
  width: 16rpx;
  height: 16rpx;
  border-radius: 50%;
  background: #ff7359;
  transform: translateX(-50%);
}

.diy-coupon-list__divider::before {
  top: -8rpx;
}

.diy-coupon-list__divider::after {
  bottom: -8rpx;
}

.diy-coupon-list__amount {
  font-size: 36rpx;
  font-weight: 800;
  line-height: 1.1;
  color: #f5483b;
  font-variant-numeric: tabular-nums;
}

.diy-coupon-list__desc {
  margin-top: 6rpx;
  font-size: 22rpx;
  color: #f5483b;
}

.diy-coupon-list__btn {
  margin-top: 12rpx;
  width: 100%;
  background: #f5483b;
  border-radius: 6rpx;
  padding: 10rpx 0;
  display: flex;
  align-items: center;
  justify-content: center;

  text {
    font-size: 22rpx;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: 1rpx;
  }
}
</style>
