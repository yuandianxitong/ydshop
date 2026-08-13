<template>
  <page-meta
    :page-style="themePageStyle"
    :navigation-bar-background-color="navBg"
    :navigation-bar-text-style="navText"
  />
  <view class="category-tab-wrap">
    <!-- 顶部全宽搜索区（白底 + 灰色 pill 输入框） -->
    <view class="cat-search">
      <view class="cat-search__pill" @tap="goSearch">
        <d-icon name="search" size="28rpx" color="#999" />
        <text class="cat-search__placeholder">搜索我喜欢的</text>
      </view>
    </view>

    <!-- 主体区（绝对定位锁死高度，避免内部 scroll-view 撑出 viewport） -->
    <view class="cat-body" :class="{ 'cat-body--with-action': hasActionBar }">
      <!-- uniapp 不支持 <component :is>（小程序限制），改用 v-if 链 -->
      <Style2 v-if="styleKey === 'style2'" @add-cart="onAddCart" />
      <Style3 v-else-if="styleKey === 'style3'" @add-cart="onAddCart" />
      <Style1 v-else />
    </view>

    <!-- style2/3 才有的购物车 + 去结算动作条（位于 d-tabbar 上方） -->
    <d-cart-action-bar
      v-if="hasActionBar"
      :count="cartCount"
      :goods-amount="cartCalc.goods_amount"
      :discount-amount="cartCalc.discount_amount"
      @tap-cart="openCartPanel"
      @tap-checkout="goCheckout"
    />

    <d-tabbar current-path="/pages/category/index" />

    <!-- 加购规格选择器（按需渲染） -->
    <d-spec-selector
      v-if="specGoods"
      v-model:visible="specSelectorVisible"
      :specs="specGroups"
      :skus="skuItems"
      :default-image="specDefaultImage"
      action="cart"
      @confirm="handleSpecConfirm"
    />

    <!-- 购物车面板（按需渲染） -->
    <d-cart-panel
      v-model:visible="cartPanelVisible"
      :items="cartItems"
      @change="loadCart"
    />
  </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'
import { goodsApi, type GoodsDetail, type GoodsItem, type SkuItem } from '@/api/goods'
import { cartApi, type CartItem } from '@/api/cart'
import { orderApi } from '@/api/order'
import { buildSpecGroups, buildSkuItems } from '@/utils/goods-spec'
import { getToken } from '@/utils/auth'
import { useThemePageStyle } from '@/composables/useThemePageStyle'

const { themePageStyle, navBg, navText } = useThemePageStyle()
import Style1 from './styles/Style1.vue'
import Style2 from './styles/Style2.vue'
import Style3 from './styles/Style3.vue'

type StyleKey = 'style1' | 'style2' | 'style3'

const appStore = useAppStore()

const styleKey = computed<StyleKey>(() =>
  (appStore.config?.category_page_style_uniapp || 'style1') as StyleKey,
)

const hasActionBar = computed(() => styleKey.value === 'style2' || styleKey.value === 'style3')

// 购物车状态
const cartItems = ref<CartItem[]>([])
const cartCount = computed(() =>
  cartItems.value.reduce((sum, it) => sum + (it.quantity || 0), 0),
)
// 后端 order/calc 返回的金额（合计 / 优惠减 / 实付）
const cartCalc = ref({
  goods_amount: '0.00',
  discount_amount: '0.00',
  pay_amount: '0.00',
})

// 规格选择器状态
const specSelectorVisible = ref(false)
const specGoods = ref<GoodsDetail | null>(null)
const specGroups = computed(() => (specGoods.value ? buildSpecGroups(specGoods.value) : []))
const skuItems = computed(() => (specGoods.value ? buildSkuItems(specGoods.value) : []))
const specDefaultImage = computed(() =>
  specGoods.value ? appStore.getImageUrl(specGoods.value.images?.[0] || '') : '',
)

// 购物车面板
const cartPanelVisible = ref(false)

// ───── actions ─────

function goSearch() {
  uni.navigateTo({ url: '/modules/goods/pages/search' })
}

async function loadCart() {
  if (!getToken()) {
    cartItems.value = []
    cartCalc.value = { goods_amount: '0.00', discount_amount: '0.00', pay_amount: '0.00' }
    return
  }
  try {
    cartItems.value = await cartApi.getCartList()
    await refreshCalc()
  } catch {
    // ignore
  }
}

async function refreshCalc() {
  if (cartItems.value.length === 0) {
    cartCalc.value = { goods_amount: '0.00', discount_amount: '0.00', pay_amount: '0.00' }
    return
  }
  try {
    const res = await orderApi.calc({
      skus: cartItems.value.map(i => ({ sku_id: i.sku_id, quantity: i.quantity })),
    })
    cartCalc.value = {
      goods_amount: res.goods_amount,
      discount_amount: res.discount_amount,
      pay_amount: res.pay_amount,
    }
  } catch {
    // 调价接口失败时回退本地手算（无优惠）
    const sum = cartItems.value.reduce((s, i) => s + Number(i.price) * i.quantity, 0)
    cartCalc.value = {
      goods_amount: sum.toFixed(2),
      discount_amount: '0.00',
      pay_amount: sum.toFixed(2),
    }
  }
}

async function onAddCart(goods: GoodsItem) {
  uni.showLoading({ title: '加载中', mask: true })
  try {
    const detail = await goodsApi.getGoodsDetail(goods.id)
    specGoods.value = detail
    specSelectorVisible.value = true
  } catch {
    uni.showToast({ title: '加载失败', icon: 'none' })
  } finally {
    uni.hideLoading()
  }
}

async function handleSpecConfirm(payload: { sku: SkuItem; quantity: number; action: 'cart' | 'buy' }) {
  try {
    await cartApi.addToCart({ sku_id: payload.sku.id, quantity: payload.quantity })
    uni.showToast({ title: '已加入购物车', icon: 'success' })
    specSelectorVisible.value = false
    // 刷新购物车（拿最新数量与列表）
    loadCart()
  } catch (e: any) {
    uni.showToast({ title: e?.message || '加购失败', icon: 'none' })
  }
}

async function openCartPanel() {
  await loadCart()
  cartPanelVisible.value = true
}

function goCheckout() {
  if (cartCount.value === 0) {
    uni.showToast({ title: '购物车为空', icon: 'none' })
    return
  }
  uni.navigateTo({ url: '/modules/checkout/pages/index' })
}

onShow(() => {
  if (hasActionBar.value) {
    loadCart()
  }
})
</script>

<style lang="scss" scoped>
.category-tab-wrap {
  position: relative;
  height: 100vh;
  background: #ffffff;
}

.cat-search {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 96rpx;
  display: flex;
  align-items: center;
  padding: 0 24rpx;
  box-sizing: border-box;
  background: #ffffff;
  z-index: 5;
}

.cat-search__pill {
  flex: 1;
  height: 64rpx;
  border-radius: 32rpx;
  background: #f4f4f4;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
}

.cat-search__placeholder {
  font-size: 24rpx;
  color: #999;
}

// body 区：绝对定位填满 (顶部搜索 96rpx) ~ (底部 tabbar 100rpx + safe-area)
// 锁死后内部 scroll-view 的 height: 100% 才能可靠解析
.cat-body {
  position: absolute;
  top: 96rpx;
  left: 0;
  right: 0;
  bottom: calc(100rpx + env(safe-area-inset-bottom));

  // style2/3 多一层 96rpx 的 action bar，给 body 留出位置避免遮挡
  &--with-action {
    bottom: calc(100rpx + 96rpx + env(safe-area-inset-bottom));
  }
}
</style>
