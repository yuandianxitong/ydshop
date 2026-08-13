<template>
  <!-- Style 1: 纯导航 — 左侧一级 tab + 右侧二级分组图标网格 -->
  <view class="cat-style1">
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
        @tap="selectCategory(index)"
      >
        <text class="lv1-item__text">{{ cat.name }}</text>
      </view>
    </scroll-view>

    <!-- 右侧（独立滚动） -->
    <scroll-view class="content" scroll-y>
      <view v-if="categoryLoading" class="content-loading">
        <u-loading-icon size="48rpx" />
      </view>

      <template v-else-if="currentCategory">
        <view
          v-for="group in groups"
          :key="group.id"
          class="group"
        >
          <view class="group__title">{{ group.name }}</view>
          <view class="group__grid">
            <view
              v-for="item in group.items"
              :key="item.id"
              class="grid-item"
              @tap="goGoodsList(item.id, item.name)"
            >
              <view class="grid-item__icon">
                <image
                  v-if="item.icon"
                  class="grid-item__image"
                  :src="appStore.getImageUrl(item.icon)"
                  mode="aspectFill"
                />
                <d-icon v-else name="tag" size="48rpx" color="#cccccc" />
              </view>
              <text class="grid-item__name">{{ item.name }}</text>
            </view>
          </view>
        </view>

        <view v-if="!groups.length" class="empty">
          <d-empty text="该分类暂无子项" />
        </view>
      </template>

      <d-empty v-else-if="!categoryLoading" text="暂无分类" />
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'
import { goodsApi, type CategoryItem } from '@/api/goods'

const appStore = useAppStore()

const categoryList = ref<CategoryItem[]>([])
const categoryLoading = ref(false)
const selectedIndex = ref(0)
const lv1ScrollId = ref('')

const currentCategory = computed(() => categoryList.value[selectedIndex.value] || null)

interface Group {
  id: number
  name: string
  items: CategoryItem[]
}

// 二级有三级 → 三级作为图标列表；二级无三级 → 二级自身作为唯一图标项
const groups = computed<Group[]>(() => {
  const lv2 = currentCategory.value?.children || []
  return lv2.map(c => ({
    id: c.id,
    name: c.name,
    items: c.children?.length ? c.children : [c],
  }))
})

async function loadCategories() {
  categoryLoading.value = true
  try {
    categoryList.value = await goodsApi.getCategoryTree()
  } catch {
    // ignore
  } finally {
    categoryLoading.value = false
  }
}

function selectCategory(index: number) {
  if (selectedIndex.value === index) return
  selectedIndex.value = index
  lv1ScrollId.value = `lv1-${categoryList.value[index].id}`
}

function goGoodsList(id: number, name: string) {
  uni.navigateTo({ url: `/modules/goods/pages/list?category_id=${id}&title=${encodeURIComponent(name)}` })
}

onShow(() => {
  if (categoryList.value.length === 0) {
    loadCategories()
  }
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.cat-style1 {
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

.content {
  flex: 1;
  height: 100%;
  background: #ffffff;
  min-width: 0;
  // padding 放到内层（goods-wrap），避免 scroll-view 的 padding-right 不生效
}

.content-loading {
  display: flex;
  justify-content: center;
  padding: 80rpx 0;
}

.group {
  padding: 0 24rpx;

  &__title {
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
    padding: 24rpx 0 20rpx;
  }

  &__grid {
    display: flex;
    flex-wrap: wrap;
  }
}

.grid-item {
  width: 25%;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 24rpx;

  &__icon {
    width: 110rpx;
    height: 110rpx;
    border-radius: 16rpx;
    background: #f6f6f6;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 10rpx;
  }

  &__image {
    width: 100%;
    height: 100%;
  }

  &__name {
    font-size: 22rpx;
    color: var(--color-text, #{$text-color});
    text-align: center;
    line-height: 1.3;
    max-width: 120rpx;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.empty {
  padding: 80rpx 24rpx;
}
</style>
