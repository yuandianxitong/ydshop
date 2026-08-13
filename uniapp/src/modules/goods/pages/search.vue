<template>
  <view class="search-page">
    <!-- Search header -->
    <view class="search-header">
      <view class="search-input-wrap">
        <u-icon name="search" size="36rpx" color="#999" class="search-icon" />
        <input
          ref="inputRef"
          v-model="keyword"
          type="text"
          placeholder="搜索商品"
          placeholder-class="search-placeholder"
          class="search-input"
          confirm-type="search"
          @confirm="doSearch"
          @input="onInput"
          @focus="showHistory = true"
        />
        <view v-if="keyword" class="clear-btn" @tap="clearKeyword">
          <d-icon name="close" size="32rpx" color="#cccccc" />
        </view>
      </view>
      <text class="cancel-btn" @tap="handleCancel">取消</text>
    </view>

    <!-- Search suggestions (shown while typing) -->
    <view v-if="keyword && suggestions.length" class="suggestions">
      <view
        v-for="(s, i) in suggestions"
        :key="i"
        class="suggestion-item"
        @tap="selectSuggestion(s)"
      >
        <u-icon name="search" size="28rpx" color="#ccc" class="suggestion-icon" />
        <text class="suggestion-text">{{ s }}</text>
      </view>
    </view>

    <!-- History & hot keywords (shown when input empty or focused) -->
    <view v-else-if="!keyword" class="history-section">

      <!-- Hot search -->
      <view class="section-block">
        <view class="section-header">
          <text class="section-title">热门搜索</text>
        </view>
        <view class="tag-list">
          <view
            v-for="(tag, i) in hotKeywords"
            :key="i"
            class="search-tag"
            :class="{ 'search-tag--hot': i < 3 }"
            @tap="selectHot(tag)"
          >
            {{ tag }}
          </view>
        </view>
      </view>

      <!-- Search history -->
      <view v-if="history.length" class="section-block">
        <view class="section-header">
          <text class="section-title">搜索历史</text>
          <view class="clear-history-btn" @tap="confirmClearHistory">
            <u-icon name="delete" size="32rpx" color="#999" />
          </view>
        </view>
        <view class="tag-list">
          <view
            v-for="(item, i) in history"
            :key="i"
            class="search-tag"
            @tap="selectHistory(item)"
          >
            {{ item }}
          </view>
        </view>
      </view>

    </view>

  </view>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

const HISTORY_KEY = 'goods_search_history'
const MAX_HISTORY = 15

const keyword = ref('')
const history = ref<string[]>([])
const showHistory = ref(false)

// Static hot keywords (can be replaced with API call)
const hotKeywords = [
  '手机', '耳机', '键盘', '显示器', '充电器',
  '背包', '水杯', '运动鞋', '护肤品', '零食',
]

// Simple suggestions based on history + hot keywords containing the keyword
const suggestions = computed(() => {
  if (!keyword.value.trim()) return []
  const kw = keyword.value.trim().toLowerCase()
  const matches = [
    ...history.value.filter(h => h.toLowerCase().includes(kw)),
    ...hotKeywords.filter(h => h.toLowerCase().includes(kw) && !history.value.includes(h)),
  ]
  return [...new Set(matches)].slice(0, 8)
})

function loadHistory() {
  try {
    const raw = uni.getStorageSync(HISTORY_KEY)
    if (raw && Array.isArray(raw)) history.value = raw
  } catch {
    history.value = []
  }
}

function saveHistory(kw: string) {
  const list = [kw, ...history.value.filter(h => h !== kw)].slice(0, MAX_HISTORY)
  history.value = list
  try {
    uni.setStorageSync(HISTORY_KEY, list)
  } catch {
    // ignore
  }
}

function doSearch() {
  const kw = keyword.value.trim()
  if (!kw) return
  saveHistory(kw)
  uni.navigateTo({
    url: `/modules/goods/pages/list?keyword=${encodeURIComponent(kw)}`,
  })
}

function selectHistory(kw: string) {
  keyword.value = kw
  doSearch()
}

function selectHot(kw: string) {
  keyword.value = kw
  doSearch()
}

function selectSuggestion(kw: string) {
  keyword.value = kw
  doSearch()
}

function clearKeyword() {
  keyword.value = ''
}

function onInput() {
  showHistory.value = true
}

function confirmClearHistory() {
  uni.showModal({
    title: '提示',
    content: '确认清除搜索历史？',
    success: (res) => {
      if (res.confirm) {
        history.value = []
        try {
          uni.removeStorageSync(HISTORY_KEY)
        } catch {
          // ignore
        }
      }
    },
  })
}

function handleCancel() {
  uni.navigateBack()
}

onLoad((query: any) => {
  if (query?.keyword) {
    keyword.value = decodeURIComponent(query.keyword)
  }
})

onMounted(() => {
  loadHistory()
  // Focus the input
  nextTick(() => {
    // Trigger focus via platform-specific approach
  })
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.search-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.search-header {
  display: flex;
  align-items: center;
  padding: 20rpx 24rpx;
  background: #ffffff;
  gap: 20rpx;
}

.search-input-wrap {
  flex: 1;
  display: flex;
  align-items: center;
  background: #f5f5f5;
  border-radius: 40rpx;
  padding: 14rpx 24rpx;
  gap: 12rpx;
}

.search-icon {
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
  background: transparent;
  border: none;
  outline: none;
  min-width: 0;
}

.search-placeholder {
  color: #aaaaaa;
  font-size: 28rpx;
}

.clear-btn {
  flex-shrink: 0;
  padding: 4rpx;
}

.cancel-btn {
  font-size: 28rpx;
  color: $text-color-secondary;
  flex-shrink: 0;
  white-space: nowrap;
}

.suggestions {
  background: #ffffff;
  margin-top: 2rpx;
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 24rpx 32rpx;
  gap: 16rpx;
  border-bottom: 1rpx solid #f5f5f5;

  &:active {
    background: #f9f9f9;
  }
}

.suggestion-icon {
  flex-shrink: 0;
}

.suggestion-text {
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.history-section {
  padding: 20rpx;
}

.section-block {
  background: #ffffff;
  border-radius: 16rpx;
  padding: 28rpx 32rpx;
  margin-bottom: 20rpx;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24rpx;
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: var(--color-text, #{$text-color});
}

.clear-history-btn {
  padding: 8rpx;
}

.tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}

.search-tag {
  padding: 12rpx 28rpx;
  background: #f5f5f5;
  border-radius: 40rpx;
  font-size: 26rpx;
  color: var(--color-text, #{$text-color});

  &:active {
    opacity: 0.7;
  }

  &--hot {
    background: #fff0f0;
    color: $danger-color;
  }
}
</style>
