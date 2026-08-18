<template>
  <view class="coupon-page">

    <!-- Tabs -->
    <view class="coupon-page__tabs">
      <view
        class="coupon-page__tab"
        :class="{ 'coupon-page__tab--active': activeTab === 'available' }"
        @tap="setTab('available')"
      >
        <text class="coupon-page__tab-text">可领取</text>
      </view>
      <view
        class="coupon-page__tab"
        :class="{ 'coupon-page__tab--active': activeTab === 'my' }"
        @tap="setTab('my')"
      >
        <text class="coupon-page__tab-text">我的优惠券</text>
      </view>
    </view>

    <!-- Available coupons -->
    <scroll-view
      v-if="activeTab === 'available'"
      scroll-y
      class="coupon-page__scroll"
      refresher-enabled
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
    >
      <view class="coupon-page__content">

        <view v-if="availableLoading && availableList.length === 0" class="coupon-page__skeleton">
          <view v-for="i in 4" :key="i" class="coupon-page__skeleton-item" />
        </view>

        <template v-else-if="availableList.length > 0">
          <view
            v-for="coupon in availableList"
            :key="coupon.id"
            class="coupon-card"
          >
            <view class="coupon-card__left">
              <view class="coupon-card__value-wrap">
                <text v-if="coupon.type === 'fixed'" class="coupon-card__amount">
                  <text class="coupon-card__unit">¥</text>{{ coupon.value }}
                </text>
                <text v-else-if="coupon.type === 'percent'" class="coupon-card__amount">
                  {{ coupon.value }}<text class="coupon-card__unit">折</text>
                </text>
                <text v-else class="coupon-card__amount coupon-card__amount--sm">无门槛</text>
              </view>
              <text class="coupon-card__condition">
                {{ Number(coupon.min_amount) > 0 ? `满 ${coupon.min_amount} 元可用` : '无门槛' }}
              </text>
            </view>
            <view class="coupon-card__right">
              <view class="coupon-card__header">
                <view class="coupon-card__type-tag">
                  <text>{{ couponTypeText(coupon.type) }}</text>
                </view>
              </view>
              <text class="coupon-card__name">{{ coupon.name }}</text>
              <text class="coupon-card__validity">
                {{ (coupon.start_at || '').substring(0, 10) }} ~ {{ (coupon.end_at || '').substring(0, 10) }}
              </text>
              <view
                v-if="!coupon.has_claimed"
                class="coupon-card__btn"
                :class="{ 'coupon-card__btn--loading': claimingId === coupon.id }"
                @tap="handleClaim(coupon)"
              >
                <text>{{ claimingId === coupon.id ? '领取中...' : '立即领取' }}</text>
              </view>
              <view v-else class="coupon-card__btn coupon-card__btn--disabled">
                <text>已领取</text>
              </view>
            </view>
          </view>
        </template>

        <d-list-loader
          :loading="availableLoading && availableList.length > 0"
          :finished="true"
          empty-text="暂无可领取的优惠券"
        />
      </view>
    </scroll-view>

    <!-- My coupons -->
    <view v-if="activeTab === 'my'" class="coupon-page__my">

      <!-- Filter tabs -->
      <scroll-view scroll-x class="coupon-page__filter-scroll" enhanced>
        <view class="coupon-page__filter-inner">
          <view
            v-for="f in myFilters"
            :key="f.value"
            class="coupon-page__filter-item"
            :class="{ 'coupon-page__filter-item--active': myFilter === f.value }"
            @tap="setMyFilter(f.value)"
          >
            <text>{{ f.label }}</text>
          </view>
        </view>
      </scroll-view>

      <scroll-view
        scroll-y
        class="coupon-page__my-scroll"
        refresher-enabled
        :refresher-triggered="myRefreshing"
        @refresherrefresh="onMyRefresh"
        @scrolltolower="loadMoreMy"
      >
        <view class="coupon-page__content">
          <view v-if="myLoading && myList.length === 0" class="coupon-page__skeleton">
            <view v-for="i in 4" :key="i" class="coupon-page__skeleton-item" />
          </view>

          <template v-else-if="myList.length > 0">
            <view
              v-for="coupon in myList"
              :key="coupon.id"
              class="coupon-card"
              :class="coupon.status !== 'unused' ? 'coupon-card--used' : ''"
            >
              <view class="coupon-card__left">
                <view class="coupon-card__value-wrap">
                  <text v-if="coupon.type === 'fixed'" class="coupon-card__amount">
                    <text class="coupon-card__unit">¥</text>{{ coupon.value }}
                  </text>
                  <text v-else-if="coupon.type === 'percent'" class="coupon-card__amount">
                    {{ coupon.value }}<text class="coupon-card__unit">折</text>
                  </text>
                  <text v-else class="coupon-card__amount coupon-card__amount--sm">无门槛</text>
                </view>
                <text class="coupon-card__condition">
                  {{ Number(coupon.min_amount) > 0 ? `满 ${coupon.min_amount} 元可用` : '无门槛' }}
                </text>
              </view>
              <view class="coupon-card__right">
                <view class="coupon-card__header">
                  <view
                    class="coupon-card__status-tag"
                    :class="`coupon-card__status-tag--${coupon.status === 'unused' ? 'active' : 'used'}`"
                  >
                    <text>{{ couponStatusText(coupon.status) }}</text>
                  </view>
                </view>
                <text class="coupon-card__name">{{ coupon.name }}</text>
                <text class="coupon-card__validity">
                  {{ (coupon.start_at || '').substring(0, 10) }} ~ {{ (coupon.end_at || '').substring(0, 10) }}
                </text>
                <view
                  v-if="coupon.status === 'unused'"
                  class="coupon-card__btn coupon-card__btn--use"
                  @tap="goShop"
                >
                  <text>去使用</text>
                </view>
              </view>
            </view>
          </template>

          <d-list-loader
            :loading="myLoading && myList.length > 0"
            :finished="myFinished"
            :total="myTotal"
            empty-text="暂无优惠券"
          />
        </view>
      </scroll-view>
    </view>

  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { marketingApi, type CouponItem, type MyCouponItem } from '@/api/marketing'

const PAGE_SIZE = 10

// Tabs
const activeTab = ref<'available' | 'my'>('available')

function setTab(tab: 'available' | 'my') {
  activeTab.value = tab
  if (tab === 'available') {
    fetchAvailable()
  } else {
    fetchMyCoupons(true)
  }
}

// ---- Available ----
const availableList = ref<CouponItem[]>([])
const availableLoading = ref(false)
const refreshing = ref(false)

function couponTypeText(type: CouponItem['type']): string {
  if (type === 'fixed') return '满减券'
  if (type === 'percent') return '折扣券'
  if (type === 'no_threshold') return '无门槛'
  return ''
}

async function fetchAvailable() {
  if (availableLoading.value) return

  availableLoading.value = true
  try {
    const res = await marketingApi.getReceivableCoupons()
    availableList.value = (res as unknown as CouponItem[]) || []
  } catch {
    // handled by request layer
  } finally {
    availableLoading.value = false
    refreshing.value = false
  }
}

async function onRefresh() {
  refreshing.value = true
  await fetchAvailable()
}

// Claim coupon
const claimingId = ref<number | null>(null)

async function handleClaim(coupon: CouponItem) {
  if (claimingId.value === coupon.id) return
  claimingId.value = coupon.id
  try {
    await marketingApi.claimCoupon(coupon.id)
    uni.showToast({ title: '领取成功！', icon: 'success' })
    fetchAvailable()
  } catch {
    // handled
  } finally {
    claimingId.value = null
  }
}

// ---- My coupons ----
const myFilters = [
  { label: '全部', value: '' },
  { label: '未使用', value: 'unused' },
  { label: '已使用', value: 'used' },
  { label: '已过期', value: 'expired' },
] as const
const myFilter = ref<'' | 'unused' | 'used' | 'expired'>('')
const myList = ref<MyCouponItem[]>([])
const myLoading = ref(false)
const myRefreshing = ref(false)
const myFinished = ref(false)
const myTotal = ref(0)
const myPage = ref(1)

async function fetchMyCoupons(reset = false) {
  if (myLoading.value && !reset) return

  myLoading.value = true
  try {
    const res = await marketingApi.getMyCoupons({
      status: myFilter.value || undefined,
      page_no: myPage.value,
      page_size: PAGE_SIZE,
    })
    myList.value = reset ? res.list : [...myList.value, ...res.list]
    myTotal.value = res.pagination.total
    myFinished.value = myList.value.length >= myTotal.value
    if (!myFinished.value) myPage.value += 1
  } catch {
    // handled
  } finally {
    myLoading.value = false
    myRefreshing.value = false
  }
}

function setMyFilter(val: '' | 'unused' | 'used' | 'expired') {
  myFilter.value = val
  myPage.value = 1
  fetchMyCoupons(true)
}

function couponStatusText(status: MyCouponItem['status']): string {
  return status === 'unused' ? '未使用' : status === 'used' ? '已使用' : '已过期'
}

async function onMyRefresh() {
  myRefreshing.value = true
  await fetchMyCoupons(true)
}

function loadMoreMy() {
  if (!myFinished.value && !myLoading.value) {
    fetchMyCoupons()
  }
}

function goShop() {
  uni.switchTab({ url: '/pages/index/index' })
}

onLoad(() => {
  fetchAvailable()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.coupon-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});
  display: flex;
  flex-direction: column;

  &__tabs {
    background: #ffffff;
    display: flex;
    border-bottom: 1rpx solid $border-color;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  &__tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24rpx 0;
    border-bottom: 4rpx solid transparent;

    &--active {
      border-bottom-color: var(--color-primary, #{$primary-color});

      .coupon-page__tab-text {
        color: var(--color-primary, #{$primary-color});
        font-weight: 500;
      }
    }
  }

  &__tab-text {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
  }

  &__scroll {
    flex: 1;
    height: calc(100vh - 96rpx);
  }

  &__content {
    padding: 20rpx 20rpx 40rpx;
  }

  &__skeleton {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
  }

  &__skeleton-item {
    height: 180rpx;
    background: #ffffff;
    border-radius: 16rpx;
    animation: pulse 1.5s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  &__my {
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  &__filter-scroll {
    background: #ffffff;
    white-space: nowrap;
  }

  &__filter-inner {
    display: inline-flex;
    padding: 16rpx 20rpx;
    gap: 16rpx;
  }

  &__filter-item {
    padding: 10rpx 28rpx;
    border-radius: 40rpx;
    border: 1rpx solid $border-color;
    font-size: 24rpx;
    color: var(--color-text, #{$text-color});
    flex-shrink: 0;

    &--active {
      background: var(--color-primary, #{$primary-color});
      border-color: var(--color-primary, #{$primary-color});
      color: #ffffff;
    }
  }

  &__my-scroll {
    flex: 1;
    height: calc(100vh - 200rpx);
  }
}

// Coupon card
.coupon-card {
  display: flex;
  background: #ffffff;
  border-radius: 16rpx;
  overflow: hidden;
  margin-bottom: 20rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);

  &--used {
    opacity: 0.55;
  }

  &__left {
    background: var(--color-primary, #{$primary-color});
    color: #ffffff;
    padding: 32rpx 24rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 160rpx;
  }

  &__value-wrap {
    text-align: center;
  }

  &__amount {
    font-size: 64rpx;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;

    &--sm {
      font-size: 32rpx;
    }
  }

  &__unit {
    font-size: 26rpx;
    font-weight: 400;
  }

  &__condition {
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 8rpx;
    white-space: nowrap;
  }

  &__right {
    flex: 1;
    padding: 24rpx 28rpx;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
  }

  &__header {
    display: flex;
    align-items: center;
  }

  &__type-tag {
    padding: 4rpx 16rpx;
    background: rgba(41, 121, 255, 0.1);
    border-radius: 20rpx;

    text {
      font-size: 22rpx;
      color: var(--color-primary, #{$primary-color});
      font-weight: 500;
    }
  }

  &__status-tag {
    padding: 4rpx 16rpx;
    border-radius: 20rpx;

    text { font-size: 22rpx; font-weight: 500; }

    &--active {
      background: rgba(41, 121, 255, 0.1);
      text { color: var(--color-primary, #{$primary-color}); }
    }
    &--used {
      background: #f5f5f5;
      text { color: $text-color-secondary; }
    }
    &--expired {
      background: rgba(250, 53, 52, 0.1);
      text { color: $danger-color; }
    }
  }

  &__name {
    font-size: 28rpx;
    font-weight: 500;
    color: var(--color-text, #{$text-color});
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__validity {
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__btn {
    margin-top: 8rpx;
    padding: 12rpx 28rpx;
    background: var(--color-primary, #{$primary-color});
    border-radius: 40rpx;
    align-self: flex-start;

    text {
      font-size: 24rpx;
      color: #ffffff;
      font-weight: 500;
    }

    &--loading {
      opacity: 0.6;
    }

    &--disabled {
      background: #ccc;
      pointer-events: none;
      opacity: 0.6;

      text { color: #fff; }
    }

    &--use {
      background: transparent;
      border: 1rpx solid var(--color-primary, #{$primary-color});

      text { color: var(--color-primary, #{$primary-color}); }
    }
  }
}
</style>
