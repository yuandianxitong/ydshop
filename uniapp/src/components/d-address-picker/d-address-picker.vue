<template>
  <view v-if="visible" class="d-addr-picker__mask" @tap.self="handleClose">
    <view class="d-addr-picker">
      <view class="d-addr-picker__header">
        <text class="d-addr-picker__title">{{ title }}</text>
        <view class="d-addr-picker__close" @tap="handleClose">
          <text class="d-addr-picker__close-icon">✕</text>
        </view>
      </view>

      <scroll-view scroll-y class="d-addr-picker__list">
        <view
          v-for="addr in addressList"
          :key="addr.id"
          class="d-addr-picker__item"
          :class="{ 'd-addr-picker__item--selected': selectedId === addr.id }"
          @tap="handlePick(addr)"
        >
          <view class="d-addr-picker__item-row">
            <text class="d-addr-picker__item-name">{{ addr.name }} {{ addr.phone }}</text>
            <view v-if="addr.is_default === 1" class="d-addr-picker__item-default">默认</view>
          </view>
          <text class="d-addr-picker__item-detail">
            {{ addr.province }} {{ addr.city }} {{ addr.district }} {{ addr.detail }}
          </text>
        </view>
        <view v-if="!loading && addressList.length === 0" class="d-addr-picker__empty">
          <text>暂无收货地址</text>
        </view>
      </scroll-view>

      <view class="d-addr-picker__footer">
        <view class="d-addr-picker__add-btn" @tap="handleAddNew">
          <text>+ 新增收货地址</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { memberApi, type AddressItem } from '@/api/member'

const props = withDefaults(
  defineProps<{
    visible: boolean
    selectedId?: number | null
    title?: string
  }>(),
  {
    selectedId: null,
    title: '选择收货地址',
  },
)

const emit = defineEmits<{
  (e: 'update:visible', v: boolean): void
  (e: 'select', addr: AddressItem): void
}>()

const addressList = ref<AddressItem[]>([])
const loading = ref(false)

async function loadList() {
  loading.value = true
  try {
    const list = await memberApi.getAddressList()
    addressList.value = Array.isArray(list) ? list : []
  } catch {
    addressList.value = []
  } finally {
    loading.value = false
  }
}

// visible 由 false → true 时拉一次列表（保证回到此 popup 时数据最新）
watch(
  () => props.visible,
  async (v) => {
    if (v) await loadList()
  },
  { immediate: true },
)

function handleClose() {
  emit('update:visible', false)
}

function handlePick(addr: AddressItem) {
  emit('select', addr)
  emit('update:visible', false)
}

function handleAddNew() {
  emit('update:visible', false)
  uni.navigateTo({ url: '/modules/address/pages/edit' })
}
</script>

<style lang="scss" scoped>
.d-addr-picker__mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1100;
  display: flex;
  align-items: flex-end;
}

.d-addr-picker {
  width: 100%;
  background: #fff;
  border-top-left-radius: 24rpx;
  border-top-right-radius: 24rpx;
  display: flex;
  flex-direction: column;
  max-height: 75vh;

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 32rpx;
    border-bottom: 1rpx solid #f0f0f0;
  }
  &__title {
    font-size: 32rpx;
    font-weight: 600;
    color: #18181b;
  }
  &__close { padding: 8rpx; }
  &__close-icon { font-size: 36rpx; color: #71717a; }

  &__list {
    flex: 1;
    padding: 16rpx 32rpx;
    max-height: 60vh;
  }
  &__item {
    background: #f9f9f9;
    border-radius: 12rpx;
    padding: 24rpx;
    margin-bottom: 16rpx;
    border: 2rpx solid transparent;
    &--selected {
      border-color: #2979ff;
      background: #f0f7ff;
    }
  }
  &__item-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 8rpx;
  }
  &__item-name {
    font-size: 28rpx;
    font-weight: 600;
    color: #18181b;
  }
  &__item-default {
    background: #2979ff;
    color: #fff;
    font-size: 20rpx;
    padding: 4rpx 12rpx;
    border-radius: 6rpx;
  }
  &__item-detail {
    font-size: 24rpx;
    color: #71717a;
    line-height: 1.5;
  }

  &__empty {
    text-align: center;
    padding: 80rpx 0;
    color: #aaa;
    font-size: 26rpx;
  }
  &__footer {
    padding: 16rpx 32rpx;
    padding-bottom: calc(16rpx + constant(safe-area-inset-bottom));
    padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #f0f0f0;
  }
  &__add-btn {
    height: 80rpx;
    line-height: 80rpx;
    text-align: center;
    background: #2979ff;
    color: #fff;
    border-radius: 12rpx;
    font-size: 28rpx;
  }
}
</style>
