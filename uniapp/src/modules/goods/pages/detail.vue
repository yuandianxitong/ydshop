<template>
  <view class="detail">
    <!-- ===== Sticky Nav ===== -->
    <view
      class="detail__nav"
      :class="{ 'detail__nav--scrolled': scrollY >= 60 }"
      :style="{ paddingTop: statusBarHeight + 'px' }"
    >
      <view class="detail__nav-inner">
        <view class="detail__nav-btn" @tap="handleBack">
          <d-icon name="arrow-left" size="40rpx" :color="navIconColor" />
        </view>
        <text v-if="scrollY >= 200" class="detail__nav-title">
          {{ goods?.name || '商品详情' }}
        </text>
        <view class="detail__nav-actions">
          <view class="detail__nav-btn" @tap="goCart">
            <d-icon name="cart" size="40rpx" :color="navIconColor" />
          </view>
        </view>
      </view>
    </view>

    <!-- ===== Loading skeleton ===== -->
    <d-skeleton v-if="loading" variant="goods-detail" />

    <!-- ===== Network error ===== -->
    <d-empty
      v-else-if="!goods && loadError"
      icon="/static/images/error.png"
      text="加载失败，请检查网络"
      action-text="重试"
      @action="retryLoad"
    />

    <!-- ===== Goods not exist / 已下架 ===== -->
    <d-empty
      v-else-if="!goods"
      text="商品已下架"
      action-text="逛逛其他"
      @action="goHome"
    />

    <!-- ===== Main content ===== -->
    <scroll-view
      v-else
      class="detail__scroll"
      scroll-y
      @scroll="onScroll"
    >
      <!-- ----- Top: swiper + price + title + meta ----- -->
      <view class="detail__top">
        <!-- Swiper -->
        <swiper
          class="detail__swiper"
          :indicator-dots="false"
          :autoplay="false"
          :current="currentSwiperIndex"
          @change="onSwiperChange"
        >
          <swiper-item
            v-for="(img, idx) in allImages"
            :key="idx"
            @tap="previewImages(idx)"
          >
            <image :src="getImageUrl(img)" mode="aspectFill" class="detail__cover" lazy-load />
          </swiper-item>
        </swiper>
        <!-- Pagination indicator (横线) -->
        <view v-if="allImages.length > 1" class="detail__pager">
          <view
            v-for="(_, idx) in allImages"
            :key="idx"
            class="detail__pager-dot"
            :class="{ 'detail__pager-dot--active': idx === currentSwiperIndex }"
          />
        </view>

        <!-- Price block -->
        <view class="detail__price-block">
          <d-flash-sale-banner
            v-if="flashSale"
            :sale="flashSale"
            @expired="onFlashSaleExpired"
          />
          <d-price
            v-else
            :main="numericPrice"
            :original="(goods as any).original_price"
            :tag="(goods as any).promo_tag"
            size="xl"
          />
        </view>

        <!-- Title -->
        <view class="detail__title">{{ goods.name }}</view>

        <!-- Meta row -->
        <view class="detail__meta">
          <text>已售 {{ goods.sales_count || 0 }}</text>
          <text>评价 {{ reviewTotal }}</text>
          <text v-if="(goods as any).delivery_text">{{ (goods as any).delivery_text }}</text>
        </view>
      </view>

      <!-- ----- Middle: cells + reviews (Task 5) ----- -->
      <!-- ----- 8px gray bar ----- -->
      <d-section-bar />

      <!-- ----- Cell rows ----- -->
      <d-cell-group>
        <d-cell label="优惠" arrow @tap="onPromoTap">
          <view class="detail__cell-promo">
            <d-tag v-if="(goods as any).promo_tag" :text="(goods as any).promo_tag" variant="auxiliary" plain />
            <text class="detail__cell-text">{{ promoCellValue }}</text>
          </view>
        </d-cell>

        <d-cell label="规格" arrow @tap="openSpecSelector('cart')">
          <view class="detail__cell-specs">
            <d-tag
              v-for="(val, name) in selectedSpecMap"
              :key="name"
              :text="val"
              variant="neutral"
            />
            <text v-if="!Object.keys(selectedSpecMap).length" class="detail__cell-text">
              请选择规格
            </text>
          </view>
        </d-cell>

        <d-cell label="服务" arrow @tap="onServiceTap">
          <text class="detail__cell-text">7 天无理由 · 正品保障 · 闪电退款</text>
        </d-cell>

        <d-cell label="配送" :is-last="true" arrow @tap="onPickupPreview">
          <text class="detail__cell-text">{{ deliveryText }}</text>
        </d-cell>
      </d-cell-group>

      <!-- ----- 8px gray bar ----- -->
      <d-section-bar />

      <!-- ----- Review preview ----- -->
      <view class="detail__reviews">
        <view class="detail__reviews-header">
          <text class="detail__reviews-title">评价 ({{ reviewTotal }})</text>
          <text class="detail__reviews-link" @tap="goReviewList">查看全部 ›</text>
        </view>
        <view v-if="reviews.length" class="detail__reviews-summary">
          <u-rate :model-value="reviewAvgRating" readonly size="14" />
          <text class="detail__reviews-rate">好评率 {{ goodReviewRate }}%</text>
        </view>
        <view
          v-for="rv in reviews.slice(0, 1)"
          :key="rv.id"
          class="detail__review-item"
        >
          <view class="detail__review-header">
            <image
              :src="rv.avatar || defaultAvatar"
              mode="aspectFill"
              class="detail__review-avatar"
            />
            <text class="detail__review-user">{{ rv.nickname || '匿名用户' }}</text>
            <text class="detail__review-time">{{ formatTime(rv.created_at) }}</text>
          </view>
          <text class="detail__review-content">{{ rv.content }}</text>
          <view v-if="rv.images?.length" class="detail__review-imgs">
            <image
              v-for="(img, idx) in rv.images.slice(0, 3)"
              :key="idx"
              :src="getImageUrl(img)"
              mode="aspectFill"
              class="detail__review-img"
            />
          </view>
        </view>
        <d-empty v-if="!reviews.length" text="暂无评价" />
      </view>

      <!-- ----- 8px gray bar ----- -->
      <d-section-bar />

      <!-- ----- Detail rich-text (懒加载) ----- -->
      <view class="detail__rich">
        <view class="detail__rich-title">商品详情</view>
        <rich-text
          v-if="goods.detail"
          :nodes="formatRichText(goods.detail)"
          class="detail__rich-content"
        />
        <d-empty v-else text="暂无详情" />
      </view>

      <!-- ----- 已下架蒙层 ----- -->
      <view v-if="goods && !isGoodsActive" class="detail__sold-out-mask">
        <text class="detail__sold-out-text">已下架</text>
      </view>
    </scroll-view>

    <!-- ----- Bottom bar (inline 实现，避免 mp 自定义组件 wrapper 阻断 flex) ----- -->
    <view v-if="goods" class="detail__bar">
      <view class="detail__bar-icons">
        <view class="detail__bar-icon" @tap="goService">
          <d-icon name="customer-service" size="40rpx" color="#71717a" />
          <text class="detail__bar-icon-text">反馈</text>
        </view>
        <view class="detail__bar-icon" @tap="handleToggleFavorite">
          <d-icon
            :name="isFavorite ? 'heart-fill' : 'heart'"
            size="40rpx"
            :color="isFavorite ? '#ef4444' : '#71717a'"
          />
          <text class="detail__bar-icon-text">{{ isFavorite ? '已收藏' : '收藏' }}</text>
        </view>
        <view class="detail__bar-icon" @tap="goCart">
          <d-icon name="cart" size="40rpx" color="#71717a" />
          <text class="detail__bar-icon-text">购物车</text>
        </view>
      </view>
      <view class="detail__bar-actions">
        <view
          class="detail__bar-btn detail__bar-btn--cart"
          :class="{ 'detail__bar-btn--disabled': !isGoodsActive }"
          @tap="onCartTap"
        >
          {{ isGoodsActive ? '加入购物车' : '已下架' }}
        </view>
        <view
          class="detail__bar-btn detail__bar-btn--buy"
          :class="{ 'detail__bar-btn--disabled': !isGoodsActive }"
          @tap="onBuyTap"
        >
          {{ isGoodsActive ? '立即购买' : '已下架' }}
        </view>
      </view>
    </view>

    <!-- ----- Spec selector ----- -->
    <d-spec-selector
      v-if="goods"
      v-model:visible="specSelectorVisible"
      :specs="specGroups"
      :skus="skuItems"
      :default-image="getImageUrl(goods.images?.[0] || '')"
      :action="specAction"
      @confirm="handleSpecConfirm"
    />

    <!-- 满减规则 popup -->
    <d-promo-popup v-model:visible="promoPopupVisible" :rules="promoRules" />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { goodsApi, type GoodsDetail, type SkuItem } from '@/api/goods'
import { storeApi } from '@/api/store'
import { cartApi } from '@/api/cart'
import { memberApi } from '@/api/member'
import { useAppStore } from '@/store/app.store'
import { getToken } from '@/utils/auth'
import { redirectToLogin } from '@/utils/login-redirect'
import DPromoPopup from '@/components/d-promo-popup/d-promo-popup.vue'
import DFlashSaleBanner from '@/components/d-flash-sale-banner/d-flash-sale-banner.vue'
import { marketingApi, type FullDiscountRule, type FlashSaleMatched } from '@/api/marketing'

type SpecAction = 'cart' | 'buy'

// ---- stores ----
const appStore = useAppStore()

// ---- state ----
const goods = ref<GoodsDetail | null>(null)
const loading = ref(true)
const isFavorite = ref(false)
const specSelectorVisible = ref(false)
const specAction = ref<SpecAction>('cart')
const selectedSku = ref<SkuItem | null>(null)
const selectedQty = ref(1)
const currentSwiperIndex = ref(0)
const reviews = ref<any[]>([])
const reviewTotal = ref(0)
const loadError = ref(false)

// ---- system ----
const statusBarHeight = ref(0)
const safeAreaBottom = ref(0)
const bottomBarHeight = ref(80)
const scrollY = ref(0)

const defaultAvatar = '/static/images/default-avatar.png'

const promoRules = ref<FullDiscountRule[]>([])
const promoPopupVisible = ref(false)

async function loadFullDiscountRules() {
  if (!goods.value?.id) return
  try {
    const res = await marketingApi.getFullDiscountRules(goods.value.id as number)
    promoRules.value = Array.isArray(res) ? res : []
  } catch {
    promoRules.value = []
  }
}

const flashSale = ref<FlashSaleMatched | null>(null)

async function loadFlashSale() {
  if (!goods.value?.id) return
  try {
    const res = await marketingApi.getFlashSaleByGoods(goods.value.id as number)
    flashSale.value = res || null
  } catch {
    flashSale.value = null
  }
}

function onFlashSaleExpired() {
  flashSale.value = null
}

// ---- computed (Task 5) ----
const promoSummary = computed(() => {
  if (!goods.value) return ''
  const items: string[] = []
  if ((goods.value as any).promo_full_off) items.push((goods.value as any).promo_full_off)
  if ((goods.value as any).promo_installments) items.push(`${(goods.value as any).promo_installments} 期免息`)
  return items.join(' · ') || '查看活动'
})

const promoCellValue = computed(() => {
  if (!promoRules.value.length) return promoSummary.value || '暂无活动'
  const r0 = promoRules.value[0]
  const tiers = r0.rules || []
  if (!tiers.length) return r0.name
  if (r0.type === 'reduce') {
    return tiers.map((t: any) => `满 ${t.min_amount ?? t.min ?? 0} 减 ${t.value}`).join(' / ')
  }
  if (r0.type === 'percent') {
    return tiers.map((t) => `满 ${t.min} 享 ${(t.value * 10).toFixed(1).replace(/\.0$/, '')} 折`).join(' / ')
  }
  if (r0.type === 'freight') {
    return tiers.map((t) => `满 ${t.min} 包邮`).join(' / ')
  }
  return r0.name
})

const selectedSpecMap = computed<Record<string, string>>(() => {
  if (!selectedSku.value) return {}
  return selectedSku.value.spec_values ?? {}
})

const pickupStoreCount = ref(0)

const deliveryText = computed(() => {
  const modes = goods.value?.delivery_modes ?? ['express']
  const hasExpress = modes.includes('express')
  const hasPickup = modes.includes('pickup')
  if (hasExpress && hasPickup) return `支持快递 · 支持自提（${pickupStoreCount.value} 个门店）`
  if (hasPickup) return `仅自提（${pickupStoreCount.value} 个门店）`
  return '支持快递'
})

watch(
  () => goods.value?.id,
  async (id) => {
    if (!id) return
    const modes = goods.value?.delivery_modes ?? ['express']
    if (modes.includes('pickup')) {
      try {
        const data = await storeApi.list({ goods_id: id })
        pickupStoreCount.value = (data.list ?? []).length
      } catch {
        pickupStoreCount.value = 0
      }
    }
  },
  { immediate: true }
)

const reviewAvgRating = computed<number>(() => {
  if (!reviews.value.length) return 0
  const total = reviews.value.reduce((a, r) => a + (r.rating ?? 5), 0)
  return Math.round((total / reviews.value.length) * 10) / 10
})

const goodReviewRate = computed(() => {
  if (!reviews.value.length) return 0
  const good = reviews.value.filter(r => (r.rating ?? 5) >= 4).length
  return Math.round((good / reviews.value.length) * 100)
})

// ---- computed ----
const allImages = computed<string[]>(() => {
  if (!goods.value) return []
  const imgs: string[] = []
  goods.value.images?.forEach(img => {
    if (!imgs.includes(img)) imgs.push(img)
  })
  return imgs
})

const specGroups = computed(() => {
  // 数据源优先级：
  // 1. data.specs（GoodsSpuRepository::getDetail 已格式化，admin 端走这条）
  // 2. data.specNames（GoodsSpu::with('specNames.values') toArray，user 端 GoodsController::show 走这条）
  // 3. SKU.spec_values（GoodsSpuRepository 二次构建后的字段，admin 端有）
  // 注：当前用户端 GoodsController::show 直接返回 toArray，未经 Repository 格式化，
  //     所以实际走 #2 specNames 分支（每项含 values 关联数组）

  // 1. 后端预构建 specs
  const backendSpecs = (goods.value as any)?.specs as
    | Array<{ name: string; values: string[] }>
    | undefined
  if (backendSpecs?.length) {
    return backendSpecs.map((spec, specIdx) => ({
      name: spec.name,
      values: spec.values.map((v, vIdx) => ({
        id: specIdx * 1000 + vIdx + 1,
        value: v,
      })),
    }))
  }

  // 2. specNames（含 values 关联）
  const specNames = (goods.value as any)?.specNames as
    | Array<{ id: number; name: string; values: Array<{ id: number; value: string }> }>
    | undefined
  if (specNames?.length) {
    return specNames.map((spec, specIdx) => ({
      name: spec.name,
      values: (spec.values ?? []).map((v, vIdx) => ({
        id: v.id ?? specIdx * 1000 + vIdx + 1,
        value: v.value,
      })),
    }))
  }

  // 3. SKU.spec_values 兜底
  if (!goods.value?.skus?.length) return []
  const map: Record<string, Set<string>> = {}
  goods.value.skus.forEach(sku => {
    Object.entries(sku.spec_values || {}).forEach(([name, val]) => {
      if (!map[name]) map[name] = new Set()
      map[name].add(val)
    })
  })
  return Object.entries(map).map(([name, valSet], specIdx) => ({
    name,
    values: Array.from(valSet).map((v, vIdx) => ({
      id: specIdx * 1000 + vIdx + 1,
      value: v,
    })),
  }))
})

// 用户端 GoodsController::show 不调用 GoodsSpuRepository::getDetail()，
// SKU 上没有 spec_values（只有 spec_value_ids 数组）。这里基于 specNames 反查
// 重建 spec_values，让 d-spec-selector 的 SKU 匹配逻辑可以工作。
const skuItems = computed(() => {
  const skus = goods.value?.skus ?? []
  if (!skus.length) return []

  // 从 specNames 构建 specValueId → { specName, value } 映射
  const specNames = (goods.value as any)?.specNames as
    | Array<{ id: number; name: string; values: Array<{ id: number; value: string }> }>
    | undefined
  const idMap = new Map<number, { specName: string; value: string }>()
  ;(specNames ?? []).forEach(spec => {
    ;(spec.values ?? []).forEach(v => {
      idMap.set(v.id, { specName: spec.name, value: v.value })
    })
  })

  if (idMap.size === 0) {
    // 后端已经构建了 spec_values 或没有 specNames 数据，直接返回原 skus
    return skus
  }

  // 给每个 sku 补齐 spec_values 字段
  return skus.map(sku => {
    if (sku.spec_values && Object.keys(sku.spec_values).length) return sku
    const ids = (sku.spec_value_ids ?? []) as number[]
    const specValues: Record<string, string> = {}
    ids.forEach(id => {
      const found = idMap.get(id)
      if (found) specValues[found.specName] = found.value
    })
    return { ...sku, spec_values: specValues }
  })
})

const numericPrice = computed<number>(() => {
  if (selectedSku.value) return selectedSku.value.price
  if (!goods.value) return 0
  const inStock = goods.value.skus?.filter(s => s.stock > 0)
  if (!inStock?.length) return goods.value.min_price
  return Math.min(...inStock.map(s => s.price))
})

// ---- methods ----
function getImageUrl(url: string): string {
  if (!url) return ''
  return appStore.getImageUrl(url)
}

function formatRichText(html: string): string {
  if (!html) return ''
  const imgStyle = 'max-width:100% !important;height:auto !important;display:block;'
  // 先移除 img 标签上已有的 style 属性，再统一注入新样式
  return html.replace(/<img[^>]*>/gi, (match) => {
    const cleaned = match.replace(/\s*style\s*=\s*["'][^"']*["']/gi, '')
    return cleaned.replace(/<img/i, `<img style="${imgStyle}"`)
  })
}

function handleBack() {
  const pages = getCurrentPages()
  if (pages.length > 1) {
    uni.navigateBack()
  } else {
    uni.switchTab({ url: '/pages/index/index' })
  }
}

function goHome() {
  uni.switchTab({ url: '/pages/index/index' })
}

function goCart() {
  uni.switchTab({ url: '/pages/cart/index' })
}

function onScroll(e: any) {
  scrollY.value = e.detail.scrollTop
}

function onSwiperChange(e: any) {
  currentSwiperIndex.value = e.detail?.current ?? 0
}

function previewImages(idx: number) {
  const urls = allImages.value.map(img => getImageUrl(img))
  uni.previewImage({ current: urls[idx], urls })
}

// ---- Task 5 functions ----
function onPromoTap() {
  if (!promoRules.value.length) {
    uni.showToast({ title: '该商品暂无活动', icon: 'none' })
    return
  }
  promoPopupVisible.value = true
}
function onServiceTap() {
  uni.showToast({ title: '服务说明', icon: 'none' })
}
function onPickupPreview() {
  const modes = goods.value?.delivery_modes ?? ['express']
  if (!modes.includes('pickup')) return
  uni.navigateTo({
    url: `/modules/checkout/pages/pickup-store?readonly=1&goodsId=${goods.value?.id}`,
  })
}
function goReviewList() {
  if (!goods.value) return
  uni.navigateTo({ url: `/modules/goods/pages/reviews?goods_id=${goods.value.id}` })
}
function formatTime(s: string | number | Date | undefined): string {
  if (!s) return ''
  const d = new Date(s)
  if (Number.isNaN(d.getTime())) return ''
  const yy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yy}-${mm}-${dd}`
}

function goService() {
  const goodsName = encodeURIComponent(goods.value?.name || '')
  uni.navigateTo({ url: `/modules/feedback/pages/feedback?type=other&goods=${goodsName}` })
}

function openSpecSelector(action: SpecAction = 'cart') {
  specAction.value = action
  specSelectorVisible.value = true
}

async function doAddToCart(sku: SkuItem, qty: number) {
  try {
    await cartApi.addToCart({ sku_id: sku.id, quantity: qty })
    uni.showToast({ title: '已加入购物车', icon: 'success' })
  } catch {
    // error shown by request layer
  }
}

function doBuyNow(sku: SkuItem, qty: number) {
  const goodsId = goods.value?.id
  if (!goodsId) return
  const matchedFlashItem = flashSale.value?.matched_item
  const flashParam = matchedFlashItem?.sku_id === sku.id ? `&flash_item_id=${matchedFlashItem.id}` : ''
  uni.navigateTo({
    url: `/modules/checkout/pages/index?goods_id=${goodsId}&sku_id=${sku.id}&quantity=${qty}${flashParam}`,
  })
}

function handleSpecConfirm(payload: { sku: SkuItem; quantity: number }) {
  selectedSku.value = payload.sku
  selectedQty.value = payload.quantity
  if (specAction.value === 'cart') {
    doAddToCart(payload.sku, payload.quantity)
  } else {
    doBuyNow(payload.sku, payload.quantity)
  }
}

async function handleToggleFavorite() {
  if (!getToken()) {
    redirectToLogin()
    return
  }
  if (!goods.value) return
  try {
    const result = await memberApi.toggleFavorite({ spu_id: ((goods.value as any).spu_id as number | undefined) || goods.value.id })
    isFavorite.value = result.favorited
    uni.showToast({
      title: isFavorite.value ? '已收藏' : '已取消收藏',
      icon: 'none',
    })
  } catch {
    // ignore
  }
}

// ---- fetch ----
async function fetchGoods(id: string) {
  loading.value = true
  loadError.value = false
  try {
    const result = await goodsApi.getGoodsDetail(id)
    goods.value = result
    selectedSku.value = result.skus?.find(s => s.stock > 0) ?? result.skus?.[0] ?? null
    // 商品加载完成后并发加载营销规则（不阻塞主流程）
    Promise.all([loadFullDiscountRules(), loadFlashSale()])
  } catch (e: any) {
    goods.value = null
    // 区分：商品不存在（404 等）vs 网络错误
    const status = e?.statusCode || e?.status
    loadError.value = !(status === 404)
  } finally {
    loading.value = false
  }
}

function retryLoad() {
  // 从当前路由参数取 id
  const pages = getCurrentPages()
  const cur: any = pages[pages.length - 1]
  const id = cur?.options?.id as string | undefined
  if (!id) return
  fetchGoods(id).then(() => {
    const spuId = (goods.value as any)?.spu_id || id
    fetchReviews(spuId)
  })
}

async function fetchReviews(id: string | number) {
  try {
    const result = await goodsApi.getGoodsReviews(id, { page_no: 1, page_size: 3 })
    reviews.value = result.list
    reviewTotal.value = result.pagination?.total ?? 0
  } catch {
    // ignore
  }
}

async function fetchFavorite(spuId: string | number) {
  if (!getToken()) return
  try {
    const result = await memberApi.checkFavorite(spuId)
    isFavorite.value = result.favorited
  } catch {
    // ignore
  }
}

// 商品上架状态判断：后端 goods_spu.status 是 enum('draft','on_sale','off_sale')
// 字符串枚举（非 tinyint），仅 'on_sale' 视为上架。字段缺失（undefined）按上架处理
// 避免老数据兼容问题。
const isGoodsActive = computed(() => {
  const s = (goods.value as any)?.status
  return s === 'on_sale' || s === undefined
})

// nav 图标颜色：未滚动时白色（透明 nav 在大图上），滚动后切换为深灰
const navIconColor = computed(() => (scrollY.value >= 60 ? '#18181b' : '#ffffff'))

function onCartTap() {
  if (!isGoodsActive.value) return
  if (!getToken()) {
    redirectToLogin()
    return
  }
  openSpecSelector('cart')
}
function onBuyTap() {
  if (!isGoodsActive.value) return
  if (!getToken()) {
    redirectToLogin()
    return
  }
  openSpecSelector('buy')
}

// ---- lifecycle ----
onLoad(async (options: any) => {
  const id = options?.id
  if (!id) {
    loading.value = false
    return
  }

  // Get system info for safe areas
  const sysInfo = uni.getSystemInfoSync()
  statusBarHeight.value = sysInfo.statusBarHeight || 0
  safeAreaBottom.value = (sysInfo as any).safeAreaInsets?.bottom ?? 0
  bottomBarHeight.value = 100 + safeAreaBottom.value

  await appStore.getConfig()
  await fetchGoods(id)

  if (goods.value) {
    const spuId = ((goods.value as any).spu_id as number | undefined) || goods.value.id
    fetchReviews(spuId)
    fetchFavorite(spuId)
    // 已登录则记录浏览（失败静默 — 浏览记录是辅助功能，不应阻塞详情体验）
    if (getToken()) {
      memberApi.recordBrowseHistory(spuId).catch(() => {})
    }
  }
})

onShow(() => {
  // 后续登录回调时重拉 favorite（如原文件有此逻辑则保留）
})

// 显式保留供模板或其他函数使用的变量，避免 TS 未使用变量警告
void defaultAvatar
void selectedQty
void bottomBarHeight
void safeAreaBottom
</script>

<style lang="scss" scoped>

.detail {
  position: relative;
  min-height: 100vh;
  background: var(--color-bg-2);
  padding-bottom: env(safe-area-inset-bottom);

  &__nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: transparent;
    transition: background var(--duration-base);

    &--scrolled {
      background: var(--color-bg-1);
      border-bottom: 1rpx solid var(--color-border-2);
    }
  }
  &__nav-inner {
    display: flex;
    align-items: center;
    height: 88rpx;
    padding: 0 var(--space-3);
  }
  &__nav-btn {
    width: 64rpx;
    height: 64rpx;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-1);
    font-size: 32rpx;
  }
  &__nav--scrolled &__nav-btn {
    background: transparent;
  }
  &__nav-title {
    flex: 1;
    text-align: center;
    font-size: var(--font-base);
    font-weight: 600;
    color: var(--color-text-1);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
  &__nav-actions {
    display: flex;
    gap: var(--space-1);
  }

  &__scroll {
    height: 100vh;
    box-sizing: border-box;
    padding-bottom: calc(112rpx + env(safe-area-inset-bottom));
  }

  &__top {
    background: var(--color-bg-1);
  }

  &__swiper {
    width: 100%;
    height: 750rpx; // 1:1 (375 * 2 rpx)
  }
  &__cover { width: 100%; height: 100%; }

  &__pager {
    position: absolute;
    top: 720rpx;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6rpx;
  }
  &__pager-dot {
    width: 10rpx;
    height: 10rpx;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.2);
    transition: all var(--duration-base);

    &--active {
      width: 28rpx;
      border-radius: 5rpx;
      background: var(--color-text-1);
    }
  }

  &__price-block {
    padding: var(--space-3) var(--space-4) var(--space-1);
  }
  &__title {
    padding: var(--space-1) var(--space-4) var(--space-2);
    font-size: var(--font-md);
    font-weight: 500;
    color: var(--color-text-1);
    line-height: var(--line-snug);
  }
  &__meta {
    display: flex;
    gap: var(--space-3);
    padding: 0 var(--space-4) var(--space-3);
    font-size: var(--font-xs);
    color: var(--color-text-3);
  }

  // Task 6 styles
  &__rich {
    background: var(--color-bg-1);
    padding: var(--space-4);
  }
  &__rich-title {
    font-size: var(--font-md);
    font-weight: 600;
    color: var(--color-text-1);
    margin-bottom: var(--space-3);
  }
  &__rich-trigger {
    padding: var(--space-4) 0;
    text-align: center;
  }
  &__rich-tip {
    font-size: var(--font-sm);
    color: var(--color-text-3);
  }
  &__rich-content {
    font-size: var(--font-base);
    color: var(--color-text-1);
    line-height: var(--line-normal);
  }

  // 底部 bar inline 实现
  &__bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100;
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    height: 112rpx;
    padding: 0 var(--space-3);
    padding-bottom: env(safe-area-inset-bottom);
    background: var(--color-bg-1);
    border-top: 1rpx solid var(--color-border-2);
    box-sizing: content-box;
  }
  &__bar-icons {
    flex-shrink: 0;
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
  }
  &__bar-actions {
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    width: 0;
    min-width: 0;
    margin-left: var(--space-2);
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: flex-end;
  }

  &__bar-icon {
    width: 80rpx;
    margin-right: var(--space-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--color-text-2);
    flex-shrink: 0;
  }
  &__bar-icon-text {
    font-size: var(--font-xs);
    margin-top: 2rpx;
  }

  &__bar-btn {
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    width: 0;
    min-width: 0;
    height: 72rpx;
    line-height: 72rpx;
    text-align: center;
    border-radius: var(--radius-2xl);
    font-size: var(--font-sm);
    font-weight: 600;
    margin-left: var(--space-2);

    &--cart {
      background: var(--color-bg-3);
      color: var(--color-text-1);
      border: 1rpx solid var(--color-border-1);
    }
    &--buy {
      background: var(--color-primary);
      color: #fff;
    }
    &--disabled {
      opacity: 0.5;
    }
  }

  &__sold-out-mask {
    position: absolute;
    top: 88rpx; // nav height
    left: 0;
    right: 0;
    height: 750rpx; // swiper height
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
  }
  &__sold-out-text {
    font-size: var(--font-xl);
    color: #fff;
    font-weight: 700;
    letter-spacing: 4rpx;
  }

  // Task 5 styles
  &__cell-promo,
  &__cell-specs {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--space-1);
    justify-content: flex-end;
  }
  &__cell-text {
    font-size: var(--font-sm);
    color: var(--color-text-1);
  }

  &__reviews {
    background: var(--color-bg-1);
    padding: var(--space-3) var(--space-4);
  }
  &__reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-2);
  }
  &__reviews-title {
    font-size: var(--font-md);
    font-weight: 600;
    color: var(--color-text-1);
  }
  &__reviews-link {
    font-size: var(--font-sm);
    color: var(--color-text-3);
  }
  &__reviews-summary {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-2);
    padding-bottom: var(--space-2);
    border-bottom: 1rpx solid var(--color-border-2);
  }
  &__reviews-rate {
    font-size: var(--font-xs);
    color: var(--color-text-2);
  }
  &__review-item {
    padding: var(--space-2) 0;
  }
  &__review-header {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-1);
  }
  &__review-avatar {
    width: 56rpx;
    height: 56rpx;
    border-radius: 50%;
  }
  &__review-user {
    flex: 1;
    font-size: var(--font-sm);
    color: var(--color-text-1);
  }
  &__review-time {
    font-size: var(--font-xs);
    color: var(--color-text-3);
  }
  &__review-content {
    font-size: var(--font-sm);
    color: var(--color-text-1);
    line-height: var(--line-normal);
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
  }
  &__review-imgs {
    display: flex;
    gap: var(--space-1);
    margin-top: var(--space-1);
  }
  &__review-img {
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--radius-sm);
  }
}
</style>
