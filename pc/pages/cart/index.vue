<template>
  <div class="bg-gray-50 min-h-screen pb-12">
    <div class="mx-auto max-w-1200px px-4 pt-5">

      <h2 class="text-xl font-bold text-gray-900 mb-6">我的购物车</h2>

      <!-- Loading skeleton -->
      <div v-if="loading" class="bg-white rounded-sm p-8 space-y-3">
        <div v-for="i in 3" :key="i" class="h-24 bg-gray-100 rounded animate-pulse" />
      </div>

      <!-- Empty -->
      <div
        v-else-if="!cartList.length"
        class="bg-white rounded-sm py-20 text-center"
      >
        <span class="i-carbon-shopping-cart text-5xl text-gray-300 block mx-auto mb-3" />
        <p class="text-gray-500 mb-4">购物车空空如也</p>
        <NuxtLink
          to="/goods"
          class="inline-block px-6 py-2 bg-[var(--color-primary)] text-white rounded-sm text-sm hover:opacity-90 transition-opacity"
        >
          去逛逛
        </NuxtLink>
      </div>

      <!-- Two-column layout: list (left) + summary (right) -->
      <div v-else class="flex gap-4">

        <!-- ===== Left: cart list ===== -->
        <div class="flex-1 min-w-0">
          <div class="bg-white rounded-sm overflow-hidden">
            <!-- Table header -->
            <div class="cart-row cart-row--header">
              <div class="cart-cell cart-cell--check">
                <label class="checkbox-wrap">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    :indeterminate.prop="someSelected && !allSelected"
                    @change="handleToggleAll"
                  />
                </label>
                全选
              </div>
              <div class="cart-cell cart-cell--goods">商品信息</div>
              <div class="cart-cell cart-cell--price">单价</div>
              <div class="cart-cell cart-cell--qty">数量</div>
              <div class="cart-cell cart-cell--subtotal">小计</div>
              <div class="cart-cell cart-cell--op">操作</div>
            </div>

            <!-- Items -->
            <div
              v-for="item in cartList"
              :key="item.id"
              class="cart-row"
              :class="{ 'cart-row--unavailable': !isAvailable(item) }"
            >
              <div class="cart-cell cart-cell--check">
                <label class="checkbox-wrap" :class="{ 'cursor-not-allowed': !isAvailable(item) }">
                  <input
                    type="checkbox"
                    :checked="item.selected === 1"
                    :disabled="!isAvailable(item)"
                    @change="handleToggleItem(item)"
                  />
                </label>
              </div>

              <div class="cart-cell cart-cell--goods">
                <div class="flex items-start gap-3 min-w-0">
                  <NuxtLink
                    :to="goodsPath(item)"
                    class="block w-20 h-20 flex-shrink-0 rounded overflow-hidden bg-gray-100"
                  >
                    <img
                      :src="item.image || defaultImg"
                      :alt="item.spu_name"
                      class="w-full h-full object-cover"
                      @error="onImgError"
                    />
                  </NuxtLink>
                  <div class="min-w-0 flex-1">
                    <p
                      class="text-sm text-gray-700 line-clamp-2 cursor-pointer hover:text-[var(--color-primary)]"
                      @click="goDetail(item)"
                    >
                      {{ item.spu_name }}
                    </p>
                    <p v-if="item.spec_text" class="text-xs text-gray-400 mt-1">规格：{{ item.spec_text }}</p>
                    <p v-if="!isAvailable(item)" class="text-xs text-red-500 mt-1">{{ unavailableReason(item) }}</p>
                  </div>
                </div>
              </div>

              <div class="cart-cell cart-cell--price">
                <span class="text-gray-700">¥{{ formatPrice(item.price) }}</span>
              </div>

              <div class="cart-cell cart-cell--qty">
                <div class="qty-stepper">
                  <button
                    class="qty-btn"
                    :disabled="item.quantity <= 1 || updating === item.id"
                    @click="updateQuantity(item, item.quantity - 1)"
                  >
                    −
                  </button>
                  <input
                    type="number"
                    class="qty-input"
                    :value="item.quantity"
                    min="1"
                    :max="item.stock || 1"
                    @change="onQtyInput($event, item)"
                  />
                  <button
                    class="qty-btn"
                    :disabled="item.quantity >= (item.stock || 1) || updating === item.id"
                    @click="updateQuantity(item, item.quantity + 1)"
                  >
                    +
                  </button>
                </div>
              </div>

              <div class="cart-cell cart-cell--subtotal">
                <span class="text-[var(--color-primary)] font-semibold">
                  ¥{{ formatPrice(item.price * item.quantity) }}
                </span>
              </div>

              <div class="cart-cell cart-cell--op">
                <button
                  class="text-sm text-gray-500 hover:text-red-500"
                  @click="removeItem(item)"
                >
                  删除
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== Right: summary panel ===== -->
        <aside class="w-72 flex-shrink-0">
          <div class="bg-white rounded-sm p-5 sticky top-20">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">结算明细</h3>

            <div class="space-y-2 text-sm">
              <div class="flex justify-between text-gray-500">
                <span>已选商品</span>
                <span>{{ selectedCount }} 件</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>商品总额</span>
                <span>¥{{ formatPrice(selectedSubtotal) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>预估运费</span>
                <span>¥0.00</span>
              </div>
            </div>

            <div class="border-t border-gray-100 mt-4 pt-4 flex justify-between items-baseline">
              <span class="text-sm text-gray-500">合计</span>
              <span class="text-2xl font-bold text-[var(--color-primary)]">
                ¥{{ formatPrice(selectedSubtotal) }}
              </span>
            </div>

            <button
              class="w-full mt-5 py-2.5 rounded-sm bg-[var(--color-primary)] text-white text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="selectedCount === 0 || submitting"
              @click="handleCheckout"
            >
              {{ selectedCount > 0 ? `去结算（${selectedCount}）` : '请选择商品' }}
            </button>

            <button
              v-if="cartList.length"
              class="w-full mt-2 py-2 rounded-sm border border-gray-200 text-sm text-gray-500 hover:text-red-500 hover:border-red-300"
              @click="removeUnavailable"
            >
              清理失效商品
            </button>
          </div>
        </aside>

      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import { cartApi, type CartItem } from '~/api/cart'

definePageMeta({ middleware: 'auth' })

const router = useRouter()
const message = useMessage()

const defaultImg = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect fill="%23f3f4f6" width="80" height="80"/></svg>'

const cartList = ref<CartItem[]>([])
const loading = ref(true)
const submitting = ref(false)
const updating = ref<number | null>(null)

async function fetchCart() {
  loading.value = true
  try {
    const res = await cartApi.getCartList()
    if (res.code === 200) cartList.value = res.data
  } finally {
    loading.value = false
  }
}

function isAvailable(item: CartItem): boolean {
  return item.sku_status === 1 && item.spu_status === 'on_sale' && item.stock > 0
}

function unavailableReason(item: CartItem): string {
  if (item.spu_status !== 'on_sale') return '商品已下架'
  if (item.sku_status !== 1) return '规格已下架'
  if (item.stock <= 0) return '已售罄'
  return ''
}

function formatPrice(val: number | string | null | undefined): string {
  if (val == null || val === '') return '0.00'
  const n = typeof val === 'number' ? val : Number(val)
  if (!Number.isFinite(n)) return '0.00'
  return n.toFixed(2)
}

function onImgError(e: Event) {
  const img = e.target as HTMLImageElement
  img.src = defaultImg
}

// ---- Selection ----
const availableItems = computed(() => cartList.value.filter(isAvailable))
const allSelected = computed(() =>
  availableItems.value.length > 0
  && availableItems.value.every(i => i.selected === 1),
)
const someSelected = computed(() => cartList.value.some(i => i.selected === 1))

async function handleToggleItem(item: CartItem) {
  const prev = item.selected
  item.selected = item.selected === 1 ? 0 : 1
  try {
    await cartApi.toggleSelectItem(item.id)
  } catch {
    item.selected = prev
    message.error('操作失败')
  }
}

async function handleToggleAll() {
  const target = allSelected.value ? 0 : 1
  const snapshot = cartList.value.map(i => i.selected)
  cartList.value.forEach((i) => {
    if (isAvailable(i)) i.selected = target
  })
  try {
    await cartApi.selectAllItems({ selected: target })
  } catch {
    cartList.value.forEach((i, idx) => { i.selected = snapshot[idx] })
    message.error('操作失败')
  }
}

// ---- Quantity ----
async function updateQuantity(item: CartItem, val: number) {
  const max = item.stock || 1
  if (val < 1 || val > max || val === item.quantity) return
  const prev = item.quantity
  item.quantity = val
  updating.value = item.id
  try {
    await cartApi.updateCartItem(item.id, { quantity: val })
  } catch {
    item.quantity = prev
    message.error('更新失败')
  } finally {
    updating.value = null
  }
}

function onQtyInput(e: Event, item: CartItem) {
  const target = e.target as HTMLInputElement
  const val = Number(target.value)
  if (!Number.isFinite(val) || val < 1) {
    target.value = String(item.quantity)
    return
  }
  const max = item.stock || 1
  const clamped = Math.min(val, max)
  if (clamped !== val) target.value = String(clamped)
  updateQuantity(item, clamped)
}

// ---- Remove ----
async function removeItem(item: CartItem) {
  if (!window.confirm(`确认移除「${item.spu_name}」？`)) return
  const prev = [...cartList.value]
  cartList.value = cartList.value.filter(i => i.id !== item.id)
  try {
    await cartApi.removeCartItem(item.id)
    message.success('已移除')
  } catch {
    cartList.value = prev
    message.error('移除失败')
  }
}

async function removeUnavailable() {
  const dead = cartList.value.filter(i => !isAvailable(i))
  if (dead.length === 0) {
    message.info('暂无失效商品')
    return
  }
  if (!window.confirm(`清理 ${dead.length} 件失效商品？`)) return
  // 串行删除，避免后端并发竞争；规模 ≤ 几十一般不慢
  for (const item of dead) {
    try {
      await cartApi.removeCartItem(item.id)
      cartList.value = cartList.value.filter(i => i.id !== item.id)
    } catch {
      // 单条失败继续下一条
    }
  }
  message.success('清理完成')
}

// ---- Summary ----
const selectedItems = computed(() => cartList.value.filter(i => i.selected === 1 && isAvailable(i)))
const selectedCount = computed(() =>
  selectedItems.value.reduce((sum, i) => sum + i.quantity, 0),
)
const selectedSubtotal = computed(() =>
  selectedItems.value.reduce((sum, i) => sum + Number(i.price) * i.quantity, 0),
)

// ---- Checkout ----
function handleCheckout() {
  if (selectedCount.value === 0) {
    message.warning('请先勾选商品')
    return
  }
  router.push('/checkout')
}

// ---- Navigation ----
function goDetail(item: CartItem) {
  const spuId = item.sku?.spu?.id
  if (spuId) router.push(`/goods/${spuId}`)
}

function goodsPath(item: CartItem) {
  return item.sku?.spu?.id ? `/goods/${item.sku.spu.id}` : '/goods'
}

onMounted(() => {
  fetchCart()
})
</script>

<style scoped>
.cart-row {
  display: grid;
  grid-template-columns: 60px 1fr 100px 130px 100px 80px;
  align-items: center;
  gap: 8px;
  padding: 16px 20px;
  border-bottom: 1px solid #f0f0f0;
}
.cart-row--header {
  background: #fafafa;
  padding-top: 12px;
  padding-bottom: 12px;
  font-size: 0.8125rem;
  color: #6b7280;
}
.cart-row--unavailable {
  opacity: 0.6;
}
.cart-row:last-child {
  border-bottom: 0;
}

.cart-cell {
  font-size: 0.875rem;
}
.cart-cell--check {
  display: flex;
  align-items: center;
  gap: 6px;
}
.cart-cell--price,
.cart-cell--subtotal,
.cart-cell--qty,
.cart-cell--op {
  text-align: center;
}

.checkbox-wrap {
  display: inline-flex;
  align-items: center;
  cursor: pointer;
}
.checkbox-wrap input {
  width: 16px;
  height: 16px;
  accent-color: var(--color-primary);
  cursor: pointer;
}

.qty-stepper {
  display: inline-flex;
  align-items: stretch;
  border: 1px solid #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
  background: #fff;
}
.qty-btn {
  width: 28px;
  height: 28px;
  background: #fafafa;
  color: #6b7280;
  font-size: 1rem;
  line-height: 1;
  cursor: pointer;
  border: 0;
  transition: background-color 0.15s;
}
.qty-btn:hover:not(:disabled) {
  background: #f3f4f6;
}
.qty-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
.qty-input {
  width: 48px;
  height: 28px;
  border: 0;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  text-align: center;
  font-size: 0.8125rem;
  outline: none;
  -moz-appearance: textfield;
}
.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
