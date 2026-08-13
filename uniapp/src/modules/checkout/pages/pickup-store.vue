<template>
  <d-page :padding="false" class="pickup-store">
    <view class="pickup-store__header">
      <u-tabs
        :list="tabList"
        :current="tab"
        :inactive-style="{ color: 'var(--color-text-2, #71717a)' }"
        :active-style="{ color: 'var(--color-primary, #2979ff)' }"
        @click="onTabClick"
      />
    </view>

    <view v-if="loading" class="pickup-store__loading">加载中...</view>

    <view v-else-if="tab === 0" class="pickup-store__list">
      <d-empty v-if="!stores.length" text="暂未开通自提门店" />
      <view v-for="s in stores" :key="s.id" class="pickup-store__item">
        <d-store-card :store="s" :readonly="readonly" @select="onSelect" />
      </view>
    </view>

    <view v-else class="pickup-store__map">
      <d-store-map :stores="stores" :center="mapCenter" @select="onSelect" />
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { storeApi, type Store } from '@/api/store'

const loading = ref(true)
const tab = ref(0)
const stores = ref<Store[]>([])
const mapCenter = ref<{ lng: number; lat: number }>({ lng: 116.4074, lat: 39.9042 })
const readonly = ref(false)
const goodsId = ref<number | undefined>()

const tabList = [{ name: '列表视图' }, { name: '地图视图' }]

function onTabClick(item: { index: number }) {
  tab.value = item.index
}

async function loadList() {
  let lng: number | undefined
  let lat: number | undefined
  try {
    const loc = await new Promise<UniApp.GetLocationSuccess>((resolve, reject) => {
      uni.getLocation({
        type: 'gcj02',
        success: resolve,
        fail: reject,
      })
    })
    lng = loc.longitude
    lat = loc.latitude
    mapCenter.value = { lng, lat }
  } catch {
    // 拒绝授权 / 失败 → 用默认城市坐标（北京）
  }
  try {
    const data = await storeApi.list({ lng, lat, goods_id: goodsId.value })
    stores.value = data.list ?? []
  } catch {
    stores.value = []
  }
  loading.value = false
}

function onSelect(s: Store) {
  if (readonly.value) return
  if (!s.is_open_now) {
    uni.showModal({
      title: '提示',
      content: `「${s.name}」当前已歇业，确认选择？`,
      success: ({ confirm }) => {
        if (confirm) goBack(s)
      },
    })
    return
  }
  goBack(s)
}

function goBack(s: Store) {
  uni.$emit('pickup:store-selected', s)
  uni.navigateBack()
}

onLoad((opts: any) => {
  readonly.value = opts?.readonly === '1'
  goodsId.value = opts?.goodsId ? Number(opts.goodsId) : undefined
  loadList()
})
</script>

<style lang="scss" scoped>
.pickup-store {
  background: var(--color-bg-2, #fafafa);
  min-height: 100vh;

  &__header {
    background: var(--color-bg, #fff);
  }

  &__loading {
    text-align: center;
    padding: 60rpx;
    color: var(--color-text-2, #71717a);
  }

  &__list {
    padding: 16rpx;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
  }

  &__item {
    background: var(--color-bg, #fff);
    border-radius: 16rpx;
  }

  &__map {
    height: calc(100vh - 88rpx);
  }
}
</style>
