<template>
  <view class="history-page">
    <!-- 顶部清空入口 -->
    <view v-if="list.length > 0" class="history-top">
      <text class="history-top__count">共 {{ total }} 条</text>
      <text class="history-top__clear" @tap="onClear">清空</text>
    </view>

    <scroll-view
      class="history-scroll"
      :class="{ 'history-scroll--with-top': list.length > 0 }"
      scroll-y
      @scrolltolower="loadMore"
    >
      <u-swipe-action v-if="list.length > 0">
        <u-swipe-action-item
          v-for="item in list"
          :key="item.id"
          :name="item.id"
          :options="swipeOptions"
          @click="onSwipeClick"
        >
          <view class="history-item" @tap="goDetail(item.spu_id)">
            <image class="history-item__img" :src="appStore.getImageUrl(item.spu_image)" mode="aspectFill" />
            <view class="history-item__main">
              <text class="history-item__name">{{ item.spu_name || '商品已下架' }}</text>
              <text class="history-item__time">{{ formatTime(item.viewed_at) }}</text>
              <view class="history-item__price-row">
                <d-price :price="item.min_price" />
              </view>
            </view>
          </view>
        </u-swipe-action-item>
      </u-swipe-action>
      <d-list-loader :loading="loading" :finished="finished" :total="total" empty-text="暂无浏览记录" />
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'
import { memberApi, type BrowseHistoryItem } from '@/api/member'

const appStore = useAppStore()

const list = ref<BrowseHistoryItem[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = 20
const loading = ref(false)
const finished = ref(false)

const swipeOptions = [{ text: '删除', style: { backgroundColor: '#ef4444' } }]

async function loadList(reset = false) {
  if (loading.value) return
  if (!reset && finished.value) return

  loading.value = true
  if (reset) {
    page.value = 1
    finished.value = false
  }

  try {
    const res = await memberApi.getBrowseHistory({ page_no: page.value, page_size: pageSize })
    if (page.value === 1) {
      list.value = res.list
    } else {
      list.value = [...list.value, ...res.list]
    }
    total.value = res.pagination.total
    finished.value = res.pagination.total === 0 || page.value >= res.pagination.last_page
    page.value += 1
  } catch {
    finished.value = true
  } finally {
    loading.value = false
  }
}

function loadMore() {
  if (!finished.value && !loading.value) loadList(false)
}

function onSwipeClick(e: { index: number; name: string | number }) {
  const id = Number(e.name)
  removeItem(id)
}

async function removeItem(id: number) {
  const prev = [...list.value]
  list.value = list.value.filter(i => i.id !== id)
  total.value = Math.max(0, total.value - 1)
  try {
    await memberApi.removeBrowseHistory(id)
    uni.showToast({ title: '已删除', icon: 'success', duration: 1500 })
  } catch {
    list.value = prev
    total.value = prev.length
    uni.showToast({ title: '删除失败', icon: 'none' })
  }
}

function onClear() {
  uni.showModal({
    title: '清空浏览记录',
    content: '确定清空全部浏览记录？',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await memberApi.clearBrowseHistory()
        list.value = []
        total.value = 0
        finished.value = true
        uni.showToast({ title: '已清空', icon: 'success' })
      } catch {
        uni.showToast({ title: '清空失败', icon: 'none' })
      }
    },
  })
}

function goDetail(spuId: number) {
  uni.navigateTo({ url: `/modules/goods/pages/detail?id=${spuId}` })
}

function formatTime(s: string): string {
  if (!s) return ''
  // 简洁显示：今天 HH:mm / 昨天 HH:mm / MM-DD HH:mm
  const t = new Date(s.replace(/-/g, '/'))
  if (Number.isNaN(t.getTime())) return s
  const now = new Date()
  const sameDay = t.getFullYear() === now.getFullYear() && t.getMonth() === now.getMonth() && t.getDate() === now.getDate()
  const yest = new Date(now.getTime() - 86400000)
  const isYest = t.getFullYear() === yest.getFullYear() && t.getMonth() === yest.getMonth() && t.getDate() === yest.getDate()
  const hh = String(t.getHours()).padStart(2, '0')
  const mm = String(t.getMinutes()).padStart(2, '0')
  if (sameDay) return `今天 ${hh}:${mm}`
  if (isYest) return `昨天 ${hh}:${mm}`
  const M = String(t.getMonth() + 1).padStart(2, '0')
  const D = String(t.getDate()).padStart(2, '0')
  return `${M}-${D} ${hh}:${mm}`
}

onShow(() => {
  loadList(true)
})
</script>

<style lang="scss">
// 非 scoped + 页面前缀，绕开 mp 端 scoped 样式穿透 uview-plus 的不稳定问题
.history-page .u-swipe-action-item {
  margin: 16rpx 24rpx 0;
  border-radius: 16rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
</style>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.history-page {
  min-height: 100vh;
  background: var(--color-bg-2, #fafafa);
}

.history-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20rpx 32rpx;
  background: #ffffff;
  border-bottom: 1rpx solid $border-color;

  &__count {
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__clear {
    font-size: 26rpx;
    color: var(--color-primary, #{$primary-color});
  }
}

.history-scroll {
  height: 100vh;

  &--with-top {
    height: calc(100vh - 80rpx);
  }
}

.history-item {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  padding: 24rpx;
  background: var(--color-bg-1, #ffffff);
  box-sizing: border-box;

  &__img {
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--radius-sm, 8rpx);
    background: var(--color-bg-3, #f4f4f5);
    flex-shrink: 0;
  }

  &__main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
  }

  &__name {
    font-size: 28rpx;
    font-weight: 500;
    color: var(--color-text-1, #18181b);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__time {
    font-size: 22rpx;
    color: $text-color-secondary;
  }

  &__price-row {
    display: flex;
    align-items: center;
  }
}
</style>
