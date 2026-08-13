<template>
  <page-meta
    :page-style="themePageStyle"
    :navigation-bar-background-color="navBg"
    :navigation-bar-text-style="navText"
  />
  <view class="my-page">
    <!-- ===== Header with gradient ===== -->
    <view class="header" :style="{ paddingTop: statusBarHeight + 'px' }">
      <!-- Decorative bubbles -->
      <view class="header__bubble header__bubble--1" />
      <view class="header__bubble header__bubble--2" />
      <view class="header__bubble header__bubble--3" />
      <!-- 设置图标（右上角） -->
      <view
        class="header__settings"
        :style="{ top: statusBarHeight + 36 + 'px' }"
        @tap="goAuthPage('/modules/user/pages/settings')"
      >
        <d-icon name="gear" size="44rpx" color="#ffffff" />
      </view>
      <!-- User info row -->
      <view class="user-row" @tap="goUserAction">
        <view class="avatar-wrap">
          <image
            v-if="userStore.isLoggedIn && userStore.avatar"
            class="avatar"
            :src="avatarUrl"
            mode="aspectFill"
          />
          <view v-else class="default-avatar">
            <d-icon name="user" size="60rpx" color="rgba(255,255,255,0.9)" />
          </view>
        </view>
        <view class="user-text">
          <text v-if="userStore.isLoggedIn" class="user-name">{{ userStore.nickname || '未设置昵称' }}</text>
          <text v-else class="user-name">登录/注册</text>
          <text v-if="userStore.isLoggedIn" class="user-sub">{{ maskMobile(userStore.userInfo?.mobile) }}</text>
        </view>
      </view>

      <!-- Stats row -->
      <view class="stats-row">
        <view class="stats-item" @tap="goAuthPage('/modules/favorite/pages/list')">
          <text class="stats-num">{{ userStore.isLoggedIn ? balanceInfo.favorites : 0 }}</text>
          <text class="stats-label">收藏商品</text>
        </view>
        <view class="stats-item" @tap="goAuthPage('/modules/user/pages/points')">
          <text class="stats-num">{{ userStore.isLoggedIn ? balanceInfo.points : 0 }}</text>
          <text class="stats-label">我的积分</text>
        </view>
        <view class="stats-item" @tap="goAuthPage('/modules/user/pages/balance')">
          <text class="stats-num">{{ userStore.isLoggedIn ? balanceInfo.balance : '0.00' }}</text>
          <text class="stats-label">我的余额</text>
        </view>
      </view>
    </view>

    <!-- ===== Body cards ===== -->
    <view class="body">

      <!-- Order card -->
      <view class="card">
        <view class="card__header">
          <text class="card__title">我购买的订单</text>
          <text class="card__link" @tap="goOrderList(0)">查看全部订单</text>
        </view>
        <view class="order-grid">
          <view class="order-item" @tap="goOrderList(1)">
            <view class="order-item__badge-wrap">
              <d-icon name="wallet" size="48rpx" color="#ff9900" />
              <text v-if="orderCounts.pending > 0" class="order-item__badge">{{ formatBadge(orderCounts.pending) }}</text>
            </view>
            <text class="order-item__text">待付款</text>
          </view>
          <view class="order-item" @tap="goOrderList(2)">
            <view class="order-item__badge-wrap">
              <d-icon name="box" size="48rpx" color="#2979ff" />
              <text v-if="orderCounts.paid > 0" class="order-item__badge">{{ formatBadge(orderCounts.paid) }}</text>
            </view>
            <text class="order-item__text">待发货</text>
          </view>
          <view class="order-item" @tap="goOrderList(3)">
            <view class="order-item__badge-wrap">
              <d-icon name="send" size="48rpx" color="#19be6b" />
              <text v-if="orderCounts.shipped > 0" class="order-item__badge">{{ formatBadge(orderCounts.shipped) }}</text>
            </view>
            <text class="order-item__text">待收货</text>
          </view>
          <view class="order-item" @tap="goOrderList(4)">
            <view class="order-item__badge-wrap">
              <d-icon name="check-line" size="48rpx" color="#7c4dff" />
            </view>
            <text class="order-item__text">已完成</text>
          </view>
          <view class="order-item" @tap="goOrderList(5)">
            <view class="order-item__badge-wrap">
              <d-icon name="clipboard" size="48rpx" color="#fa3534" />
              <text v-if="orderCounts.refunding > 0" class="order-item__badge">{{ formatBadge(orderCounts.refunding) }}</text>
            </view>
            <text class="order-item__text">退款/售后</text>
          </view>
        </view>
      </view>

      <!-- Common functions card -->
      <view class="card">
        <view class="card__header">
          <text class="card__title">常用功能</text>
        </view>
        <view class="func-grid">
          <view v-if="hasDistribution" class="func-item" @tap="goAuthPage('/modules/distribution/pages/index')">
            <d-icon name="diagram" size="48rpx" color="#ff9900" />
            <text class="func-item__text">分销中心</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/user/pages/balance')">
            <d-icon name="wallet" size="48rpx" color="#2979ff" />
            <text class="func-item__text">钱包</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/message/pages/message-list')">
            <view class="func-item__icon-wrap">
              <d-icon name="bell" size="48rpx" color="#7c4dff" />
              <text v-if="unreadCount > 0" class="func-item__badge">{{ formatBadge(unreadCount) }}</text>
            </view>
            <text class="func-item__text">消息中心</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/address/pages/list')">
            <d-icon name="location" size="48rpx" color="#19be6b" />
            <text class="func-item__text">收货地址</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/marketing/pages/coupon')">
            <d-icon name="ticket" size="48rpx" color="#7c4dff" />
            <text class="func-item__text">优惠券</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/user/pages/sign')">
            <d-icon name="time" size="48rpx" color="#71717a" />
            <text class="func-item__text">签到</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/marketing/pages/points-mall')">
            <d-icon name="tag" size="48rpx" color="#71717a" />
            <text class="func-item__text">积分商城</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/user/pages/edit-profile')">
            <d-icon name="user" size="48rpx" color="#2979ff" />
            <text class="func-item__text">个人资料</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/order/pages/invoice-list')">
            <d-icon name="file-text" size="48rpx" color="#71717a" />
            <text class="func-item__text">发票管理</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/order/pages/refund-list')">
            <d-icon name="shield" size="48rpx" color="#fa3534" />
            <text class="func-item__text">我的售后</text>
          </view>
          <view class="func-item" @tap="goAuthPage('/modules/feedback/pages/feedback?tab=history')">
            <d-icon name="clipboard" size="48rpx" color="#19be6b" />
            <text class="func-item__text">我的反馈</text>
          </view>
        </view>
      </view>
    </view>
    <d-tabbar current-path="/pages/my/index" />
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/user.store'
import { useAppStore } from '@/store/app.store'
import { userApi } from '@/api/user'
import { memberApi } from '@/api/member'
import { orderApi } from '@/api/order'
import { messageApi } from '@/api/message'
import { useThemePageStyle } from '@/composables/useThemePageStyle'
import { redirectToLogin } from '@/utils/login-redirect'

const { themePageStyle, navBg, navText } = useThemePageStyle()

const userStore = useUserStore()
const appStore = useAppStore()
const hasDistribution = computed(() => {
  const plugins = appStore.config?.installed_plugins
  return Array.isArray(plugins) && plugins.includes('distribution')
})

const statusBarHeight = ref(0)
const balanceInfo = ref({ balance: '0.00', points: 0, favorites: 0 })
const orderCounts = ref({ pending: 0, paid: 0, shipped: 0, refunding: 0 })
const unreadCount = ref(0)

function formatBadge(n: number): string {
  if (n <= 0) return ''
  return n > 99 ? '99+' : String(n)
}

async function loadOrderCounts() {
  if (!userStore.isLoggedIn) {
    orderCounts.value = { pending: 0, paid: 0, shipped: 0, refunding: 0 }
    return
  }
  try {
    const data = await orderApi.getOrderCounts()
    orderCounts.value = {
      pending: data?.pending ?? 0,
      paid: data?.paid ?? 0,
      shipped: data?.shipped ?? 0,
      refunding: data?.refunding ?? 0,
    }
  } catch {
    // ignore
  }
}

async function loadUnreadCount() {
  if (!userStore.isLoggedIn) {
    unreadCount.value = 0
    return
  }
  try {
    const data = await messageApi.getUnreadCount()
    unreadCount.value = Number(data?.count || 0)
  } catch {
    unreadCount.value = 0
  }
}

try {
  const sysInfo = uni.getSystemInfoSync()
  statusBarHeight.value = sysInfo.statusBarHeight || 0
} catch {
  statusBarHeight.value = 44
}

const avatarUrl = computed(() => {
  if (userStore.isLoggedIn && userStore.avatar) {
    return appStore.getImageUrl(userStore.avatar)
  }
  return ''
})

function maskMobile(mobile?: string): string {
  if (!mobile || mobile.length < 11) return mobile || ''
  return mobile.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

function goUserAction() {
  if (userStore.isLoggedIn) {
    uni.navigateTo({ url: '/modules/user/pages/edit-profile' })
  } else {
    redirectToLogin()
  }
}

function goAuthPage(url: string) {
  if (!userStore.isLoggedIn) {
    redirectToLogin(url)
    return
  }
  uni.navigateTo({ url })
}

function goOrderList(status: number) {
  if (!userStore.isLoggedIn) {
    redirectToLogin(`/modules/order/pages/list?status=${status}`)
    return
  }
  uni.navigateTo({ url: `/modules/order/pages/list?status=${status}` })
}

function loadAssets() {
  if (!userStore.isLoggedIn) {
    balanceInfo.value = { balance: '0.00', points: 0, favorites: 0 }
    return
  }
  Promise.all([
    userApi.getBalance(),
    userApi.getPoints(),
    memberApi.getFavoriteList({ page_no: 1, page_size: 1 }),
  ]).then(([balRes, ptsRes, favRes]) => {
    balanceInfo.value.balance = balRes.balance || '0.00'
    balanceInfo.value.points = ptsRes.points || 0
    balanceInfo.value.favorites = (favRes as any)?.pagination?.total ?? 0
  }).catch(() => {
    // ignore
  })
}

onShow(() => {
  if (userStore.isLoggedIn && !userStore.userInfo) {
    userStore.getUserInfo().catch(() => {
      userStore.logout({ redirect: false })
    })
  }
  loadAssets()
  loadOrderCounts()
  loadUnreadCount()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.my-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: calc(100rpx + env(safe-area-inset-bottom));
}

// ===== Header =====
.header {
  background: var(--color-primary, #{$primary-color});
  border-radius: 0 0 40rpx 40rpx;
  padding-bottom: 36rpx;
  position: relative;
  overflow: hidden;

  &__bubble {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);

    &--1 {
      width: 240rpx;
      height: 240rpx;
      top: -60rpx;
      right: -40rpx;
    }

    &--2 {
      width: 160rpx;
      height: 160rpx;
      top: 80rpx;
      right: 120rpx;
      background: rgba(255, 255, 255, 0.06);
    }

    &--3 {
      width: 200rpx;
      height: 200rpx;
      bottom: -40rpx;
      left: -20rpx;
      background: rgba(255, 255, 255, 0.05);
    }
  }

  &__settings {
    position: absolute;
    right: 24rpx;
    width: 64rpx;
    height: 64rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
  }
}

.user-row {
  display: flex;
  align-items: center;
  padding: 90rpx 40rpx 58rpx;
  position: relative;
  z-index: 1;
}

.avatar-wrap {
  width: 112rpx;
  height: 112rpx;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 4rpx solid rgba(255, 255, 255, 0.4);

  .avatar {
    width: 100%;
    height: 100%;
  }

  .default-avatar {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.user-text {
  flex: 1;
  margin-left: 28rpx;
}

.user-name {
  display: block;
  font-size: 36rpx;
  font-weight: 700;
  color: #ffffff;
}

.user-sub {
  display: block;
  font-size: 26rpx;
  color: rgba(255, 255, 255, 0.75);
  margin-top: 6rpx;
}

.stats-row {
  display: flex;
  padding: 0 40rpx;
  position: relative;
  z-index: 1;
}

.stats-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stats-num {
  font-size: 36rpx;
  font-weight: 700;
  color: #ffffff;
}

.stats-label {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.75);
  margin-top: 4rpx;
}

// ===== Body =====
.body {
  padding: 20rpx 24rpx;
  padding-bottom: 40rpx;
}

.card {
  background: #ffffff;
  border-radius: 20rpx;
  margin-bottom: 20rpx;
  overflow: hidden;

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28rpx 32rpx 16rpx;
  }

  &__title {
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__link {
    font-size: 24rpx;
    color: $text-color-secondary;
  }
}

// ===== Order grid =====
.order-grid {
  display: flex;
  padding: 16rpx 0 28rpx;
}

.order-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12rpx;

  &__badge-wrap {
    position: relative;
  }

  &__badge {
    position: absolute;
    top: -8rpx;
    right: -16rpx;
    min-width: 32rpx;
    height: 32rpx;
    padding: 0 8rpx;
    background: var(--color-danger, #fa3534);
    color: #fff;
    font-size: 20rpx;
    line-height: 32rpx;
    text-align: center;
    border-radius: 16rpx;
    box-sizing: border-box;
  }

  &__text {
    font-size: 24rpx;
    color: var(--color-text, #{$text-color});
  }
}

// ===== Function grid =====
.func-grid {
  display: flex;
  flex-wrap: wrap;
  padding: 16rpx 0 8rpx;
}

.func-item {
  width: 20%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 28rpx;

  &__icon-wrap {
    position: relative;
  }

  &__badge {
    position: absolute;
    top: -10rpx;
    right: -22rpx;
    min-width: 30rpx;
    height: 30rpx;
    padding: 0 7rpx;
    color: #fff;
    font-size: 18rpx;
    line-height: 30rpx;
    text-align: center;
    background: var(--color-danger, #fa3534);
    border: 2rpx solid #fff;
    border-radius: 16rpx;
    box-sizing: border-box;
  }

  &__text {
    font-size: 24rpx;
    color: var(--color-text, #{$text-color});
  }
}
</style>
