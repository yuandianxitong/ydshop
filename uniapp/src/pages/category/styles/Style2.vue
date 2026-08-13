<template>
  <!-- Style 2: 左侧一级 tab + 文字 chip 二级条 + 单列横向商品流 -->
  <view class="cat-style2">
    <!-- 左侧一级分类（独立滚动） -->
    <scroll-view
      class="lv1"
      scroll-y
      :scroll-into-view="lv1ScrollId"
      scroll-with-animation
    >
      <view
        v-for="(cat, index) in categoryList"
        :id="`lv1-${cat.id}`"
        :key="cat.id"
        class="lv1-item"
        :class="{ 'lv1-item--active': selectedIndex === index }"
        @tap="selectLv1(index)"
      >
        <text class="lv1-item__text">{{ cat.name }}</text>
      </view>
    </scroll-view>

    <!-- 右侧 -->
    <view class="right">
      <!-- 顶部文字 chip 二级分类条 -->
      <scroll-view v-if="subList.length > 0" class="lv2-bar" scroll-x>
        <view class="lv2-bar__inner">
          <view
            class="lv2-chip"
            :class="{ 'lv2-chip--active': selectedSubId === null }"
            @tap="selectLv2(null)"
          >全部</view>
          <view
            v-for="sub in subList"
            :key="sub.id"
            class="lv2-chip"
            :class="{ 'lv2-chip--active': selectedSubId === sub.id }"
            @tap="selectLv2(sub.id)"
          >{{ sub.name }}</view>
        </view>
      </scroll-view>

      <scroll-view class="goods-wrap" scroll-y @scrolltolower="onScrollToLower">
        <view v-if="categoryLoading" class="content-loading">
          <u-loading-icon size="48rpx" />
        </view>
        <template v-else>
          <view class="goods-list">
            <d-goods-row-card
              v-for="item in goodsList"
              :key="item.id"
              :goods="item"
              @add-cart="$emit('add-cart', $event)"
            />
            <d-list-loader :loading="goodsLoading" :finished="goodsFinished" :total="goodsTotal" empty-text="该分类暂无商品" />
          </view>
        </template>
      </scroll-view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { goodsApi, type CategoryItem, type GoodsItem } from '@/api/goods'

defineEmits<{
  'add-cart': [goods: GoodsItem]
}>()

const categoryList = ref<CategoryItem[]>([])
const categoryLoading = ref(false)
const selectedIndex = ref(0)
const selectedSubId = ref<number | null>(null)
const lv1ScrollId = ref('')

const goodsList = ref<GoodsItem[]>([])
const goodsLoading = ref(false)
const goodsFinished = ref(false)
const goodsTotal = ref(0)
const goodsPage = ref(1)
const goodsPageSize = 10

const currentCategory = computed(() => categoryList.value[selectedIndex.value] || null)
const subList = computed(() => currentCategory.value?.children || [])

async function loadCategories() {
  categoryLoading.value = true
  try {
    const res = await goodsApi.getCategoryTree()
    categoryList.value = res
    if (res.length > 0) {
      await loadGoods(true)
    }
  } catch {
    // ignore
  } finally {
    categoryLoading.value = false
  }
}

async function loadGoods(reset = false) {
  if (!currentCategory.value) return
  if (goodsLoading.value) return
  if (!reset && goodsFinished.value) return

  goodsLoading.value = true
  if (reset) {
    goodsPage.value = 1
    goodsFinished.value = false
    goodsList.value = []
    goodsTotal.value = 0
  }

  try {
    const catId = selectedSubId.value || currentCategory.value.id
    const res = await goodsApi.getGoodsList({
      category_id: catId,
      page_no: goodsPage.value,
      page_size: goodsPageSize,
    })
    if (goodsPage.value === 1) {
      goodsList.value = res.list
    } else {
      goodsList.value = [...goodsList.value, ...res.list]
    }
    goodsTotal.value = res.pagination.total
    goodsFinished.value = goodsPage.value >= res.pagination.last_page
    goodsPage.value += 1
  } catch {
    goodsFinished.value = true
  } finally {
    goodsLoading.value = false
  }
}

function selectLv1(index: number) {
  if (selectedIndex.value === index) return
  selectedIndex.value = index
  selectedSubId.value = null
  lv1ScrollId.value = `lv1-${categoryList.value[index].id}`
}

function selectLv2(id: number | null) {
  if (selectedSubId.value === id) return
  selectedSubId.value = id
  loadGoods(true)
}

function onScrollToLower() {
  if (!goodsFinished.value && !goodsLoading.value) {
    loadGoods(false)
  }
}

watch(selectedIndex, () => {
  loadGoods(true)
})

onShow(() => {
  if (categoryList.value.length === 0) {
    loadCategories()
  }
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.cat-style2 {
  display: flex;
  width: 100%;
  height: 100%;
  background: var(--color-bg, #{$bg-color});
}

.lv1 {
  width: 180rpx;
  height: 100%;
  background: #f6f6f6;
  flex-shrink: 0;
}

.lv1-item {
  padding: 32rpx 12rpx;
  text-align: center;
  position: relative;

  &__text {
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
  }

  &--active {
    background: #ffffff;

    .lv1-item__text {
      color: var(--color-primary, #{$primary-color});
      font-weight: 600;
    }

    &::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 6rpx;
      height: 36rpx;
      background: var(--color-primary, #{$primary-color});
      border-radius: 0 4rpx 4rpx 0;
    }
  }
}

.right {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-width: 0;
  background: #ffffff;
}

.lv2-bar {
  white-space: nowrap;
  background: #ffffff;
  flex-shrink: 0;
  border-bottom: 1rpx solid $border-color;
}

.lv2-bar__inner {
  display: flex;
  padding: 16rpx;
  gap: 12rpx;
}

.lv2-chip {
  flex-shrink: 0;
  padding: 8rpx 24rpx;
  font-size: 24rpx;
  color: var(--color-text, #{$text-color});
  background: #f4f4f4;
  border-radius: 28rpx;
  line-height: 1.5;

  &--active {
    background: var(--color-primary, #{$primary-color});
    color: #ffffff;
    font-weight: 500;
  }
}

.goods-wrap {
  flex: 1;
  height: 0; // uniapp scroll-view 在 flex column 中用 flex: 1 + height: 0 才能正确撑高
  // 在 scroll-view 上不放 padding（uniapp scroll-y 时 padding-right 表现不一致），
  // 把 padding 放到内层 .goods-list
}

.goods-list {
  padding: 16rpx;
  display: flex;
  flex-direction: column;
}

.content-loading {
  display: flex;
  justify-content: center;
  padding: 80rpx 0;
}
</style>
