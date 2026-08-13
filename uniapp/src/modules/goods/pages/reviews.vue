<template>
  <view class="reviews">
    <!-- ===== Nav ===== -->
    <view class="reviews__nav" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="reviews__nav-inner">
        <view class="reviews__nav-back" @tap="handleBack">
          <d-icon name="arrow-left" size="32rpx" color="#18181b" />
        </view>
        <text class="reviews__nav-title">商品评价</text>
        <view style="width: 64rpx" />
      </view>
    </view>

    <scroll-view
      class="reviews__scroll"
      scroll-y
      :style="{ height: `calc(100vh - 88rpx - ${statusBarHeight}px)` }"
      @scrolltolower="onLoadMore"
    >
      <!-- Summary -->
      <view v-if="total > 0" class="reviews__summary">
        <view class="reviews__summary-rate">
          <text class="reviews__summary-num">{{ avgRating.toFixed(1) }}</text>
          <text class="reviews__summary-label">综合评分</text>
        </view>
        <view class="reviews__summary-stats">
          <view class="reviews__summary-row">
            <text class="reviews__summary-key">好评率</text>
            <text class="reviews__summary-val">{{ goodRate }}%</text>
          </view>
          <view class="reviews__summary-row">
            <text class="reviews__summary-key">评价数</text>
            <text class="reviews__summary-val">{{ total }}</text>
          </view>
        </view>
      </view>

      <!-- List -->
      <d-empty v-if="!loading && list.length === 0" text="暂无评价" />
      <view v-else class="reviews__list">
        <view
          v-for="rv in list"
          :key="rv.id"
          class="reviews__card"
        >
          <view class="reviews__card-header">
            <image
              :src="rv.avatar || defaultAvatar"
              mode="aspectFill"
              class="reviews__card-avatar"
            />
            <view class="reviews__card-meta">
              <text class="reviews__card-user">{{ rv.nickname || '匿名用户' }}</text>
              <view class="reviews__card-meta-row">
                <u-rate :model-value="rv.rating" readonly size="12" />
                <text class="reviews__card-time">{{ formatTime(rv.created_at) }}</text>
              </view>
            </view>
          </view>
          <text class="reviews__card-content">{{ rv.content }}</text>
          <view v-if="rv.images && rv.images.length > 0" class="reviews__card-imgs">
            <image
              v-for="(img, idx) in rv.images"
              :key="idx"
              :src="getImageUrl(img)"
              mode="aspectFill"
              class="reviews__card-img"
              @tap="previewReviewImage(rv.images, idx)"
            />
          </view>
          <view v-if="(rv as ReviewItemExt).spec_label" class="reviews__card-spec">
            <d-tag :text="(rv as ReviewItemExt).spec_label!" variant="neutral" />
          </view>
        </view>

        <!-- Pagination -->
        <d-list-loader
          :loading="loaderStatus === 'loading'"
          :finished="loaderStatus === 'noMore'"
          :total="total"
        />
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { goodsApi, type ReviewItem } from '@/api/goods'
import { useAppStore } from '@/store/app.store'

interface ReviewItemExt extends ReviewItem {
  spec_label?: string
}

const appStore = useAppStore()

// Route params
const pages = getCurrentPages()
const currentPage = pages[pages.length - 1] as any
const goodsId = currentPage?.options?.goods_id as string

// ===== Review list state =====
const list = ref<ReviewItemExt[]>([])
const loading = ref(false)
const total = ref(0)
const page = ref(1)
const pageSize = 10
const loaderStatus = ref<'more' | 'loading' | 'noMore'>('more')

const statusBarHeight = ref(0)

// Computed
const avgRating = computed<number>(() => {
  if (!list.value.length) return 0
  const sum = list.value.reduce((a, r) => a + (r.rating ?? 5), 0)
  return sum / list.value.length
})

const goodRate = computed(() => {
  if (!list.value.length) return 0
  const good = list.value.filter(r => (r.rating ?? 5) >= 4).length
  return Math.round((good / list.value.length) * 100)
})

const defaultAvatar = '/static/images/default-avatar.png'

function getImageUrl(path: string) {
  return appStore.getImageUrl(path)
}

function formatTime(s: string | number | Date | undefined): string {
  if (!s) return ''
  const d = new Date(s as string)
  if (Number.isNaN(d.getTime())) return ''
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function previewReviewImage(images: string[], current: number) {
  uni.previewImage({
    urls: images.map(getImageUrl),
    current: getImageUrl(images[current]),
  })
}

function handleBack() {
  uni.navigateBack()
}

async function loadList() {
  if (loaderStatus.value === 'noMore' || loading.value) return
  loading.value = true
  loaderStatus.value = 'loading'
  try {
    if (!goodsId) {
      loaderStatus.value = 'noMore'
      return
    }
    const data = await goodsApi.getGoodsReviews(goodsId, { page_no: page.value, page_size: pageSize })
    const items = (data.list ?? []) as ReviewItemExt[]
    list.value.push(...items)
    total.value = data.pagination?.total ?? list.value.length
    if (items.length < pageSize) {
      loaderStatus.value = 'noMore'
    } else {
      loaderStatus.value = 'more'
      page.value++
    }
  } catch {
    loaderStatus.value = 'more'
  } finally {
    loading.value = false
  }
}

function onLoadMore() {
  loadList()
}

onMounted(() => {
  const sysInfo = uni.getSystemInfoSync()
  statusBarHeight.value = sysInfo.statusBarHeight ?? 0
  loadList()
})
</script>

<style lang="scss" scoped>

.reviews {
  min-height: 100vh;
  background: var(--color-bg-2);

  &__nav {
    background: var(--color-bg-1);
    border-bottom: 1rpx solid var(--color-border-2);
  }
  &__nav-inner {
    display: flex;
    align-items: center;
    height: 88rpx;
    padding: 0 var(--space-3);
  }
  &__nav-back {
    width: 64rpx;
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-1);
    font-size: 32rpx;
  }
  &__nav-title {
    flex: 1;
    text-align: center;
    font-size: var(--font-base);
    font-weight: 600;
    color: var(--color-text-1);
  }

  &__scroll {
    box-sizing: border-box;
  }

  &__summary {
    display: flex;
    align-items: center;
    padding: var(--space-4);
    background: var(--color-bg-1);
    border-bottom: 1rpx solid var(--color-border-2);
    gap: var(--space-4);
  }
  &__summary-rate {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    padding-right: var(--space-4);
    border-right: 1rpx solid var(--color-border-2);
  }
  &__summary-num {
    font-size: 56rpx;
    font-weight: 800;
    color: var(--color-price);
    line-height: 1;
  }
  &__summary-label {
    font-size: var(--font-xs);
    color: var(--color-text-3);
    margin-top: 4rpx;
  }
  &__summary-stats {
    flex: 1;
  }
  &__summary-row {
    display: flex;
    justify-content: space-between;
    padding: 4rpx 0;
  }
  &__summary-key {
    font-size: var(--font-sm);
    color: var(--color-text-2);
  }
  &__summary-val {
    font-size: var(--font-sm);
    color: var(--color-text-1);
    font-weight: 500;
  }

  &__list {
    padding: 0 var(--space-3);
  }
  &__card {
    margin-top: var(--space-2);
    padding: var(--space-3);
    background: var(--color-bg-1);
    border-radius: var(--radius-md);
  }
  &__card-header {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-2);
  }
  &__card-avatar {
    width: 72rpx;
    height: 72rpx;
    border-radius: 50%;
  }
  &__card-meta {
    flex: 1;
  }
  &__card-user {
    font-size: var(--font-sm);
    color: var(--color-text-1);
  }
  &__card-meta-row {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-top: 4rpx;
  }
  &__card-time {
    font-size: var(--font-xs);
    color: var(--color-text-3);
  }
  &__card-content {
    font-size: var(--font-sm);
    color: var(--color-text-1);
    line-height: var(--line-normal);
  }
  &__card-imgs {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-1);
    margin-top: var(--space-2);
  }
  &__card-img {
    width: 200rpx;
    height: 200rpx;
    border-radius: var(--radius-sm);
  }
  &__card-spec {
    margin-top: var(--space-2);
  }
}
</style>
