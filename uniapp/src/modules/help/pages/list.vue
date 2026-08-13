<template>
  <view class="help-list">
    <!-- 搜索条 -->
    <view class="search-bar">
      <u-search
        v-model="keyword"
        placeholder="搜索帮助"
        :show-action="false"
        @search="onSearch"
        @clear="onSearch"
      />
    </view>

    <!-- 分类 tab 横向滚动 -->
    <scroll-view scroll-x class="cat-scroll">
      <view class="cat-list">
        <view class="cat-item" :class="{ on: activeCat === 0 }" @tap="setCategory(0)">全部</view>
        <view
          v-for="c in categories"
          :key="c.id"
          class="cat-item"
          :class="{ on: activeCat === c.id }"
          @tap="setCategory(c.id)"
        >{{ c.name }}</view>
      </view>
    </scroll-view>

    <!-- 列表 -->
    <view class="list">
      <view v-if="loading && !list.length" class="empty">加载中...</view>
      <view v-else-if="!list.length" class="empty">该分类下暂无帮助</view>
      <view
        v-for="item in list"
        :key="item.id"
        class="item"
        @tap="goDetail(item.id)"
      >
        <view class="item-title">{{ item.title }}</view>
        <view v-if="item.summary" class="item-summary">{{ item.summary }}</view>
        <view class="item-meta">阅读 {{ item.view_count }}</view>
      </view>
      <view v-if="!hasMore && list.length" class="no-more">- 没有更多了 -</view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onReachBottom, onPullDownRefresh } from '@dcloudio/uni-app'
import { helpApi, type HelpCategoryInfo, type HelpItem } from '@/api/help'

const categories = ref<HelpCategoryInfo[]>([])
const list = ref<HelpItem[]>([])
const total = ref(0)
const page = ref(1)
const pageSize = 20
const loading = ref(false)

const keyword = ref('')
const activeCat = ref(0)
const hasMore = computed(() => list.value.length < total.value)

async function loadCategories() {
  categories.value = await helpApi.getCategories()
}

async function loadList(reset = false) {
  if (reset) { page.value = 1; list.value = [] }
  if (loading.value) return
  loading.value = true
  try {
    const params: any = { page_no: page.value, page_size: pageSize }
    if (activeCat.value) params.category_id = activeCat.value
    if (keyword.value.trim()) params.keyword = keyword.value.trim()
    const res = await helpApi.getList(params)
    list.value = page.value === 1 ? res.list : [...list.value, ...res.list]
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function setCategory(id: number) {
  activeCat.value = id
  loadList(true)
}

function onSearch() {
  loadList(true)
}

function goDetail(id: number) {
  uni.navigateTo({ url: `/modules/help/pages/detail?id=${id}` })
}

onLoad(async () => { await loadCategories(); await loadList(true) })

onReachBottom(() => {
  if (hasMore.value && !loading.value) {
    page.value++
    loadList()
  }
})

onPullDownRefresh(async () => {
  await loadList(true)
  uni.stopPullDownRefresh()
})
</script>

<style scoped lang="scss">
.help-list { min-height: 100vh; background: #f5f7fa; }
.search-bar { padding: 16rpx 24rpx; background: #fff; }
.cat-scroll { background: #fff; border-bottom: 1px solid #f0f0f0; }
.cat-list { display: flex; padding: 16rpx 24rpx; gap: 16rpx; }
.cat-item {
  flex-shrink: 0; padding: 8rpx 24rpx; font-size: 26rpx; color: #4b5563;
  background: #f3f4f6; border-radius: 24rpx;
}
.cat-item.on { background: var(--color-primary, #4f6bff); color: #fff; }
.list { padding: 0 24rpx; }
.item {
  background: #fff; border-radius: 8rpx; padding: 24rpx; margin-top: 16rpx;
}
.item-title { font-size: 30rpx; color: #1f2937; line-height: 1.4; }
.item-summary { font-size: 24rpx; color: #6b7280; margin-top: 8rpx; line-height: 1.5; }
.item-meta { font-size: 22rpx; color: #9ca3af; margin-top: 12rpx; }
.empty { padding: 96rpx 0; text-align: center; font-size: 26rpx; color: #9ca3af; }
.no-more { padding: 24rpx 0; text-align: center; font-size: 24rpx; color: #c0c4cc; }
</style>
