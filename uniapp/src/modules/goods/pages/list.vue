<template>
  <d-page :safe-area="true" :padding="false">

    <!-- Sort tabs -->
    <view class="sort-bar">
      <view
        v-for="tab in sortTabs"
        :key="tab.value"
        class="sort-tab"
        :class="{ 'sort-tab--active': activeSort === tab.value }"
        @tap="setSort(tab.value)"
      >
        <text class="sort-tab__label">{{ tab.label }}</text>
        <view v-if="tab.value === 'price'" class="sort-price-arrows">
          <text
            class="sort-arrow"
            :class="{ 'sort-arrow--active': sortParam === 'price_asc' }"
          >▲</text>
          <text
            class="sort-arrow"
            :class="{ 'sort-arrow--active': sortParam === 'price_desc' }"
          >▼</text>
        </view>
      </view>
    </view>

    <!-- Goods grid (infinite scroll) -->
    <scroll-view
      scroll-y
      class="goods-scroll"
      @scrolltolower="getList"
    >
      <view class="goods-grid">
        <d-goods-card
          v-for="item in list"
          :key="item.id"
          :goods="item"
        />
      </view>

      <d-list-loader
        :loading="loading"
        :finished="finished"
        :total="total"
        empty-text="暂无商品"
      />
    </scroll-view>

  </d-page>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { goodsApi, type GoodsItem } from '@/api/goods'
import { usePaging } from '@/hooks/usePaging'

// Query params from route
const categoryId = ref<number | undefined>(undefined)
const keyword = ref<string | undefined>(undefined)
const brandId = ref<number | undefined>(undefined)

const sortTabs = [
  { label: '综合', value: '' },
  { label: '销量', value: 'sales' },
  { label: '价格', value: 'price' },
  { label: '最新', value: 'newest' },
]

const activeSort = ref('')
// Track price sort direction: default asc
const priceSortDir = ref<'price_asc' | 'price_desc'>('price_asc')

const sortParam = computed(() => {
  if (activeSort.value === 'price') return priceSortDir.value
  return activeSort.value
})

function buildParams(base: Record<string, any>) {
  const params: Record<string, any> = { ...base }
  if (categoryId.value) params.category_id = categoryId.value
  if (keyword.value) params.keyword = keyword.value
  if (brandId.value) params.brand_id = brandId.value
  if (sortParam.value) params.sort = sortParam.value
  return params
}

const { list, loading, finished, total, getList, refresh } = usePaging<GoodsItem>({
  fetchFun: (params) => goodsApi.getGoodsList(buildParams(params)),
})

function setSort(value: string) {
  if (value === 'price') {
    if (activeSort.value === 'price') {
      // Toggle price direction
      priceSortDir.value = priceSortDir.value === 'price_asc' ? 'price_desc' : 'price_asc'
    } else {
      priceSortDir.value = 'price_asc'
    }
  }
  activeSort.value = value
  refresh()
}

onLoad((query: any) => {
  if (query?.category_id) categoryId.value = Number(query.category_id)
  if (query?.keyword) keyword.value = String(query.keyword)
  if (query?.brand_id) brandId.value = Number(query.brand_id)

  // Set nav title if keyword
  if (query?.keyword) {
    uni.setNavigationBarTitle({ title: `搜索：${query.keyword}` })
  } else if (query?.category_id) {
    // Title will be set when category is loaded if needed
  }

  getList()
})

onPullDownRefresh(async () => {
  await refresh()
  uni.stopPullDownRefresh()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.sort-bar {
  display: flex;
  background: #ffffff;
  border-bottom: 1rpx solid $border-color;
  position: sticky;
  top: 0;
  z-index: 10;
}

.sort-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24rpx 0;
  position: relative;

  &--active {
    .sort-tab__label {
      color: var(--color-primary, #{$primary-color});
      font-weight: 500;
    }
  }

  &__label {
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
  }
}

.sort-price-arrows {
  display: flex;
  flex-direction: column;
  margin-left: 6rpx;
  gap: 0;
  line-height: 1;
}

.sort-arrow {
  font-size: 16rpx;
  color: #cccccc;
  line-height: 1.2;

  &--active {
    color: var(--color-primary, #{$primary-color});
  }
}

.goods-scroll {
  height: calc(100vh - 100rpx);
}

.goods-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
  padding: 20rpx;
}
</style>
