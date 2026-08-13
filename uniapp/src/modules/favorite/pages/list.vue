<template>
  <view class="favorite-list-page">

    <scroll-view
      scroll-y
      class="favorite-list-page__scroll"
      refresher-enabled
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="loadMore"
    >
      <view class="favorite-list-page__content">

        <!-- Skeleton -->
        <view v-if="loading && list.length === 0" class="skeleton-grid">
          <view v-for="i in 4" :key="i" class="skeleton-card" />
        </view>

        <!-- Empty state -->
        <view v-else-if="!loading && list.length === 0" class="empty-wrap">
          <u-icon name="star" size="80rpx" color="#ccc" />
          <text class="empty-text">暂无收藏商品</text>
          <u-button
            type="primary"
            plain
            :customStyle="{ marginTop: '20rpx', borderRadius: '40rpx' }"
            @click="goShopping"
          >
            去逛逛
          </u-button>
        </view>

        <!-- Goods grid -->
        <view v-else class="goods-grid">
          <view
            v-for="item in list"
            :key="item.id"
            class="goods-grid-item"
            @longpress="onLongPress(item)"
          >
            <d-goods-card :goods="toGoodsItem(item)" />
            <view class="unfav-btn" @tap.stop="handleUnfavorite(item)">
              <u-icon name="close" size="22rpx" color="#fff" />
            </view>
          </view>
        </view>

        <!-- List loader -->
        <d-list-loader
          :loading="loading && list.length > 0"
          :finished="finished"
          :total="total"
          empty-text="暂无收藏商品"
        />

        <view style="height: 40rpx" />
      </view>
    </scroll-view>

  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow, onPullDownRefresh } from '@dcloudio/uni-app'
import { memberApi, type FavoriteItem } from '@/api/member'
import type { GoodsItem } from '@/api/goods'

const list = ref<FavoriteItem[]>([])
const loading = ref(false)
const refreshing = ref(false)
const finished = ref(false)
const total = ref(0)
const page = ref(1)
const PAGE_SIZE = 10

function toGoodsItem(item: FavoriteItem): GoodsItem {
  return {
    id: item.spu_id,
    name: item.spu_name,
    images: item.spu_image ? [item.spu_image] : [],
    min_price: Number(item.min_price ?? 0),
    max_price: Number(item.min_price ?? 0),
    sales_count: 0,
    status: item.spu_status,
  }
}

async function fetchList(reset = false) {
  if (loading.value && !reset) return
  if (finished.value && !reset) return

  loading.value = true

  if (reset) {
    page.value = 1
    finished.value = false
  }

  try {
    const result = await memberApi.getFavoriteList({ page_no: page.value, page_size: PAGE_SIZE })
    const items: FavoriteItem[] = result?.list || []
    const totalCount: number = result?.pagination?.total ?? items.length

    if (reset) {
      list.value = items
    } else {
      list.value.push(...items)
    }

    total.value = totalCount

    if (list.value.length >= totalCount || items.length < PAGE_SIZE) {
      finished.value = true
    } else {
      page.value++
    }
  } catch {
    // ignore
  } finally {
    loading.value = false
    refreshing.value = false
  }
}

async function onRefresh() {
  refreshing.value = true
  await fetchList(true)
}

async function loadMore() {
  if (finished.value || loading.value) return
  await fetchList(false)
}

async function handleUnfavorite(item: FavoriteItem) {
  uni.showModal({
    title: '取消收藏',
    content: '确定取消收藏该商品吗？',
    confirmText: '确定',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await memberApi.toggleFavorite({ spu_id: item.spu_id })
        uni.showToast({ title: '已取消收藏', icon: 'success' })
        // Remove from list
        list.value = list.value.filter(f => f.id !== item.id)
        total.value = Math.max(0, total.value - 1)
        if (list.value.length === 0) {
          finished.value = true
        }
      } catch {
        uni.showToast({ title: '操作失败', icon: 'none' })
      }
    },
  })
}

function onLongPress(item: FavoriteItem) {
  uni.showActionSheet({
    itemList: ['取消收藏'],
    itemColor: '#fa3534',
    success: (res) => {
      if (res.tapIndex === 0) {
        handleUnfavorite(item)
      }
    },
  })
}

function goShopping() {
  uni.switchTab({ url: '/pages/index/index' })
}

onShow(() => {
  fetchList(true)
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.favorite-list-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});

  &__scroll {
    height: 100vh;
  }

  &__content {
    padding: $page-padding;
  }
}

.skeleton-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20rpx;
}

.skeleton-card {
  height: 360rpx;
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

.goods-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20rpx;
}

.goods-grid-item {
  position: relative;
}

.unfav-btn {
  position: absolute;
  top: 12rpx;
  right: 12rpx;
  width: 44rpx;
  height: 44rpx;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}
</style>
