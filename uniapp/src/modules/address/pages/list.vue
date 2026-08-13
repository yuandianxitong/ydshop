<template>
  <view class="address-list-page">

    <scroll-view
      scroll-y
      class="address-list-page__scroll"
      refresher-enabled
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
    >
      <view class="address-list-page__content">

        <!-- Loading skeleton -->
        <view v-if="loading && list.length === 0" class="skeleton-wrap">
          <view v-for="i in 3" :key="i" class="skeleton-item" />
        </view>

        <!-- Empty state -->
        <view v-else-if="!loading && list.length === 0" class="empty-wrap">
          <d-icon name="location" size="80rpx" color="#d4d4d8" />
          <text class="empty-text">暂无收货地址</text>
        </view>

        <!-- Address cards -->
        <template v-else>
          <view
            v-for="addr in list"
            :key="addr.id"
            class="addr-card"
            :class="{ 'addr-card--default': addr.is_default === 1, 'addr-card--selected': selectMode && selectedId === addr.id }"
            @tap="handleCardTap(addr)"
          >
            <view class="addr-card__main">
              <view class="addr-card__name-row">
                <text class="addr-card__name">{{ addr.name }}</text>
                <text class="addr-card__mobile">{{ addr.phone }}</text>
                <view v-if="addr.is_default === 1" class="addr-card__default-badge">默认</view>
                <view v-if="selectMode && selectedId === addr.id" class="addr-card__check">
                  <d-icon name="check-line" size="28rpx" color="#fff" />
                </view>
              </view>
              <text class="addr-card__detail">
                {{ addr.province }} {{ addr.city }} {{ addr.district }} {{ addr.detail }}
              </text>
            </view>

            <view class="addr-card__actions">
              <view
                v-if="addr.is_default !== 1"
                class="addr-card__action-btn"
                @tap.stop="handleSetDefault(addr)"
              >
                <d-icon name="star" size="28rpx" color="#71717a" />
                <text class="addr-card__action-text">设默认</text>
              </view>
              <view
                class="addr-card__action-btn"
                @tap.stop="goEdit(addr.id)"
              >
                <d-icon name="edit" size="28rpx" color="#71717a" />
                <text class="addr-card__action-text">编辑</text>
              </view>
              <view
                class="addr-card__action-btn addr-card__action-btn--danger"
                @tap.stop="handleDelete(addr)"
              >
                <d-icon name="trash" size="28rpx" color="#ef4444" />
                <text class="addr-card__action-text addr-card__action-text--danger">删除</text>
              </view>
            </view>
          </view>
        </template>

        <view style="height: 160rpx" />
      </view>
    </scroll-view>

    <!-- Add button (fixed bottom) inline 实现 -->
    <view class="bottom-bar">
      <view class="bottom-bar__btn" @tap="goAdd">
        <d-icon name="add" size="32rpx" color="#ffffff" />
        <text class="bottom-bar__btn-text">新增收货地址</text>
      </view>
    </view>

  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { memberApi, type AddressItem } from '@/api/member'

const list = ref<AddressItem[]>([])
const loading = ref(false)
const refreshing = ref(false)

// Select mode: when navigated from checkout
const selectMode = ref(false)
const selectedId = ref<number | null>(null)

async function fetchList() {
  loading.value = true
  try {
    const result = await memberApi.getAddressList()
    list.value = Array.isArray(result) ? result : []
  } catch {
    // ignore
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

async function onRefresh() {
  refreshing.value = true
  await fetchList()
}

function handleCardTap(addr: AddressItem) {
  if (selectMode.value) {
    // Pass selected address back to checkout via globalData
    const gApp = getApp() as any
    if (!gApp.globalData) gApp.globalData = {}
    gApp.globalData.selectedAddressId = addr.id
    uni.navigateBack()
  } else {
    goEdit(addr.id)
  }
}

function goAdd() {
  uni.navigateTo({ url: '/modules/address/pages/edit' })
}

function goEdit(id: number) {
  uni.navigateTo({ url: `/modules/address/pages/edit?id=${id}` })
}

async function handleSetDefault(addr: AddressItem) {
  try {
    await memberApi.updateAddress(addr.id, {
      name: addr.name,
      phone: addr.phone,
      province: addr.province,
      city: addr.city,
      district: addr.district,
      detail: addr.detail,
      is_default: 1,
    })
    uni.showToast({ title: '已设为默认', icon: 'success' })
    await fetchList()
  } catch {
    uni.showToast({ title: '操作失败', icon: 'none' })
  }
}

function handleDelete(addr: AddressItem) {
  uni.showModal({
    title: '确认删除',
    content: '确定要删除该收货地址吗？',
    confirmText: '删除',
    confirmColor: '#fa3534',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await memberApi.deleteAddress(addr.id)
        uni.showToast({ title: '删除成功', icon: 'success' })
        await fetchList()
      } catch {
        uni.showToast({ title: '删除失败', icon: 'none' })
      }
    },
  })
}

onLoad((options) => {
  selectMode.value = options?.select === '1'
  selectedId.value = options?.selected_id ? Number(options.selected_id) : null
})

onShow(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.address-list-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});
  position: relative;

  &__scroll {
    height: 100vh;
  }

  &__content {
    padding: $page-padding;
  }
}

.skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.skeleton-item {
  height: 200rpx;
  background: #fff;
  border-radius: 16rpx;
  animation: skeleton-loading 1.2s ease-in-out infinite;
}

@keyframes skeleton-loading {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 120rpx 0;
  gap: 24rpx;
}

.empty-text {
  font-size: 28rpx;
  color: #ccc;
}

.addr-card {
  background: #fff;
  border-radius: 16rpx;
  margin-bottom: 24rpx;
  padding: 28rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
  border: 2rpx solid transparent;
  transition: border-color 0.2s;

  &--default {
    border-color: rgba(41, 121, 255, 0.3);
  }

  &--selected {
    border-color: var(--color-primary, #{$primary-color});
    background: rgba(41, 121, 255, 0.02);
  }

  &__main {
    margin-bottom: 20rpx;
  }

  &__name-row {
    display: flex;
    align-items: center;
    gap: 16rpx;
    margin-bottom: 12rpx;
    flex-wrap: wrap;
  }

  &__name {
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__mobile {
    font-size: 28rpx;
    color: #666;
  }

  &__default-badge {
    font-size: 20rpx;
    color: #fff;
    background: var(--color-primary, #{$primary-color});
    padding: 4rpx 12rpx;
    border-radius: 8rpx;
    line-height: 1.4;
  }

  &__check {
    width: 36rpx;
    height: 36rpx;
    border-radius: 50%;
    background: var(--color-primary, #{$primary-color});
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__detail {
    font-size: 26rpx;
    color: $text-color-secondary;
    line-height: 1.6;
  }

  &__actions {
    display: flex;
    gap: 32rpx;
    padding-top: 20rpx;
    border-top: 1rpx solid #f5f5f5;
  }

  &__action-btn {
    display: flex;
    align-items: center;
    gap: 8rpx;
    padding: 8rpx 0;
  }

  &__action-text {
    font-size: 24rpx;
    color: $text-color-secondary;

    &--danger {
      color: $danger-color;
    }
  }
}

.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: #ffffff;
  padding: 20rpx 32rpx;
  padding-bottom: calc(20rpx + constant(safe-area-inset-bottom));
  padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
  box-shadow: 0 -2rpx 12rpx rgba(0, 0, 0, 0.06);
  box-sizing: border-box;

  &__btn {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 88rpx;
    background: #2979ff;
    border-radius: 16rpx;
    color: #ffffff;
  }
  &__btn-text {
    margin-left: 8rpx;
    font-size: 30rpx;
    font-weight: 600;
  }
}
</style>
