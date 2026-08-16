<template>
  <div class="bg-gray-50 min-h-screen pb-16">
    <div class="mx-auto max-w-1200px px-4 py-6">

      <!-- Page title -->
      <div class="flex items-center gap-3 mb-6">
        <button class="text-gray-400 hover:text-gray-600" @click="router.back()">
          <span class="i-carbon-arrow-left text-xl" />
        </button>
        <h1 class="text-xl font-bold text-gray-800">确认订单</h1>
      </div>

      <!-- Loading skeleton -->
      <div v-if="initialLoading" class="space-y-4">
        <div class="bg-white rounded-sm p-5 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-24 mb-4" />
          <div class="h-20 bg-gray-100 rounded" />
        </div>
        <div class="bg-white rounded-sm p-5 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-24 mb-4" />
          <div class="h-32 bg-gray-100 rounded" />
        </div>
      </div>

      <template v-else>
        <!-- ===== Delivery Type Section ===== -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <h2 class="text-base font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <span class="i-carbon-delivery text-[var(--color-primary)]" />
            配送方式
          </h2>
          <DeliveryTypeSelector v-model="deliveryType" :available="availableDeliveryTypes" />
          <!-- 同城配送试算错误（超出配送范围等） -->
          <p v-if="deliveryError" class="mt-3 text-sm text-red-500 flex items-center gap-1.5">
            <span class="i-carbon-warning-alt flex-shrink-0" />
            {{ deliveryError }}
          </p>
          <!-- 同城配送地址缺少经纬度提示 -->
          <p
            v-else-if="deliveryType === 'merchant' && selectedAddress && !addressHasLocation"
            class="mt-3 text-sm text-amber-600 flex items-center gap-1.5"
          >
            <span class="i-carbon-warning-alt flex-shrink-0" />
            当前地址缺少定位信息，同城配送需要地图定位，请在地址管理中重新编辑该地址
          </p>
        </div>

        <!-- ===== Address Section (express / merchant) ===== -->
        <div v-if="deliveryType !== 'pickup'" class="bg-white rounded-sm p-5 mb-4">
          <h2 class="text-base font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <span class="i-carbon-location text-[var(--color-primary)]" />
            收货地址
          </h2>
          <AddressSelector @select="onAddressSelect" />
        </div>

        <!-- ===== Pickup Store Section ===== -->
        <div v-else class="bg-white rounded-sm p-5 mb-4">
          <h2 class="text-base font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <span class="i-carbon-store text-[var(--color-primary)]" />
            自提门店
          </h2>

          <!-- 已选门店卡片 -->
          <div
            v-if="pickupStore"
            class="pickup-store-card"
            @click="storeDialogVisible = true"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-medium text-sm text-gray-800">{{ pickupStore.name }}</span>
                  <span v-if="pickupStore.phone" class="text-sm text-gray-500">{{ pickupStore.phone }}</span>
                </div>
                <p class="text-sm text-gray-500 leading-relaxed">{{ pickupStore.address }}</p>
              </div>
              <span class="text-xs text-[var(--color-primary)] flex-shrink-0 mt-0.5">更换门店</span>
            </div>
          </div>

          <!-- 未选门店占位 -->
          <button
            v-else
            class="w-full py-6 border border-dashed border-gray-300 rounded text-sm text-gray-500 hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] transition-colors flex items-center justify-center gap-1.5"
            @click="storeDialogVisible = true"
          >
            <span class="i-carbon-store text-base" />
            请选择自提门店
          </button>
        </div>

        <!-- ===== Order Items ===== -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <h2 class="text-base font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <span class="i-carbon-shopping-bag text-[var(--color-primary)]" />
            商品清单
          </h2>

          <div v-if="orderItems.length === 0" class="text-center py-10 text-gray-400 text-sm">
            暂无商品信息
          </div>

          <table v-else class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="text-left text-gray-500 font-normal pb-3 w-1/2">商品</th>
                <th class="text-center text-gray-500 font-normal pb-3">单价</th>
                <th class="text-center text-gray-500 font-normal pb-3">数量</th>
                <th class="text-right text-gray-500 font-normal pb-3">小计</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in orderItems"
                :key="item.sku_id"
                class="border-b border-gray-50 last:border-0"
              >
                <!-- Product info -->
                <td class="py-3 pr-4">
                  <div class="flex items-center gap-3">
                    <img
                      :src="item.cover || '/placeholder.png'"
                      :alt="item.name"
                      class="w-16 h-16 object-cover rounded flex-shrink-0 border border-gray-100"
                      @error="(e) => (e.target as HTMLImageElement).src = '/placeholder.png'"
                    />
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-gray-800 leading-snug line-clamp-2">{{ item.name }}</p>
                      <p v-if="item.sku_name" class="mt-1 text-gray-400 text-xs">规格：{{ item.sku_name }}</p>
                    </div>
                  </div>
                </td>
                <!-- Price -->
                <td class="py-3 text-center text-gray-700">
                  ¥{{ item.price.toFixed(2) }}
                </td>
                <!-- Quantity -->
                <td class="py-3 text-center text-gray-700">
                  × {{ item.quantity }}
                </td>
                <!-- Subtotal -->
                <td class="py-3 text-right font-medium text-gray-800">
                  ¥{{ (item.price * item.quantity).toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ===== Buyer Remark ===== -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <h2 class="text-base font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <span class="i-carbon-chat text-[var(--color-primary)]" />
            买家留言
          </h2>
          <NInput
            v-model:value="buyerRemark"
            type="textarea"
            placeholder="选填，请填写您对此次交易的要求（限200字）"
            :maxlength="200"
            :rows="3"
            show-count
          />
        </div>

        <!-- ===== Coupon ===== -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <h2 class="text-base font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <span class="i-carbon-ticket text-[var(--color-primary)]" />
            优惠券
          </h2>
          <NSelect
            v-model:value="selectedCouponId"
            :options="couponOptions"
            placeholder="不使用优惠券"
            clearable
            :loading="couponLoading"
          />
        </div>

        <!-- ===== Amount Summary + Submit ===== -->
        <div class="bg-white rounded-sm p-5">
          <div class="flex justify-end">
            <div class="w-72 space-y-2 text-sm">
              <div class="flex justify-between text-gray-600">
                <span>商品金额</span>
                <span>¥{{ calcResult.goods_amount }}</span>
              </div>
              <div class="flex justify-between text-gray-600">
                <span>运费</span>
                <span :class="Number(calcResult.freight_amount) === 0 ? 'text-green-500' : ''">
                  {{ Number(calcResult.freight_amount) === 0 ? '免运费' : `¥${calcResult.freight_amount}` }}
                </span>
              </div>
              <div v-if="Number(calcResult.discount_amount) > 0" class="flex justify-between text-red-500">
                <span>优惠</span>
                <span>-¥{{ calcResult.discount_amount }}</span>
              </div>
              <div class="flex justify-between font-bold text-base text-gray-800 pt-2 border-t border-gray-100">
                <span>合计</span>
                <span class="text-red-500">¥{{ calcResult.pay_amount }}</span>
              </div>
            </div>
          </div>

          <div class="flex justify-end items-center gap-4 mt-5 pt-4 border-t border-gray-100">
            <div class="text-sm text-gray-500">
              共 {{ totalQuantity }} 件商品，实付：
              <span class="text-red-500 text-xl font-bold ml-1">¥{{ calcResult.pay_amount }}</span>
            </div>
            <NButton
              type="primary"
              size="large"
              :loading="submitting"
              :disabled="submitDisabled"
              class="min-w-36"
              @click="handleSubmitOrder"
            >
              提交订单
            </NButton>
          </div>
        </div>
      </template>

    </div>

    <!-- Pickup store dialog -->
    <PickupStoreDialog
      v-model:show="storeDialogVisible"
      :selected-id="pickupStore?.id"
      @select="onStoreSelect"
    />
  </div>
</template>

<script setup lang="ts">
import { NButton, NInput, NSelect, useMessage, type SelectOption } from 'naive-ui'
import AddressSelector from '~/components/AddressSelector.vue'
import DeliveryTypeSelector from '~/components/DeliveryTypeSelector.vue'
import PickupStoreDialog from '~/components/PickupStoreDialog.vue'
import { orderApi, type CreateOrderData, type OrderCalcResult, type DeliveryType } from '~/api/order'
import { cartApi, type CartItem } from '~/api/cart'
import { type AddressItem } from '~/api/member'
import { goodsApi } from '~/api/goods'
import { marketingApi, type AvailableCouponItem } from '~/api/marketing'
import type { Store } from '~/api/store'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const message = useMessage()

const initialLoading = ref(true)
const submitting = ref(false)
const couponLoading = ref(false)
const selectedAddress = ref<AddressItem | null>(null)
const buyerRemark = ref('')
const availableCoupons = ref<AvailableCouponItem[]>([])
const selectedCouponId = ref<number | null>(null)

interface CheckoutItem {
  sku_id: number
  spu_id: number
  name: string
  cover: string
  sku_name: string
  price: number
  quantity: number
  /** SPU 级支持的配送方式（后端 v2.4.0 起返回，如 ['express','pickup']） */
  delivery_modes?: string[]
  flash_item_id?: number
  group_activity_id?: number
  group_id?: number
}

const orderItems = ref<CheckoutItem[]>([])
const calcResult = ref<OrderCalcResult>({
  goods_amount: '0.00',
  freight_amount: '0.00',
  discount_amount: '0.00',
  pay_amount: '0.00',
})

// ---- 配送方式 ----
const deliveryType = ref<DeliveryType>('express')
const pickupStore = ref<Store | null>(null)
const storeDialogVisible = ref(false)
// 同城配送试算时的业务错误（超出配送范围 / 缺经纬度），refreshCalc 写入，提交时再校验
const deliveryError = ref('')

// 取所有商品 delivery_modes 交集；空 items 或字段缺失时降级为 ['express', 'pickup']（与 uniapp 一致）
const availableDeliveryTypes = computed<DeliveryType[]>(() => {
  if (!orderItems.value.length) return ['express', 'pickup']
  const sets = orderItems.value.map(i => new Set(i.delivery_modes && i.delivery_modes.length > 0 ? i.delivery_modes : ['express', 'pickup']))
  const intersection = [...sets[0]].filter(m => sets.every(s => s.has(m)))
  const allowed = intersection.filter(m => m === 'express' || m === 'merchant' || m === 'pickup') as DeliveryType[]
  return allowed.length ? allowed : ['express']
})

// 当前配送方式不在可用列表时，自动切到第一个可用项
watch(availableDeliveryTypes, (types) => {
  if (!types.includes(deliveryType.value)) {
    deliveryType.value = types[0]
  }
}, { immediate: true })

const addressHasLocation = computed(() => {
  const addr = selectedAddress.value
  return !!(addr && addr.lng && addr.lat)
})

const submitDisabled = computed(() => {
  if (!orderItems.value.length) return true
  if (deliveryType.value === 'pickup') return !pickupStore.value
  return !selectedAddress.value
})

const goodsAmount = computed(() =>
  orderItems.value.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0)
)
const totalQuantity = computed(() =>
  orderItems.value.reduce((sum, item) => sum + item.quantity, 0)
)
const couponOptions = computed<SelectOption[]>(() => availableCoupons.value.map(item => ({
  label: `${item.coupon.name}（优惠 ¥${Number(item.discount).toFixed(2)}）`,
  value: item.id,
})))

function orderPayloadItems(): CreateOrderData['items'] {
  return orderItems.value.map(item => ({
    sku_id: item.sku_id,
    quantity: item.quantity,
    flash_item_id: item.flash_item_id,
    group_activity_id: item.group_activity_id,
    group_id: item.group_id,
  }))
}

async function refreshCalc() {
  if (!orderItems.value.length) return
  const addr = selectedAddress.value
  const res = await orderApi.calc({
    skus: orderPayloadItems(),
    coupon_user_id: selectedCouponId.value || undefined,
    delivery_type: deliveryType.value,
    region_code: addr?.region_code,
    province: addr?.province,
    // 同城配送：把用户地址坐标传给后端，用于运费计算 + 配送距离校验
    ...(deliveryType.value === 'merchant' && addr
      ? { lng: addr.lng, lat: addr.lat }
      : {}),
    ...(deliveryType.value === 'pickup' && pickupStore.value
      ? { pickup_store_id: pickupStore.value.id }
      : {}),
  }, false)
  if (res.code === 200) {
    calcResult.value = res.data
    deliveryError.value = ''
  } else {
    // 业务错误（超出配送范围 / 缺经纬度等）展示在配送方式区域；金额降级为本地手算
    deliveryError.value = res.message || '运费试算失败'
    const sum = goodsAmount.value
    calcResult.value = {
      goods_amount: sum.toFixed(2),
      freight_amount: '0.00',
      discount_amount: '0.00',
      pay_amount: sum.toFixed(2),
    }
  }
}

async function loadAvailableCoupons() {
  if (!orderItems.value.length) return
  couponLoading.value = true
  try {
    const res = await marketingApi.getAvailableCoupons({
      order_amount: goodsAmount.value,
      spu_ids: orderItems.value.map(item => item.spu_id).join(','),
    })
    if (res.code === 200) {
      availableCoupons.value = res.data
      if (selectedCouponId.value && !res.data.some(item => item.id === selectedCouponId.value)) {
        selectedCouponId.value = null
      }
    }
  } finally {
    couponLoading.value = false
  }
}

async function loadItems() {
  initialLoading.value = true
  try {
    const skuId = Number(route.query.sku_id) || 0
    const goodsId = Number(route.query.goods_id) || 0
    const qty = Math.max(1, Number(route.query.quantity) || 1)
    const flashItemId = Number(route.query.flash_item_id) || 0
    const groupActivityId = Number(route.query.group_activity_id) || 0
    const groupId = Number(route.query.group_id) || 0

    if (skuId) {
      if (!goodsId) throw new Error('缺少商品参数')
      const res = await goodsApi.getGoodsDetail(goodsId)
      if (res.code !== 200) return
      const goods = res.data
      const sku = goods.skus.find(item => item.id === skuId)
      if (!sku) throw new Error('商品规格不存在')

      let displayPrice = Number(sku.price)
      if (flashItemId) {
        const sale = await marketingApi.getFlashSaleByGoods(goodsId)
        if (sale.code !== 200 || !sale.data || sale.data.matched_item.id !== flashItemId || sale.data.matched_item.sku_id !== skuId) {
          throw new Error('秒杀活动已结束或商品不匹配')
        }
        displayPrice = Number(sale.data.matched_item.flash_price)
      } else if (groupActivityId) {
        const activity = await marketingApi.getGroupBuyDetail(groupActivityId)
        if (activity.code !== 200 || activity.data.spu_id !== goodsId || activity.data.sku_id !== skuId) {
          throw new Error('拼团活动已结束或商品不匹配')
        }
        displayPrice = Number(activity.data.group_price)
      }

      orderItems.value = [{
        sku_id: sku.id,
        spu_id: goods.id,
        name: goods.name,
        cover: sku.image || goods.images?.[0] || goods.cover,
        sku_name: sku.spec_text || sku.name || '',
        price: displayPrice,
        quantity: qty,
        // delivery_modes 是 SPU 级字段（不在 SKU 上），不映射会导致 buy-now 流程同城/自提选项不显示
        delivery_modes: goods.delivery_modes,
        flash_item_id: flashItemId || undefined,
        group_activity_id: groupActivityId || undefined,
        group_id: groupId || undefined,
      }]
    } else {
      const res = await cartApi.getSelectedItems()
      if (res.code === 200) {
        orderItems.value = res.data.map((item: CartItem) => ({
          sku_id: item.sku_id,
          spu_id: Number(item.sku?.spu?.id || 0),
          name: item.spu_name,
          cover: item.image,
          sku_name: item.spec_text,
          price: Number(item.price),
          quantity: item.quantity,
          delivery_modes: item.delivery_modes,
        }))
      }
    }

    await Promise.all([refreshCalc(), loadAvailableCoupons()])
  } catch (error: any) {
    message.error(error?.message || '获取商品信息失败')
    orderItems.value = []
  } finally {
    initialLoading.value = false
  }
}

function onAddressSelect(addr: AddressItem) {
  selectedAddress.value = addr
}

function onStoreSelect(store: Store) {
  pickupStore.value = store
}

watch(selectedCouponId, () => refreshCalc())

// 配送方式 / 地址 / 自提门店变更时重新试算（同城配送运费与范围校验会随之变化）
watch(
  [deliveryType, () => selectedAddress.value?.id, () => pickupStore.value?.id],
  () => {
    if (orderItems.value.length) refreshCalc()
  },
)

async function handleSubmitOrder() {
  const addr = selectedAddress.value
  if (!orderItems.value.length) {
    message.warning('商品信息不完整')
    return
  }
  if (deliveryType.value === 'pickup') {
    if (!pickupStore.value) {
      message.warning('请选择自提门店')
      return
    }
  } else if (!addr) {
    message.warning('请选择收货地址')
    return
  }
  // 同城配送：地址必须有经纬度（地图定位）
  if (deliveryType.value === 'merchant' && !addressHasLocation.value) {
    message.warning('当前地址缺少定位信息，同城配送需要地图定位，请在地址管理中重新编辑该地址')
    return
  }
  // 试算阶段的业务错误（超出配送范围等）未解除时不允许提交
  if (deliveryError.value) {
    message.warning(deliveryError.value)
    return
  }

  submitting.value = true
  try {
    const res = await orderApi.createOrder({
      items: orderPayloadItems(),
      // 到店自提无需收货地址
      address: deliveryType.value !== 'pickup' && addr ? {
        name: addr.name,
        phone: addr.phone,
        province: addr.province,
        city: addr.city,
        district: addr.district,
        detail: addr.detail,
        region_code: addr.region_code,
        lng: addr.lng,
        lat: addr.lat,
      } : undefined,
      buyer_remark: buyerRemark.value || undefined,
      coupon_user_id: selectedCouponId.value || undefined,
      delivery_type: deliveryType.value,
      pickup_store_id: deliveryType.value === 'pickup' ? pickupStore.value!.id : undefined,
    })
    if (res.code === 200) {
      message.success('订单创建成功')
      router.push(`/pay/${res.data.order_no}`)
    }
  } finally {
    submitting.value = false
  }
}

onMounted(loadItems)
</script>

<style scoped>
.pickup-store-card {
  padding: 12px 14px;
  border: 2px solid var(--color-primary);
  border-radius: 6px;
  cursor: pointer;
  background-color: rgb(from var(--color-primary) r g b / 0.04);
  transition: all 0.2s;
}
</style>
