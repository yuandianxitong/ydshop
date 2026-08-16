<template>
  <div class="bg-gray-50 min-h-screen pb-12">
    <div class="mx-auto max-w-1200px px-4 pt-5">

      <!-- Loading skeleton -->
      <div v-if="loading" class="flex gap-6 bg-white rounded-sm p-6">
        <div class="w-80 flex-shrink-0 space-y-3">
          <div class="bg-gray-200 animate-pulse rounded" style="aspect-ratio:1/1;" />
          <div class="flex gap-2">
            <div v-for="i in 4" :key="i" class="flex-1 bg-gray-200 animate-pulse rounded" style="aspect-ratio:1/1;" />
          </div>
        </div>
        <div class="flex-1 space-y-4 pt-2">
          <div class="h-6 bg-gray-200 animate-pulse rounded w-3/4" />
          <div class="h-4 bg-gray-200 animate-pulse rounded w-1/2" />
          <div class="h-10 bg-gray-200 animate-pulse rounded w-1/3" />
        </div>
      </div>

      <!-- Error / not found -->
      <div v-else-if="!goods" class="text-center py-24 bg-white rounded-sm">
        <span class="i-carbon-warning text-4xl text-gray-300 block mb-4" />
        <p class="text-gray-400">商品不存在或已下架</p>
        <NuxtLink to="/goods" class="mt-4 inline-block text-[var(--color-primary)] text-sm hover:underline">
          返回商品列表
        </NuxtLink>
      </div>

      <!-- Main content -->
      <template v-else>
        <!-- ===== Top: two-column ===== -->
        <div class="flex gap-6 bg-white rounded-sm p-6">

          <!-- Left: image gallery with hover zoom -->
          <div class="w-80 flex-shrink-0">
            <!-- Main image wrapper (relative; no overflow-hidden so magnifier panel can float outside) -->
            <div
              ref="mainImgRef"
              class="relative bg-gray-100 cursor-crosshair select-none"
              style="aspect-ratio:1/1;"
              @mousemove="onZoomMove"
              @mouseenter="zoomActive = true"
              @mouseleave="zoomActive = false"
            >
              <img
                :src="activeImage || coverImage"
                :alt="goods.name"
                class="w-full h-full object-cover rounded"
                draggable="false"
                @error="onImgError"
              />
              <!-- Zoom lens (small translucent box on main image) -->
              <div
                v-show="zoomActive"
                class="absolute pointer-events-none border border-gray-300 bg-white/30"
                :style="{ width: `${LENS_SIZE}px`, height: `${LENS_SIZE}px`, left: `${lensX}px`, top: `${lensY}px` }"
              />
              <!-- Zoom result panel (floats to the right of main image) -->
              <div
                v-show="zoomActive"
                class="absolute top-0 left-full ml-3 bg-white border border-gray-200 overflow-hidden pointer-events-none z-20 shadow-md"
                :style="{ width: `${RESULT_SIZE}px`, height: `${RESULT_SIZE}px` }"
              >
                <div
                  class="w-full h-full"
                  :style="{
                    backgroundImage: `url('${activeImage || coverImage}')`,
                    backgroundRepeat: 'no-repeat',
                    backgroundSize: `${ZOOMED_BG}px ${ZOOMED_BG}px`,
                    backgroundPosition: `-${lensX * ZOOM}px -${lensY * ZOOM}px`,
                  }"
                />
              </div>
            </div>
            <!-- Thumbnails -->
            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
              <div
                v-for="(img, idx) in allImages"
                :key="idx"
                class="flex-shrink-0 w-16 h-16 rounded border-2 overflow-hidden cursor-pointer transition-all"
                :class="activeImage === img ? 'border-[var(--color-primary)]' : 'border-gray-200 hover:border-gray-400'"
                @mouseenter="activeImage = img"
                @click="activeImage = img"
              >
                <img
                  :src="img"
                  :alt="`图片${idx + 1}`"
                  class="w-full h-full object-cover"
                  @error="onImgError"
                />
              </div>
            </div>
          </div>

          <!-- Right: info + spec selector -->
          <div class="flex-1 min-w-0 flex flex-col">
            <!-- Name -->
            <h1 class="text-xl font-bold text-gray-800 leading-snug">{{ goods.name }}</h1>
            <!-- Subtitle -->
            <p v-if="goods.subtitle" class="mt-1.5 text-sm text-orange-500">{{ goods.subtitle }}</p>
            <!-- Category -->
            <p v-if="goods.category_name" class="mt-1 text-xs text-gray-400">
              分类：{{ goods.category_name }}
            </p>

            <!-- Price display -->
            <div class="mt-3 py-3 px-4 bg-gray-50 rounded">
              <template v-if="selectedSku">
                <span class="text-2xl font-bold text-red-500">¥{{ formatPrice(selectedSku.price) }}</span>
                <span
                  v-if="skuOriginalPrice(selectedSku) > Number(selectedSku.price)"
                  class="ml-2 text-sm text-gray-400 line-through"
                >
                  ¥{{ formatPrice(skuOriginalPrice(selectedSku)) }}
                </span>
              </template>
              <template v-else>
                <span class="text-2xl font-bold text-red-500">{{ displayPriceRange }}</span>
              </template>
            </div>

            <!-- SpecSelector -->
            <div class="mt-4 flex-1">
              <SpecSelector
                v-if="specGroups.length > 0 || goods.skus.length > 0"
                :specs="specGroups"
                :skus="skuItems"
                @change="handleSpecChange"
                @confirm="handleSpecConfirm"
              >
                <template #actions="{ disabled, onConfirm }">
                  <div class="flex gap-3">
                    <button
                      class="btn-cart flex-1"
                      :disabled="disabled"
                      @click="handleAddToCart()"
                    >
                      <span class="i-carbon-shopping-cart mr-1" />
                      加入购物车
                    </button>
                    <button
                      class="btn-buy flex-1"
                      :disabled="disabled"
                      @click="handleBuyNow()"
                    >
                      立即购买
                    </button>
                  </div>
                </template>
              </SpecSelector>
            </div>

            <!-- Favorite -->
            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-2">
              <button
                class="flex items-center gap-1.5 text-sm transition-colors"
                :class="isFavorite ? 'text-red-500' : 'text-gray-400 hover:text-red-400'"
                @click="handleToggleFavorite"
              >
                <span
                  :class="isFavorite ? 'i-carbon-favorite-filled text-lg' : 'i-carbon-favorite text-lg'"
                />
                {{ isFavorite ? '已收藏' : '收藏商品' }}
              </button>
              <span class="text-gray-200">|</span>
              <span class="text-sm text-gray-400">已售 {{ salesCount }} 件</span>
            </div>
          </div>
        </div>

        <!-- ===== Bottom: Tabs ===== -->
        <div class="mt-5 bg-white rounded-sm">
          <NTabs type="line" animated :default-value="'detail'" class="goods-tabs px-4">

            <!-- Tab 1: 商品详情（富文本） -->
            <NTabPane name="detail" tab="商品详情">
              <div class="py-4 px-2">
                <div
                  v-if="goods.detail"
                  class="goods-description prose max-w-none"
                  v-html="sanitizedDetail"
                />
                <div v-else class="text-center py-12 text-gray-400 text-sm">暂无详情</div>
              </div>
            </NTabPane>

            <!-- Tab 2: 规格参数 -->
            <NTabPane name="attrs" tab="规格参数">
              <div class="py-4 px-2">
                <table
                  v-if="paramAttrs.length"
                  class="w-full text-sm border-collapse"
                >
                  <tbody>
                    <tr
                      v-for="attr in paramAttrs"
                      :key="attr.name"
                      class="border-b border-gray-100"
                    >
                      <td class="py-2.5 pr-4 w-32 text-gray-500 font-medium align-top">{{ attr.name }}</td>
                      <td class="py-2.5 text-gray-700">{{ attr.values.join('、') }}</td>
                    </tr>
                  </tbody>
                </table>
                <div v-else class="text-center py-12 text-gray-400 text-sm">暂无规格参数</div>
              </div>
            </NTabPane>

            <!-- Tab 3: 商品评价 -->
            <NTabPane name="reviews" tab="商品评价">
              <div class="py-4 px-2">
                <div v-if="reviewsLoading" class="text-center py-8 text-gray-400 text-sm">加载中...</div>
                <template v-else-if="reviews.length">
                  <div
                    v-for="review in reviews"
                    :key="review.id"
                    class="review-item pb-5 mb-5 border-b border-gray-100 last:border-0"
                  >
                    <div class="flex items-center gap-3 mb-2">
                      <img
                        :src="review.avatar || defaultAvatar"
                        :alt="review.nickname"
                        class="w-9 h-9 rounded-full object-cover"
                        @error="onAvatarError"
                      />
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700">{{ review.nickname }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                          <NRate :value="review.rating" readonly :allow-half="false" size="small" />
                          <span class="text-xs text-gray-400">{{ review.created_at?.substring(0, 10) }}</span>
                        </div>
                      </div>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ review.content }}</p>
                    <!-- Review images -->
                    <div v-if="review.images && review.images.length" class="mt-2 flex gap-2 flex-wrap">
                      <img
                        v-for="(img, idx) in review.images"
                        :key="idx"
                        :src="img"
                        class="w-16 h-16 object-cover rounded cursor-pointer hover:opacity-90"
                        @error="onImgError"
                      />
                    </div>
                  </div>
                  <!-- Load more -->
                  <div
                    v-if="reviewTotal > reviews.length"
                    class="text-center pt-2"
                  >
                    <NButton
                      text
                      type="primary"
                      size="small"
                      :loading="reviewsLoading"
                      @click="loadMoreReviews"
                    >
                      查看更多评价 ({{ reviewTotal }}条)
                    </NButton>
                  </div>
                </template>
                <div v-else class="text-center py-12 text-gray-400 text-sm">暂无评价</div>
              </div>
            </NTabPane>

          </NTabs>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup lang="ts">
import { NButton, NTabs, NTabPane, NRate, useMessage } from 'naive-ui'
import DOMPurify from 'dompurify'
import SpecSelector from '~/components/SpecSelector.vue'
import type { SpecGroup, SkuItem as SelectorSkuItem } from '~/components/SpecSelector.vue'
import { goodsApi, type GoodsDetail, type ReviewItem } from '~/api/goods'
import { cartApi } from '~/api/cart'
import { memberApi } from '~/api/member'
import { useUserStore } from '~/store/user'

const route = useRoute()
const router = useRouter()
const message = useMessage()
const userStore = useUserStore()

const id = computed(() => route.params.id as string)

// ---- data ----
const goods = ref<GoodsDetail | null>(null)
const loading = ref(true)
const activeImage = ref('')
const isFavorite = ref(false)

// ---- hover zoom (taobao-style magnifier) ----
const MAIN_IMG_SIZE = 320   // 主图边长（与 w-80 / aspect-ratio:1/1 对齐）
const LENS_SIZE = 120       // 镜头方块大小（在主图上）
const RESULT_SIZE = 360     // 右侧放大结果面板大小
const ZOOM = RESULT_SIZE / LENS_SIZE   // 派生倍数，保证 lens 范围与结果面板对应
const ZOOMED_BG = MAIN_IMG_SIZE * ZOOM // 原图在结果面板里的等效宽高

const mainImgRef = ref<HTMLDivElement | null>(null)
const zoomActive = ref(false)
const lensX = ref(0)
const lensY = ref(0)

function onZoomMove(e: MouseEvent) {
  const el = mainImgRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  // 把鼠标点居中放进镜头方块，再 clamp 到图内
  let x = e.clientX - rect.left - LENS_SIZE / 2
  let y = e.clientY - rect.top - LENS_SIZE / 2
  x = Math.max(0, Math.min(rect.width - LENS_SIZE, x))
  y = Math.max(0, Math.min(rect.height - LENS_SIZE, y))
  lensX.value = x
  lensY.value = y
}

const reviews = ref<ReviewItem[]>([])
const reviewsLoading = ref(false)
const reviewTotal = ref(0)
const reviewPage = ref(1)

const currentSku = ref<SelectorSkuItem | null>(null)
const currentQty = ref(1)

// ---- derived ----
const allImages = computed<string[]>(() => {
  if (!goods.value) return []
  const imgs: string[] = []
  if (goods.value.cover) imgs.push(goods.value.cover)
  if (goods.value.images?.length) {
    goods.value.images.forEach(img => {
      if (!imgs.includes(img)) imgs.push(img)
    })
  }
  return imgs
})

const coverImage = computed(() => goods.value?.cover || goods.value?.images?.[0] || '')
const salesCount = computed(() => goods.value?.sales ?? goods.value?.sales_count ?? 0)

const specGroups = computed<SpecGroup[]>(() => {
  const backendSpecs = goods.value?.specs
  if (backendSpecs?.length) {
    return backendSpecs.map((spec, specIdx) => ({
      name: spec.name,
      values: spec.values.map((v, vIdx) => ({
        id: specIdx * 1000 + vIdx + 1,
        value: v,
      })),
    }))
  }

  const specNames = goods.value?.specNames
  if (specNames?.length) {
    return specNames.map((spec, specIdx) => ({
      name: spec.name,
      values: (spec.values ?? []).map((v, vIdx) => ({
        id: v.id ?? specIdx * 1000 + vIdx + 1,
        value: v.value,
      })),
    }))
  }

  if (!goods.value?.skus?.length) return []
  const map: Record<string, Set<string>> = {}
  goods.value.skus.forEach((sku) => {
    Object.entries(sku.spec_values || sku.attributes || {}).forEach(([name, val]) => {
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

const skuItems = computed<SelectorSkuItem[]>(() => {
  const skus = goods.value?.skus ?? []
  if (!skus.length) return []

  const idMap = new Map<number, { specName: string; value: string }>()
  ;(goods.value?.specNames ?? []).forEach((spec) => {
    ;(spec.values ?? []).forEach((v) => {
      idMap.set(v.id, { specName: spec.name, value: v.value })
    })
  })

  return skus.map((sku) => {
    let specValues = sku.spec_values && Object.keys(sku.spec_values).length
      ? sku.spec_values
      : undefined
    if (!specValues && idMap.size) {
      specValues = {}
      ;(sku.spec_value_ids ?? []).forEach((vid) => {
        const found = idMap.get(vid)
        if (found) specValues![found.specName] = found.value
      })
    }
    const mapped = specValues || sku.attributes || {}
    return {
      ...sku,
      price: Number(sku.price),
      original_price: Number(sku.original_price ?? sku.market_price ?? 0),
      spec_values: mapped,
      attributes: mapped,
    }
  })
})

const selectedSku = computed<SelectorSkuItem | null>(() => currentSku.value)

const paramAttrs = computed(() => {
  const rawAttrs = goods.value?.attributes
  if (rawAttrs?.length && rawAttrs[0]?.name && Array.isArray(rawAttrs[0].values)) {
    return rawAttrs
  }
  const map = new Map<string, string[]>()
  ;(goods.value?.attributeValues ?? []).forEach((av) => {
    const name = av.attribute?.name || '参数'
    if (!map.has(name)) map.set(name, [])
    map.get(name)!.push(av.value)
  })
  return [...map.entries()].map(([name, values]) => ({ name, values }))
})

const displayPriceRange = computed(() => {
  if (!goods.value?.skus?.length) {
    const fallback = goods.value?.min_price ?? goods.value?.price ?? 0
    return `¥${formatPrice(fallback)}`
  }
  const prices = goods.value.skus.filter(s => s.stock > 0).map(s => Number(s.price))
  if (!prices.length) return '暂无库存'
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  if (min === max) return `¥${min.toFixed(2)}`
  return `¥${min.toFixed(2)} ~ ¥${max.toFixed(2)}`
})

const sanitizedDetail = computed(() => {
  if (!goods.value?.detail) return ''
  if (import.meta.client) {
    return DOMPurify.sanitize(goods.value.detail)
  }
  return goods.value.detail
})

const defaultAvatar = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36"><circle cx="18" cy="18" r="18" fill="%23e5e7eb"/></svg>'

// ---- methods ----
function onImgError(e: Event) {
  const img = e.target as HTMLImageElement
  img.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect fill="%23f3f4f6" width="200" height="200"/><text x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="14">暂无图片</text></svg>'
}

function onAvatarError(e: Event) {
  const img = e.target as HTMLImageElement
  img.src = defaultAvatar
}

function formatPrice(val: string | number | undefined | null): string {
  const n = Number(val)
  return Number.isFinite(n) ? n.toFixed(2) : '0.00'
}

function skuOriginalPrice(sku: SelectorSkuItem): number {
  return Number(sku.original_price ?? sku.market_price ?? 0)
}

function handleSpecChange(payload: { sku: SelectorSkuItem | null; quantity: number }) {
  currentSku.value = payload.sku
  currentQty.value = payload.quantity
}

function handleSpecConfirm(payload: { sku: SelectorSkuItem; quantity: number }) {
  currentSku.value = payload.sku
  currentQty.value = payload.quantity
}

function gotoLogin() {
  router.push(`/login?redirect=${encodeURIComponent(route.fullPath)}`)
}

async function handleAddToCart() {
  if (!userStore.isLoggedIn) {
    message.warning('请先登录')
    gotoLogin()
    return
  }
  const sku = getActiveSku()
  if (!sku) {
    message.warning('请先选择规格')
    return
  }
  try {
    const res = await cartApi.addToCart({ sku_id: sku.id, quantity: currentQty.value })
    if (res.code === 200) {
      message.success('已加入购物车')
    }
  } catch {
    // error handled by request layer
  }
}

async function handleBuyNow() {
  if (!userStore.isLoggedIn) {
    message.warning('请先登录')
    gotoLogin()
    return
  }
  const sku = getActiveSku()
  if (!sku) {
    message.warning('请先选择规格')
    return
  }
  router.push(`/checkout?goods_id=${goods.value?.id}&sku_id=${sku.id}&quantity=${currentQty.value}`)
}

function getActiveSku(): SelectorSkuItem | null {
  if (currentSku.value) return currentSku.value
  if (skuItems.value.length === 1) return skuItems.value[0]
  return null
}

async function handleToggleFavorite() {
  if (!userStore.isLoggedIn) {
    message.warning('请先登录')
    gotoLogin()
    return
  }
  if (!goods.value) return
  try {
    const res = await memberApi.toggleFavorite({ spu_id: goods.value.spu_id || goods.value.id })
    if (res.code === 200) {
      isFavorite.value = res.data.favorited
      message.success(isFavorite.value ? '已收藏' : '已取消收藏')
    }
  } catch {
    // ignore
  }
}

async function loadMoreReviews() {
  if (!goods.value) return
  reviewsLoading.value = true
  reviewPage.value++
  try {
    const res = await goodsApi.getGoodsReviews(goods.value.spu_id || goods.value.id, {
      page_no: reviewPage.value,
      page_size: 10,
    })
    if (res.code === 200) {
      reviews.value = [...reviews.value, ...res.data.list]
      reviewTotal.value = res.data.pagination.total
    }
  } finally {
    reviewsLoading.value = false
  }
}

// ---- init ----
async function fetchGoods() {
  loading.value = true
  try {
    const res = await goodsApi.getGoodsDetail(id.value)
    if (res.code === 200) {
      goods.value = res.data
      activeImage.value = res.data.cover || (res.data.images?.[0] ?? '')
    }
  } finally {
    loading.value = false
  }
}

async function fetchReviews() {
  if (!goods.value) return
  reviewsLoading.value = true
  try {
    const res = await goodsApi.getGoodsReviews(goods.value.spu_id || goods.value.id, {
      page_no: 1,
      page_size: 10,
    })
    if (res.code === 200) {
      reviews.value = res.data.list
      reviewTotal.value = res.data.pagination.total
    }
  } finally {
    reviewsLoading.value = false
  }
}

async function fetchFavoriteStatus() {
  if (!userStore.isLoggedIn || !goods.value) return
  try {
    const res = await memberApi.checkFavorite(goods.value.spu_id || goods.value.id)
    if (res.code === 200) isFavorite.value = res.data.favorited
  } catch {
    // ignore
  }
}

// Watch active image: when selected sku has image, switch to it
watch(currentSku, (sku) => {
  if (sku?.image) activeImage.value = sku.image
})

onMounted(async () => {
  await fetchGoods()
  fetchReviews()
  fetchFavoriteStatus()
})
</script>

<style scoped>
/* ===== 加入购物车 / 立即购买 按钮（淘宝/京东风格）===== */
.btn-cart,
.btn-buy {
  height: 44px;
  padding: 0 16px;
  font-size: 15px;
  font-weight: 600;
  border: 0;
  border-radius: 2px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  transition: filter 0.15s, opacity 0.15s;
}
.btn-cart {
  background: linear-gradient(180deg, #ffb84d 0%, #ff9500 100%);
  color: #fff;
}
.btn-buy {
  background: linear-gradient(180deg, #ff5252 0%, #e53935 100%);
  color: #fff;
}
.btn-cart:hover:not(:disabled),
.btn-buy:hover:not(:disabled) {
  filter: brightness(1.08);
}
.btn-cart:disabled,
.btn-buy:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.goods-description {
  font-size: 14px;
  line-height: 1.8;
  color: #333;
}
.goods-description :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 4px;
  margin: 8px 0;
}
.goods-description :deep(p) {
  margin-bottom: 12px;
}
.goods-description :deep(h1),
.goods-description :deep(h2),
.goods-description :deep(h3) {
  font-weight: 600;
  margin-bottom: 10px;
}

.goods-tabs :deep(.n-tabs-tab--active) {
  color: var(--color-primary);
}
.goods-tabs :deep(.n-tabs-bar) {
  background-color: var(--color-primary);
}
</style>
