<template>
  <u-popup :show="visible" mode="bottom" round="24rpx" safeAreaInsetBottom @close="close">
    <view class="d-cart-panel">
      <view class="d-cart-panel__header">
        <text class="d-cart-panel__title">购物车 ({{ items.length }})</text>
        <view v-if="items.length > 0" class="d-cart-panel__clear" @tap="clearAll">清空</view>
        <view class="d-cart-panel__close" @tap="close">
          <d-icon name="close" size="32rpx" color="#71717a" />
        </view>
      </view>

      <scroll-view scroll-y class="d-cart-panel__list">
        <view v-if="!items.length" class="d-cart-panel__empty">
          <d-empty text="购物车空空如也" />
        </view>
        <view v-for="item in items" :key="item.id" class="cart-row">
          <image
            class="cart-row__img"
            :src="appStore.getImageUrl(item.image)"
            mode="aspectFill"
          />
          <view class="cart-row__main">
            <text class="cart-row__name">{{ item.spu_name }}</text>
            <text v-if="item.spec_text" class="cart-row__spec">{{ item.spec_text }}</text>
            <view class="cart-row__foot">
              <d-price :price="item.price" />
              <view class="stepper">
                <view class="stepper__btn" @tap="dec(item)">−</view>
                <text class="stepper__num">{{ item.quantity }}</text>
                <view class="stepper__btn" @tap="inc(item)">＋</view>
              </view>
            </view>
          </view>
        </view>
      </scroll-view>

      <view class="d-cart-panel__footer">
        <view class="d-cart-panel__go" @tap="goCart">前往购物车</view>
      </view>
    </view>
  </u-popup>
</template>

<script setup lang="ts">
import { useAppStore } from '@/store/app.store'
import { cartApi, type CartItem } from '@/api/cart'

const props = defineProps<{
  visible: boolean
  items: CartItem[]
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  change: []
}>()

const appStore = useAppStore()

function close() {
  emit('update:visible', false)
}

async function inc(item: CartItem) {
  try {
    await cartApi.updateCartItem(item.id, { quantity: item.quantity + 1 })
    emit('change')
  } catch {
    uni.showToast({ title: '更新失败', icon: 'none' })
  }
}

async function dec(item: CartItem) {
  if (item.quantity <= 1) {
    try {
      await cartApi.removeCartItem(item.id)
      emit('change')
    } catch {
      uni.showToast({ title: '移除失败', icon: 'none' })
    }
    return
  }
  try {
    await cartApi.updateCartItem(item.id, { quantity: item.quantity - 1 })
    emit('change')
  } catch {
    uni.showToast({ title: '更新失败', icon: 'none' })
  }
}

async function clearAll() {
  uni.showModal({
    title: '清空购物车',
    content: '确定清空购物车？',
    success: async (res) => {
      if (!res.confirm) return
      try {
        await Promise.all(props.items.map(it => cartApi.removeCartItem(it.id)))
        emit('change')
      } catch {
        uni.showToast({ title: '清空失败', icon: 'none' })
      }
    },
  })
}

function goCart() {
  close()
  uni.switchTab({ url: '/pages/cart/index' })
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.d-cart-panel {
  display: flex;
  flex-direction: column;
  max-height: 80vh;
  min-height: 360rpx;

  &__header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    padding: 28rpx 24rpx 20rpx;
    border-bottom: 1rpx solid $border-color;
  }

  &__title {
    flex: 1;
    font-size: 30rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
  }

  &__clear {
    margin-right: 24rpx;
    font-size: 24rpx;
    color: $text-color-secondary;
  }

  &__close {
    width: 48rpx;
    height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__list {
    flex: 1;
    max-height: 60vh;
    padding: 0 24rpx;
  }

  &__empty {
    padding: 80rpx 0;
  }

  &__footer {
    flex-shrink: 0;
    padding: 16rpx 24rpx;
    border-top: 1rpx solid $border-color;
  }

  &__go {
    height: 80rpx;
    border-radius: 40rpx;
    background: var(--color-primary, #{$primary-color});
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}

.cart-row {
  display: flex;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &__img {
    width: 140rpx;
    height: 140rpx;
    border-radius: 12rpx;
    background: #f8f8f8;
    flex-shrink: 0;
  }

  &__main {
    flex: 1;
    margin-left: 20rpx;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  &__name {
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__spec {
    font-size: 22rpx;
    color: $text-color-secondary;
    margin-top: 6rpx;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding-top: 12rpx;
  }
}

.stepper {
  display: flex;
  align-items: center;
  gap: 6rpx;

  &__btn {
    width: 48rpx;
    height: 48rpx;
    border-radius: 50%;
    background: #f4f4f4;
    color: var(--color-text, #{$text-color});
    font-size: 28rpx;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__num {
    min-width: 40rpx;
    font-size: 26rpx;
    color: var(--color-text, #{$text-color});
    text-align: center;
  }
}
</style>
