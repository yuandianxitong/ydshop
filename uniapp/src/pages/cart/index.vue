<template>
  <page-meta
    :page-style="themePageStyle"
    :navigation-bar-background-color="navBg"
    :navigation-bar-text-style="navText"
  />
  <view class="cart-page">
    <!-- Loading -->
    <view v-if="loading" class="cart-loading">
      <u-loading-icon size="60rpx" />
    </view>

    <!-- Empty cart -->
    <view v-else-if="cartList.length === 0" class="cart-empty">
      <image class="cart-empty__img" :src="emptyCartSvg" mode="aspectFit" />
      <text class="cart-empty__text">{{ userStore.isLoggedIn ? '购物车空空如也' : '登录后查看购物车' }}</text>
      <view class="cart-empty__actions">
        <view v-if="!userStore.isLoggedIn" class="cart-empty__btn cart-empty__btn--outline" @tap="checkLogin">去登录</view>
        <view class="cart-empty__btn" @tap="goShop">去购物</view>
      </view>
    </view>

    <!-- Cart list -->
    <template v-else>
      <scroll-view class="cart-scroll" scroll-y>
        <u-swipe-action>
          <u-swipe-action-item
            v-for="item in cartList"
            :key="item.id"
            :name="item.id"
            :options="swipeOptions"
            @click="onSwipeClick"
          >
            <view class="cart-item">
              <!-- 左侧 checkbox -->
              <view class="cart-item__check" @tap="toggleSelect(item)">
                <view class="checkbox" :class="{ 'checkbox--checked': item.selected === 1 }">
                  <text v-if="item.selected === 1" class="checkbox__tick">✓</text>
                </view>
              </view>
              <!-- 商品图 -->
              <image
                class="cart-item__image"
                :src="appStore.getImageUrl(item.image)"
                mode="aspectFill"
                @tap="goDetail(item.spu_id)"
              />
              <!-- 主体内容 -->
              <view class="cart-item__main">
                <view class="cart-item__name-row">
                  <text class="cart-item__name" @tap="goDetail(item.spu_id)">{{ item.spu_name }}</text>
                  <d-tag
                    v-if="item.delivery_modes?.includes('pickup')"
                    text="可自提"
                    variant="primary"
                    size="sm"
                    plain
                  />
                </view>
                <view v-if="item.spec_text" class="cart-item__sku-wrap">
                  <text class="cart-item__sku">{{ item.spec_text }}</text>
                </view>
                <view class="cart-item__price-row">
                  <text class="cart-item__price">
                    <text class="cart-item__price-symbol">¥</text>{{ formatPrice(item.price) }}
                  </text>
                  <!-- Inline stepper -->
                  <view class="cart-stepper">
                    <view
                      class="cart-stepper__btn"
                      :class="{ 'cart-stepper__btn--disabled': item.quantity <= 1 }"
                      @tap="updateQuantity(item, item.quantity - 1)"
                    >−</view>
                    <text class="cart-stepper__num">{{ item.quantity }}</text>
                    <view
                      class="cart-stepper__btn"
                      :class="{ 'cart-stepper__btn--disabled': item.quantity >= (item.stock || 1) }"
                      @tap="updateQuantity(item, item.quantity + 1)"
                    >+</view>
                  </view>
                </view>
              </view>
            </view>
          </u-swipe-action-item>
        </u-swipe-action>
      </scroll-view>

      <!-- Bottom bar -->
      <view class="cart-bottom">
        <view class="cart-bottom__select-all" @tap="toggleSelectAll">
          <view class="checkbox" :class="{ 'checkbox--checked': isAllSelected }">
            <text v-if="isAllSelected" class="checkbox__tick">✓</text>
          </view>
          <text class="cart-bottom__select-text">全选</text>
        </view>
        <view class="cart-bottom__totals">
          <view v-if="Number(calcResult.discount_amount) > 0" class="cart-bottom__discount">
            <text>已减</text>
            <text class="cart-bottom__discount-value">-¥{{ calcResult.discount_amount }}</text>
          </view>
          <view class="cart-bottom__total">
            <text class="cart-bottom__total-label">合计：</text>
            <text class="cart-bottom__total-price">¥{{ calcResult.pay_amount }}</text>
          </view>
        </view>
        <view
          class="cart-bottom__btn"
          :class="{ 'cart-bottom__btn--disabled': selectedCount === 0 }"
          @tap="goCheckout"
        >
          去结算({{ selectedCount }})
        </view>
      </view>
    </template>

    <d-tabbar current-path="/pages/cart/index" />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'
import { useUserStore } from '@/store/user.store'
import { useAuth } from '@/hooks/useAuth'
import { cartApi, type CartItem } from '@/api/cart'
import { orderApi } from '@/api/order'
import { useThemePageStyle } from '@/composables/useThemePageStyle'

const { themePageStyle, navBg, navText } = useThemePageStyle()

// 空购物车插图：浅灰色线框小车 + 虚线（提示空），inline data URL，零网络请求
const emptyCartSvg = `data:image/svg+xml,${encodeURIComponent(
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
    <path d="M18 22 L30 22 L37 50 L100 50 L92 80 L42 80 Z"
          fill="none" stroke="#d4d4d8" stroke-width="3"
          stroke-linecap="round" stroke-linejoin="round"/>
    <circle cx="50" cy="98" r="6" fill="none" stroke="#d4d4d8" stroke-width="3"/>
    <circle cx="86" cy="98" r="6" fill="none" stroke="#d4d4d8" stroke-width="3"/>
    <line x1="50" y1="62" x2="88" y2="62" stroke="#e4e4e7" stroke-width="2" stroke-dasharray="4 3"/>
    <line x1="50" y1="70" x2="88" y2="70" stroke="#e4e4e7" stroke-width="2" stroke-dasharray="4 3"/>
  </svg>`,
)}`
const appStore = useAppStore()
const userStore = useUserStore()
const { checkLogin } = useAuth()

const cartList = ref<CartItem[]>([])
const loading = ref(false)

const calcResult = ref({
  goods_amount: '0.00',
  discount_amount: '0.00',
  pay_amount: '0.00',
})

async function refreshCalc() {
  const selected = cartList.value.filter(i => i.selected === 1)
  if (selected.length === 0) {
    calcResult.value = { goods_amount: '0.00', discount_amount: '0.00', pay_amount: '0.00' }
    return
  }
  try {
    const res = await orderApi.calc({
      skus: selected.map(i => ({ sku_id: i.sku_id, quantity: i.quantity })),
    })
    calcResult.value = {
      goods_amount: res.goods_amount,
      discount_amount: res.discount_amount,
      pay_amount: res.pay_amount,
    }
  } catch {
    // 失败不破坏页面，回退本地手算
    const sum = selected.reduce((s, i) => s + Number(i.price) * i.quantity, 0)
    calcResult.value = {
      goods_amount: sum.toFixed(2),
      discount_amount: '0.00',
      pay_amount: sum.toFixed(2),
    }
  }
}

const isAllSelected = computed(() => cartList.value.length > 0 && cartList.value.every(i => i.selected === 1))

const selectedCount = computed(() => cartList.value.filter(i => i.selected === 1).reduce((sum, i) => sum + i.quantity, 0))

// 后端 sku.price 是 decimal(10,2) 元（直接数值，非分），不除 100
const totalPrice = computed(() => {
  const total = cartList.value
    .filter(i => i.selected === 1)
    .reduce((sum, i) => sum + i.price * i.quantity, 0)
  return total.toFixed(2)
})

// 后端 sku.price 是 decimal(10,2) 元（直接数值，非分），不除 100
function formatPrice(price: number): string {
  return Number(price || 0).toFixed(2)
}

async function loadCart() {
  loading.value = true
  try {
    const res = await cartApi.getCartList()
    cartList.value = res
    await refreshCalc()
  } catch {
    cartList.value = []
  } finally {
    loading.value = false
  }
}

async function toggleSelect(item: CartItem) {
  if (!checkLogin()) return
  const prevSelected = item.selected
  item.selected = item.selected === 1 ? 0 : 1
  try {
    await cartApi.toggleSelectItem(item.id)
  } catch {
    item.selected = prevSelected
  }
}

async function toggleSelectAll() {
  if (!checkLogin()) return
  const targetSelected = isAllSelected.value ? 0 : 1
  const prev = cartList.value.map(i => i.selected)
  cartList.value.forEach(i => { i.selected = targetSelected })
  try {
    await cartApi.selectAllItems({ selected: targetSelected })
  } catch {
    cartList.value.forEach((item, index) => { item.selected = prev[index] })
  }
}

async function updateQuantity(item: CartItem, val: number) {
  if (val < 1 || val > (item.stock || 1)) return
  if (val === item.quantity) return
  const prev = item.quantity
  item.quantity = val
  try {
    await cartApi.updateCartItem(item.id, { quantity: val })
  } catch {
    item.quantity = prev
  }
}

// 侧滑展开时仅"删除"一个按钮
const swipeOptions = [{ text: '删除', style: { backgroundColor: '#ef4444' } }]

// uview-plus 的 click emit 形如 { index, name }；name 我们绑了 item.id
function onSwipeClick(e: { index: number; name: string | number }) {
  const item = cartList.value.find(i => i.id === Number(e.name))
  if (item) removeItem(item)
}

async function removeItem(item: CartItem) {
  // 乐观删除：立即从列表移除；接口失败时还原
  const prev = [...cartList.value]
  cartList.value = cartList.value.filter(i => i.id !== item.id)
  try {
    await cartApi.removeCartItem(item.id)
    uni.showToast({ title: '已删除', icon: 'success', duration: 1500 })
  } catch {
    cartList.value = prev  // 显式新数组确保 watch 重跑
    uni.showToast({ title: '删除失败', icon: 'none' })
  }
}

function goDetail(spuId: number) {
  uni.navigateTo({ url: `/modules/goods/pages/detail?id=${spuId}` })
}

function goShop() {
  uni.switchTab({ url: '/pages/index/index' })
}

function goCheckout() {
  if (!checkLogin()) return
  if (selectedCount.value === 0) {
    uni.showToast({ title: '请选择商品', icon: 'none' })
    return
  }
  uni.navigateTo({ url: '/modules/checkout/pages/index' })
}

watch(
  () => cartList.value.map(i => `${i.id}:${i.selected}:${i.quantity}`).join(','),
  () => refreshCalc(),
  { flush: 'post' }
)

onShow(() => {
  if (!userStore.isLoggedIn) {
    cartList.value = []
    calcResult.value = { goods_amount: '0.00', discount_amount: '0.00', pay_amount: '0.00' }
    loading.value = false
    return
  }
  loadCart()
})
</script>

<!-- 非 scoped：通过 .cart-page 前缀限定到本页，绕开 scoped CSS 在 mp 端穿透 uview-plus 的不稳定问题 -->
<style lang="scss">
// 把卡片视觉装饰（margin / 圆角 / 阴影）落在 u-swipe-action-item 自身上：
// 这样红色删除按钮始终被 .u-swipe-action-item { overflow: hidden } 限制在边界内，
// cart-item 全宽填满 __content 内部，根本不存在"卡片小于父层"的间隙，红色无路可漏
.cart-page .u-swipe-action-item {
  margin: 16rpx 24rpx 0;
  border-radius: 16rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}
</style>

<style lang="scss" scoped>
.cart-page {
  min-height: 100vh;
  background: var(--color-bg-2, #fafafa);
  padding-bottom: calc(212rpx + env(safe-area-inset-bottom));
  // ↑ cart-bottom 112rpx + d-tabbar 100rpx + safe-area
}

.cart-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 400rpx;
}

.cart-empty {
  padding-top: 160rpx;
  display: flex;
  flex-direction: column;
  align-items: center;

  &__img {
    width: 280rpx;
    height: 280rpx;
    margin-bottom: 32rpx;
  }

  &__text {
    font-size: 28rpx;
    color: var(--color-text-2, #71717a);
    margin-bottom: 48rpx;
  }

  &__actions {
    display: flex;
    align-items: center;
    gap: 24rpx;
  }

  &__btn {
    width: 320rpx;
    height: 88rpx;
    line-height: 88rpx;
    border-radius: 44rpx;
    background: var(--color-danger, #ef4444);
    color: #ffffff;
    font-size: 30rpx;
    font-weight: 600;
    text-align: center;

    &--outline {
      width: 240rpx;
      background: #ffffff;
      color: var(--color-danger, #ef4444);
      border: 2rpx solid var(--color-danger, #ef4444);
    }
  }
}

.cart-scroll {
  // 高度由父级 padding-bottom 控制，scroll-view 自然占满
}

.cart-item {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 16rpx;
  padding: 24rpx;
  background: var(--color-bg-1, #ffffff);
  box-sizing: border-box;
  // margin / border-radius / box-shadow 都移到 .u-swipe-action-item 自身（见上方非 scoped 块）

  &__check {
    width: 76rpx;
    height: 76rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__image {
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--radius-sm, 8rpx);
    background: var(--color-bg-3, #f4f4f5);
    flex-shrink: 0;
  }

  &__main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 12rpx;
  }

  &__name-row {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    gap: 8rpx;
    flex-wrap: wrap;
  }

  &__name {
    flex: 1;
    font-size: 28rpx;
    color: var(--color-text-1, #18181b);
    font-weight: 500;
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  &__sku-wrap {
    // 间距由父级 gap 控制
  }

  &__sku {
    display: inline-block;
    font-size: 22rpx;
    color: var(--color-text-2, #71717a);
    background: var(--color-bg-3, #f4f4f5);
    padding: 4rpx 12rpx;
    border-radius: var(--radius-sm, 8rpx);
  }

  &__price-row {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  &__price {
    color: var(--color-danger, #ef4444);
    font-size: 32rpx;
    font-weight: 700;
  }

  &__price-symbol {
    font-size: 22rpx;
    font-weight: 600;
  }
}

// 数量加减（inline，避免 mp 自定义组件 wrapper）
.cart-stepper {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: center;
  flex-shrink: 0;
  width: 180rpx;
  height: 52rpx;
  border-radius: var(--radius-sm, 8rpx);
  overflow: hidden;
  background: var(--color-bg-3, #f4f4f5);

  &__btn {
    width: 50rpx;
    min-width: 50rpx;
    max-width: 50rpx;
    height: 52rpx;
    line-height: 52rpx;
    text-align: center;
    font-size: 32rpx;
    color: var(--color-text-1, #18181b);
    flex-shrink: 0;

    &:active { background: var(--color-border-1, #e4e4e7); }
    &--disabled { color: var(--color-text-3, #a1a1aa); &:active { background: var(--color-bg-3, #f4f4f5); } }
  }
  &__num {
    flex: 1;
    height: 52rpx;
    line-height: 52rpx;
    text-align: center;
    background: var(--color-bg-1, #ffffff);
    font-size: 28rpx;
    color: var(--color-text-1, #18181b);
  }
}

.checkbox {
  width: 44rpx;
  height: 44rpx;
  border-radius: 50%;
  border: 2rpx solid var(--color-border-1, #e4e4e7);
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-1, #ffffff);
  box-sizing: border-box;

  &--checked {
    background: var(--color-primary, #2979ff);
    border-color: var(--color-primary, #2979ff);
  }
  &__tick {
    color: var(--color-bg-1, #ffffff);
    font-size: 28rpx;
    line-height: 1;
  }
}

// 底部 bar：浮在 d-tabbar 上方
.cart-bottom {
  position: fixed;
  bottom: calc(100rpx + env(safe-area-inset-bottom));
  left: 0;
  right: 0;
  height: 112rpx;
  background: var(--color-bg-1, #ffffff);
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: center;
  padding: 0 24rpx;
  border-top: 1rpx solid var(--color-border-1, #e4e4e7);
  box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.06);
  z-index: 600;
  box-sizing: border-box;

  &__select-all {
    display: flex;
    flex-direction: row;
    align-items: center;
    flex-shrink: 0;
    margin-right: 24rpx;
  }

  &__select-text {
    margin-left: 12rpx;
    font-size: 26rpx;
    color: var(--color-text-1, #18181b);
  }

  &__totals {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4rpx;
    flex: 1;
    margin-right: 16rpx;
  }

  &__discount {
    font-size: 22rpx;
    color: var(--color-text-2, #71717a);
    display: flex;
    gap: 8rpx;
    &-value {
      color: var(--color-primary, #2979ff);
    }
  }

  &__total {
    display: flex;
    align-items: baseline;
    overflow: hidden;
  }

  &__total-label {
    font-size: 26rpx;
    color: var(--color-text-2, #71717a);
  }

  &__total-price {
    font-size: 36rpx;
    font-weight: 700;
    color: var(--color-danger, #ef4444);
  }

  &__btn {
    flex-shrink: 0;
    min-width: 200rpx;
    height: 72rpx;
    line-height: 72rpx;
    padding: 0 32rpx;
    border-radius: 36rpx;
    background: var(--color-danger, #ef4444);
    color: var(--color-bg-1, #ffffff);
    font-size: 28rpx;
    font-weight: 600;
    text-align: center;

    &--disabled {
      background: var(--color-bg-3, #f4f4f5);
      color: var(--color-text-3, #a1a1aa);
    }
  }
}
</style>
