<template>
  <view class="d-store-map">
    <map
      class="d-store-map__map"
      :longitude="center.lng"
      :latitude="center.lat"
      :scale="13"
      :markers="markers"
      :show-location="true"
      @markertap="onMarkerTap"
    />
    <view v-if="selected" class="d-store-map__pop">
      <d-store-card :store="selected" @select="onSelect" />
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Store } from '@/api/store'

const props = defineProps<{
  stores: Store[]
  center: { lng: number; lat: number }
}>()

const emit = defineEmits<{ select: [store: Store] }>()

const selected = ref<Store | null>(null)

/**
 * iconPath 字段省略，让平台使用默认 marker 样式。
 * 实施期如需品牌图标，请添加 uniapp/src/static/marker-store.png（32×32 px）
 * 并在此处补充：iconPath: '/static/marker-store.png', width: 32, height: 32
 */
const markers = computed(() =>
  props.stores.map((s) => ({
    id: s.id,
    longitude: s.lng,
    latitude: s.lat,
  })),
)

function onMarkerTap(e: any) {
  const id = e.detail?.markerId
  selected.value = props.stores.find((s) => s.id === id) ?? null
}

function onSelect(s: Store) {
  emit('select', s)
}
</script>

<style lang="scss" scoped>
.d-store-map {
  position: relative;
  width: 100%;
  height: 100%;

  &__map {
    width: 100%;
    height: 100%;
  }

  &__pop {
    position: absolute;
    bottom: 24rpx;
    left: 24rpx;
    right: 24rpx;
  }
}
</style>
