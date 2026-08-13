<template>
  <view class="checkout-page">
    <!-- pages.json 已配置原生导航栏标题"确认订单"，不再写自定义 nav -->

    <scroll-view scroll-y class="checkout-scroll">

      <!-- ===== 配送方式 chip（多于 1 种可用时显示）===== -->
      <view v-if="availableDeliveryTypes.length > 0" class="checkout-section">
        <d-delivery-chip v-model="deliveryType" :available="availableDeliveryTypes" />
        <text v-if="deliveryError" class="delivery-error">{{ deliveryError }}</text>
      </view>

      <!-- ===== Address Section（pickup 时隐藏）===== -->
      <view v-if="deliveryType !== 'pickup'" class="checkout-section" @tap="selectAddress">
        <view v-if="selectedAddress" class="address-card">
          <view class="address-card__icon">
            <d-icon name="location" size="40rpx" color="#2979ff" />
          </view>
          <view class="address-card__info">
            <view class="address-card__name-row">
              <text class="address-card__name">{{ selectedAddress.name }}</text>
              <text class="address-card__mobile">{{ selectedAddress.phone }}</text>
              <view v-if="selectedAddress.is_default === 1" class="address-card__default-tag">默认</view>
            </view>
            <text class="address-card__detail">
              {{ selectedAddress.province }} {{ selectedAddress.city }} {{ selectedAddress.district }} {{ selectedAddress.detail }}
            </text>
          </view>
          <d-icon name="arrow-right" size="32rpx" color="#a1a1aa" />
        </view>
        <view v-else class="address-empty">
          <d-icon name="location" size="40rpx" color="#a1a1aa" />
          <text class="address-empty__text">请选择收货地址</text>
          <d-icon name="arrow-right" size="32rpx" color="#a1a1aa" />
        </view>
      </view>

      <!-- ===== 自提门店 cell（pickup 时显示）===== -->
      <view v-else class="checkout-section" @tap="onPickPickupStore">
        <view v-if="pickupStore" class="address-card">
          <view class="address-card__icon">
            <d-icon name="store" size="40rpx" color="#2979ff" />
          </view>
          <view class="address-card__info">
            <view class="address-card__name-row">
              <text class="address-card__name">{{ pickupStore.name }}</text>
            </view>
            <text class="address-card__detail">{{ pickupStore.address }}</text>
          </view>
          <d-icon name="arrow-right" size="32rpx" color="#a1a1aa" />
        </view>
        <view v-else class="address-empty">
          <d-icon name="store" size="40rpx" color="#a1a1aa" />
          <text class="address-empty__text">请选择自提门店</text>
          <d-icon name="arrow-right" size="32rpx" color="#a1a1aa" />
        </view>
      </view>

      <!-- ===== Order Items ===== -->
      <view class="checkout-section checkout-section--items">
        <view class="section-title">
          <text>商品清单</text>
        </view>

        <view v-if="items.length === 0" class="items-empty">
          <text>暂无商品信息</text>
        </view>

        <view v-else class="items-list">
          <view
            v-for="item in items"
            :key="item.sku_id"
            class="item-row"
          >
            <image
              :src="item.cover || '/static/placeholder.png'"
              mode="aspectFill"
              class="item-cover"
            />
            <view class="item-info">
              <text class="item-name">{{ item.name }}</text>
              <text v-if="item.sku_name" class="item-sku">{{ item.sku_name }}</text>
              <view class="item-price-row">
                <d-price :price="item.price" size="sm" />
                <text class="item-qty">× {{ item.quantity }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- ===== Coupon ===== -->
      <view class="checkout-section coupon-cell" @tap="couponPopupVisible = true">
        <text class="coupon-cell__label">优惠券</text>
        <view class="coupon-cell__right">
          <text v-if="selectedCoupon" class="coupon-cell__discount">-¥{{ selectedCoupon.discount.toFixed(2) }}</text>
          <text v-else class="coupon-cell__hint">{{ availableCoupons.length ? `${availableCoupons.length} 张可用` : '暂无可用' }}</text>
          <d-icon name="arrow-right" size="30rpx" color="#a1a1aa" />
        </view>
      </view>

      <!-- ===== Buyer Remark ===== -->
      <view class="checkout-section">
        <view class="section-title">
          <text>买家留言</text>
        </view>
        <textarea
          v-model="buyerRemark"
          class="remark-textarea"
          placeholder="选填，请填写您对此次交易的要求（限200字）"
          :maxlength="200"
          :show-confirm-bar="false"
          auto-height
        />
      </view>

      <!-- ===== 发票申请 ===== -->
      <view class="checkout-section invoice-section">
        <view class="invoice-section__header">
          <text class="invoice-section__title">申请发票</text>
          <u-switch v-model="invoiceEnabled" :activeValue="true" :inactiveValue="false" size="20" />
        </view>

        <view v-if="invoiceEnabled" class="invoice-form">
          <view class="invoice-tabs">
            <view
              :class="['invoice-tab', { 'invoice-tab--active': invoiceForm.type === 'personal' }]"
              @tap="invoiceForm.type = 'personal'"
            >个人</view>
            <view
              :class="['invoice-tab', { 'invoice-tab--active': invoiceForm.type === 'company' }]"
              @tap="invoiceForm.type = 'company'"
            >单位</view>
          </view>

          <view class="invoice-field">
            <text class="invoice-field__label">抬头</text>
            <input
              v-model="invoiceForm.title"
              class="invoice-field__input"
              placeholder="请输入发票抬头"
              placeholder-class="invoice-field__placeholder"
            />
          </view>
          <view v-if="invoiceForm.type === 'company'" class="invoice-field">
            <text class="invoice-field__label">税号</text>
            <input
              v-model="invoiceForm.tax_no"
              class="invoice-field__input"
              placeholder="请输入纳税人识别号"
              placeholder-class="invoice-field__placeholder"
            />
          </view>
          <view class="invoice-field">
            <text class="invoice-field__label">邮箱</text>
            <input
              v-model="invoiceForm.recipient_email"
              class="invoice-field__input"
              type="text"
              placeholder="接收电子发票邮箱"
              placeholder-class="invoice-field__placeholder"
            />
          </view>
        </view>
      </view>

      <!-- ===== Amount Summary ===== -->
      <view class="checkout-section checkout-section--summary">
        <view class="summary-row">
          <text class="summary-label">商品金额</text>
          <text class="summary-value">¥{{ calcResult.goods_amount }}</text>
        </view>
        <view class="summary-row">
          <text class="summary-label">运费</text>
          <text v-if="Number(calcResult.freight_amount) > 0" class="summary-value">¥{{ calcResult.freight_amount }}</text>
          <text v-else class="summary-value summary-value--free">免运费</text>
        </view>
        <view v-if="Number(calcResult.discount_amount) > 0" class="summary-row">
          <text class="summary-label">满减</text>
          <text class="summary-value summary-value--discount">-¥{{ calcResult.discount_amount }}</text>
        </view>
        <view class="summary-divider" />
        <view class="summary-row">
          <text class="summary-label summary-label--bold">合计</text>
          <text class="summary-value summary-value--total">¥{{ calcResult.pay_amount }}</text>
        </view>
      </view>

      <!-- Bottom spacer -->
      <view style="height: 160rpx" />
    </scroll-view>

    <!-- ===== Bottom bar (inline 实现) ===== -->
    <view class="checkout-bottom">
      <view class="checkout-bottom__total-wrap">
        <text class="checkout-bottom__count">共 {{ totalQuantity }} 件</text>
        <view class="checkout-bottom__total-row">
          <text class="checkout-bottom__total-label">实付：</text>
          <text class="checkout-bottom__total">¥{{ calcResult.pay_amount }}</text>
        </view>
      </view>
      <view
        class="checkout-bottom__btn"
        :class="{ 'checkout-bottom__btn--disabled': (deliveryType !== 'pickup' && !selectedAddress) || (deliveryType === 'pickup' && !pickupStore) || items.length === 0 || submitting }"
        @tap="handleSubmit"
      >
        {{ submitting ? '提交中...' : '提交订单' }}
      </view>
    </view>

    <!-- Payment popup -->
    <d-payment-popup
      v-model="payPopupVisible"
      :amount="Number(calcResult.pay_amount)"
      :loading="paying"
      @pay="handlePay"
    />

    <!-- 地址选择 popup -->
    <d-address-picker
      v-model:visible="addrPickerVisible"
      :selected-id="selectedAddress?.id ?? null"
      @select="onAddrSelected"
    />

    <u-popup :show="couponPopupVisible" mode="bottom" round="24rpx" safeAreaInsetBottom @close="couponPopupVisible = false">
      <view class="coupon-popup">
        <view class="coupon-popup__header">
          <text class="coupon-popup__title">选择优惠券</text>
          <d-icon name="close" size="34rpx" color="#71717a" @tap="couponPopupVisible = false" />
        </view>
        <scroll-view scroll-y class="coupon-popup__list">
          <view class="coupon-option" :class="{ 'is-active': !selectedCoupon }" @tap="selectCoupon(null)">
            <view>
              <text class="coupon-option__name">不使用优惠券</text>
              <text class="coupon-option__desc">按其他活动优惠结算</text>
            </view>
            <text class="coupon-option__check">{{ !selectedCoupon ? '✓' : '' }}</text>
          </view>
          <view
            v-for="item in availableCoupons"
            :key="item.id"
            class="coupon-option"
            :class="{ 'is-active': selectedCoupon?.id === item.id }"
            @tap="selectCoupon(item)"
          >
            <view>
              <text class="coupon-option__name">{{ item.coupon.name }}</text>
              <text class="coupon-option__desc">满 ¥{{ Number(item.coupon.min_amount).toFixed(2) }} 可用 · 优惠 ¥{{ item.discount.toFixed(2) }}</text>
            </view>
            <text class="coupon-option__check">{{ selectedCoupon?.id === item.id ? '✓' : '' }}</text>
          </view>
          <view v-if="!availableCoupons.length" class="coupon-popup__empty">当前商品暂无可用优惠券</view>
        </scroll-view>
      </view>
    </u-popup>

  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { orderApi, invoiceApi, type CreateOrderData, type InvoicePayload } from '@/api/order'
import DAddressPicker from '@/components/d-address-picker/d-address-picker.vue'
import { memberApi, type AddressItem } from '@/api/member'
import { cartApi, type CartItem } from '@/api/cart'
import { goodsApi } from '@/api/goods'
import { usePayment } from '@/composables/usePayment'
import { useAppStore } from '@/store/app.store'
import { useUserStore } from '@/store/user.store'
import { getToken } from '@/utils/auth'
import { redirectToLogin } from '@/utils/login-redirect'
import type { PayChannel } from '@/api/payment'
import type { Store } from '@/api/store'
import { marketingApi, type AvailableCouponItem } from '@/api/marketing'

const appStore = useAppStore()
const userStore = useUserStore()
const { pay, loading: payLoading } = usePayment()

// 微信端自动注册的账号没有手机号，下单前提示一次（选择"暂不"后本次会话不再打扰）
let mobilePromptDismissed = false

// ---- state ----
const selectedAddress = ref<AddressItem | null>(null)
const buyerRemark = ref('')
const submitting = ref(false)

// ---- 配送方式 ----
const deliveryType = ref<'express' | 'merchant' | 'pickup'>('express')
const pickupStore = ref<Store | null>(null)
// 同城配送试算时的错误（超出范围 / 缺经纬度），refreshCalc 写入，提交时也会再校验
const deliveryError = ref('')

// 配送方式或地址变更时重新试算（同城配送运费会随之变化）
watch([deliveryType, () => selectedAddress.value?.id], () => {
  if (items.value.length) refreshCalc()
})

// 取所有商品 delivery_modes 交集；空 items 或字段缺失时降级为 ['express', 'pickup']
const availableDeliveryTypes = computed<Array<'express' | 'merchant' | 'pickup'>>(() => {
  if (!items.value.length) return ['express', 'pickup']
  const sets = items.value.map(i => new Set(i.delivery_modes && i.delivery_modes.length > 0 ? i.delivery_modes : ['express', 'pickup']))
  const intersection = [...sets[0]].filter(m => sets.every(s => s.has(m)))
  const allowed = intersection.filter(m => m === 'express' || m === 'merchant' || m === 'pickup') as Array<'express' | 'merchant' | 'pickup'>
  return allowed.length ? allowed : ['express']
})

// ---- 发票申请 ----
const invoiceEnabled = ref(false)
const invoiceForm = reactive<InvoicePayload>({
  order_id: 0, // 提交订单后回填
  type: 'personal',
  title: '',
  tax_no: '',
  recipient_email: '',
})

function validateInvoice(): boolean {
  if (!invoiceEnabled.value) return true
  if (!invoiceForm.title.trim()) {
    uni.showToast({ title: '请输入发票抬头', icon: 'none' })
    return false
  }
  if (invoiceForm.type === 'company' && !invoiceForm.tax_no?.trim()) {
    uni.showToast({ title: '单位发票需填税号', icon: 'none' })
    return false
  }
  if (!/^[\w._-]+@[\w.-]+\.[a-zA-Z]{2,}$/.test(invoiceForm.recipient_email)) {
    uni.showToast({ title: '请输入正确的邮箱', icon: 'none' })
    return false
  }
  return true
}
const paying = ref(false)
const payPopupVisible = ref(false)
const currentOrderNo = ref('')

interface CheckoutItem {
  sku_id: number
  spu_id: number
  name: string
  cover: string
  sku_name: string
  price: number
  quantity: number
  delivery_modes?: string[]
  flash_item_id?: number
  group_activity_id?: number
  group_id?: number
}

const items = ref<CheckoutItem[]>([])

// Params from navigation
let goodsId = 0    // SPU id（商品详情接口的入参）
let skuId = 0      // SKU id（用于在 SPU.skus[] 中定位购买的 SKU）
let quantity = 1
let flashItemId = 0
let groupActivityId = 0
let groupId = 0

const availableCoupons = ref<AvailableCouponItem[]>([])
const selectedCoupon = ref<AvailableCouponItem | null>(null)
const couponPopupVisible = ref(false)

// ---- computed ----
const goodsAmount = computed(() =>
  items.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
)

const totalQuantity = computed(() =>
  items.value.reduce((sum, item) => sum + item.quantity, 0)
)

const calcResult = ref({
  goods_amount: '0.00',
  freight_amount: '0.00',
  discount_amount: '0.00',
  pay_amount: '0.00',
})

async function refreshCalc() {
  if (!items.value.length) {
    calcResult.value = {
      goods_amount: '0.00',
      freight_amount: '0.00',
      discount_amount: '0.00',
      pay_amount: '0.00',
    }
    return
  }
  try {
    const res = await orderApi.calc({
      skus: items.value.map(i => ({
        sku_id: i.sku_id,
        quantity: i.quantity,
        flash_item_id: i.flash_item_id,
        group_activity_id: i.group_activity_id,
        group_id: i.group_id,
      })),
      coupon_user_id: selectedCoupon.value?.id,
      delivery_type: deliveryType.value,
      region_code: selectedAddress.value?.region_code,
      province: selectedAddress.value?.province,
      // 同城配送时把用户地址坐标传给后端，用于运费计算 + 距离校验
      ...(deliveryType.value === 'merchant' && selectedAddress.value
        ? { lng: (selectedAddress.value as any).lng, lat: (selectedAddress.value as any).lat }
        : {}),
    })
    calcResult.value = res
    deliveryError.value = ''
  } catch (e: any) {
    // 同城配送相关的业务错误（超出范围 / 缺经纬度）展示在配送 chip 下方
    if (e?.message) {
      deliveryError.value = String(e.message)
    }
    // fallback 本地手算
    const sum = items.value.reduce((s, i) => s + Number(i.price) * i.quantity, 0)
    calcResult.value = {
      goods_amount: sum.toFixed(2),
      freight_amount: '0.00',
      discount_amount: '0.00',
      pay_amount: sum.toFixed(2),
    }
  }
}

// ---- helpers ----
// 后端 sku.price 是 decimal(10,2) 元（直接数值），前端不除 100
function formatPrice(val: number): string {
  return Number(val || 0).toFixed(2)
}

function getImageUrl(url: string): string {
  if (!url) return ''
  return appStore.getImageUrl(url)
}

// ---- load data ----
async function loadItems() {
  if (skuId) {
    // Buy-now flow：用 goods_id（SPU id）拉详情，从 skus 列表里找指定 sku_id
    if (!goodsId) {
      uni.showToast({ title: '商品参数缺失，请回到详情页重试', icon: 'none' })
      return
    }
    try {
      const goods = await goodsApi.getGoodsDetail(String(goodsId))
      const sku = goods.skus?.find((s: any) => s.id === skuId) || goods.skus?.[0]
      if (sku) {
        let promotionPrice = Number(sku.price)
        if (flashItemId > 0) {
          const sale = await marketingApi.getFlashSaleByGoods(goodsId)
          if (!sale || sale.matched_item.id !== flashItemId || sale.matched_item.sku_id !== sku.id) {
            throw new Error('秒杀活动已结束或商品不匹配')
          }
          promotionPrice = Number(sale.matched_item.flash_price)
        } else if (groupActivityId > 0) {
          const activity = await marketingApi.getGroupBuyDetail(groupActivityId)
          if (activity.sku_id !== sku.id) throw new Error('拼团商品不匹配')
          promotionPrice = Number(activity.group_price)
        }
        items.value = [{
          sku_id: sku.id,
          spu_id: goods.id,
          name: goods.name,
          cover: getImageUrl(sku.image || goods.images?.[0] || ''),
          sku_name: sku.spec_text || '',
          price: promotionPrice,
          quantity,
          // delivery_modes 在 SPU 上不在 SKU 上；不传会导致 buy-now 流程同城配送 chip 不显示
          delivery_modes: (goods as any).delivery_modes,
          flash_item_id: flashItemId || undefined,
          group_activity_id: groupActivityId || undefined,
          group_id: groupId || undefined,
        }]
      }
      await refreshCalc()
    } catch {
      uni.showToast({ title: '获取商品信息失败', icon: 'none' })
    }
  } else {
    // Cart flow
    try {
      const result = await cartApi.getSelectedItems()
      items.value = result.map((item: CartItem) => ({
        sku_id: item.sku_id,
        spu_id: item.spu_id,
        name: item.spu_name,
        cover: getImageUrl(item.image),
        sku_name: item.spec_text,
        price: item.price,
        quantity: item.quantity,
        delivery_modes: item.delivery_modes,
      }))
      await refreshCalc()
    } catch {
      uni.showToast({ title: '获取购物车失败', icon: 'none' })
    }
  }
}

async function loadAvailableCoupons() {
  if (!items.value.length || !getToken()) return
  try {
    availableCoupons.value = await marketingApi.getAvailableCoupons({
      order_amount: goodsAmount.value,
      spu_ids: items.value.map(item => item.spu_id).join(','),
    })
    if (selectedCoupon.value && !availableCoupons.value.some(item => item.id === selectedCoupon.value?.id)) {
      selectedCoupon.value = null
    }
  } catch {
    availableCoupons.value = []
    selectedCoupon.value = null
  }
}

function selectCoupon(item: AvailableCouponItem | null) {
  selectedCoupon.value = item
  couponPopupVisible.value = false
  refreshCalc()
}

async function loadDefaultAddress() {
  try {
    const result = await memberApi.getDefaultAddress()
    if (result) {
      selectedAddress.value = result
    } else {
      // Fallback: load first address from list
      const list = await memberApi.getAddressList()
      if (list && list.length > 0) {
        selectedAddress.value = list[0]
      }
    }
  } catch {
    // no address
  }
}

// ---- address selection ----
const addrPickerVisible = ref(false)

function selectAddress() {
  addrPickerVisible.value = true
}

function onAddrSelected(addr: AddressItem) {
  selectedAddress.value = addr
}

// ---- 自提门店选择 ----
function onPickPickupStore() {
  uni.navigateTo({ url: '/modules/checkout/pages/pickup-store' })
}

// ---- submit order ----
/**
 * 未绑定手机号时提示去绑定，返回 false 表示中断本次下单
 * 拿不到用户资料时直接放行，不阻塞下单
 */
async function confirmMobileBound(): Promise<boolean> {
  if (mobilePromptDismissed) return true

  if (!userStore.userInfo) {
    try {
      await userStore.getUserInfo()
    } catch {
      return true
    }
  }
  if (userStore.userInfo?.mobile) return true

  const res = await new Promise<boolean>(resolve => {
    uni.showModal({
      title: '建议绑定手机号',
      content: '绑定后可用手机号登录，并接收订单与售后通知',
      confirmText: '去绑定',
      cancelText: '暂不',
      success: e => resolve(!!e.confirm),
      fail: () => resolve(false),
    })
  })

  if (res) {
    uni.navigateTo({ url: '/modules/user/pages/edit-profile' })
    return false
  }

  mobilePromptDismissed = true
  return true
}

async function handleSubmit() {
  if (!getToken()) {
    redirectToLogin()
    return
  }
  if (deliveryType.value !== 'pickup' && !selectedAddress.value) {
    uni.showToast({ title: '请选择收货地址', icon: 'none' })
    return
  }
  if (deliveryType.value === 'pickup' && !pickupStore.value) {
    uni.showToast({ title: '请选择自提门店', icon: 'none' })
    return
  }
  // 同城配送：地址必须有 lng/lat（试算时若超出范围/缺经纬度 deliveryError 已写入）
  if (deliveryType.value === 'merchant') {
    if (!selectedAddress.value || !(selectedAddress.value as any).lng || !(selectedAddress.value as any).lat) {
      uni.showToast({ title: '请重新编辑地址并选择地图位置', icon: 'none', duration: 2500 })
      return
    }
  }
  if (deliveryError.value) {
    uni.showToast({ title: deliveryError.value, icon: 'none', duration: 2500 })
    return
  }
  if (items.value.length === 0) {
    uni.showToast({ title: '商品信息不完整', icon: 'none' })
    return
  }
  if (!validateInvoice()) return
  if (!(await confirmMobileBound())) return

  submitting.value = true
  try {
    const addr = selectedAddress.value
    const orderData: CreateOrderData = {
      items: items.value.map(i => ({
        sku_id: i.sku_id,
        quantity: i.quantity,
        flash_item_id: i.flash_item_id,
        group_activity_id: i.group_activity_id,
        group_id: i.group_id,
      })),
      address: addr ? {
        name: addr.name,
        phone: addr.phone,
        province: addr.province,
        city: addr.city,
        district: addr.district,
        detail: addr.detail,
        lng: (addr as any).lng,
        lat: (addr as any).lat,
        region_code: addr.region_code,
      } : undefined,
      buyer_remark: buyerRemark.value || undefined,
      coupon_user_id: selectedCoupon.value?.id,
      delivery_type: deliveryType.value,
      pickup_store_id: deliveryType.value === 'pickup' ? pickupStore.value!.id : undefined,
    }

    const result = await orderApi.createOrder(orderData)
    if (invoiceEnabled.value && result?.id) {
      try {
        await invoiceApi.submit({
          order_id: result.id,
          type: invoiceForm.type,
          title: invoiceForm.title.trim(),
          tax_no: invoiceForm.type === 'company' ? invoiceForm.tax_no?.trim() : undefined,
          recipient_email: invoiceForm.recipient_email.trim(),
          // 开票内容由后端默认「商品明细」，C 端不展示/不采集该字段
        })
      } catch (err: any) {
        uni.showToast({
          title: '订单已下单，但发票申请失败：' + (err?.message || ''),
          icon: 'none',
          duration: 3000,
        })
        // 失败不阻断订单流程
      }
    }
    if (result.order_no) {
      currentOrderNo.value = result.order_no
      // 零元订单（优惠券全额抵扣）下单时已标记已支付，跳过支付弹窗
      const payAmount = Number(result.pay_amount ?? 0)
      if (payAmount <= 0 || result.status === 'paid') {
        uni.navigateTo({
          url: `/modules/payment/pages/pay-result?order_no=${result.order_no}&status=success`,
        })
      } else {
        payPopupVisible.value = true
      }
    }
  } catch {
    // error shown by request layer
  } finally {
    submitting.value = false
  }
}

// ---- payment ----
async function handlePay(channel: PayChannel) {
  paying.value = true
  try {
    const success = await pay({
      order_no: currentOrderNo.value,
      channel,
    })

    payPopupVisible.value = false

    if (success) {
      uni.navigateTo({
        url: `/modules/payment/pages/pay-result?order_no=${currentOrderNo.value}&status=success`,
      })
    } else {
      uni.navigateTo({
        url: `/modules/payment/pages/pay-result?order_no=${currentOrderNo.value}&status=fail`,
      })
    }
  } finally {
    paying.value = false
  }
}

// ---- lifecycle ----
onLoad((options) => {
  goodsId = Number(options?.goods_id) || 0
  skuId = Number(options?.sku_id) || 0
  quantity = Number(options?.quantity) || 1
  flashItemId = Number(options?.flash_item_id) || 0
  groupActivityId = Number(options?.group_activity_id) || 0
  groupId = Number(options?.group_id) || 0
})

// 地址选择已改为内嵌 popup（d-address-picker），不再需要 globalData 跨页传递
// onShow 保留为空（如有其他需要可在此扩展）

onMounted(async () => {
  // 监听自提门店选择事件
  uni.$on('pickup:store-selected', (s: Store) => {
    pickupStore.value = s
  })

  await Promise.all([loadItems(), loadDefaultAddress()])
  await loadAvailableCoupons()
})

onUnmounted(() => {
  uni.$off('pickup:store-selected')
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.checkout-page {
  min-height: 100vh;
  background: var(--color-bg, #{$bg-color});
}


.checkout-scroll {
  // height set dynamically
}

.checkout-section {
  background: #ffffff;
  margin: 20rpx 24rpx;
  border-radius: 16rpx;
  padding: 28rpx 28rpx;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);

  &--items {
    padding-bottom: 20rpx;
  }

  &--summary {
    padding: 24rpx 28rpx;
  }
}

.section-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 20rpx;
  padding-bottom: 16rpx;
  border-bottom: 1rpx solid #f5f5f5;
}

// Address card
.address-card {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: center;

  &__icon {
    flex-shrink: 0;
    margin-right: 16rpx;
  }

  &__info {
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    width: 0;
    min-width: 0;
  }

  &__name-row {
    display: flex;
    flex-direction: row;
    align-items: center;
    margin-bottom: 8rpx;
    flex-wrap: wrap;
  }

  &__name {
    font-size: 30rpx;
    font-weight: 600;
    color: #18181b;
    margin-right: 16rpx;
  }

  &__mobile {
    font-size: 28rpx;
    color: #71717a;
    margin-right: 12rpx;
  }

  &__default-tag {
    font-size: 20rpx;
    color: #ffffff;
    background: #2979ff;
    padding: 2rpx 10rpx;
    border-radius: 6rpx;
    line-height: 1.5;
  }

  &__detail {
    font-size: 26rpx;
    color: #71717a;
    line-height: 1.5;
  }
}

.address-empty {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: center;

  &__text {
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    width: 0;
    min-width: 0;
    margin: 0 12rpx;
    font-size: 28rpx;
    color: #71717a;
  }
}

// Items list
.items-empty {
  text-align: center;
  padding: 40rpx 0;
  font-size: 26rpx;
  color: #ccc;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 20rpx;
}

.item-row {
  display: flex;
  gap: 20rpx;
  padding: 12rpx 0;
  border-bottom: 1rpx solid #f9f9f9;

  &:last-child {
    border-bottom: none;
  }
}

.item-cover {
  width: 120rpx;
  height: 120rpx;
  border-radius: 12rpx;
  flex-shrink: 0;
  background: #f5f5f5;
}

.item-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.item-name {
  font-size: 28rpx;
  color: #333;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.item-sku {
  font-size: 24rpx;
  color: #999;
}

.item-price-row {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-top: auto;
}

.item-qty {
  font-size: 24rpx;
  color: #999;
}

// Remark textarea
.remark-textarea {
  width: 100%;
  min-height: 100rpx;
  font-size: 26rpx;
  color: #333;
  background: #f9f9f9;
  border-radius: 12rpx;
  padding: 16rpx 20rpx;
  box-sizing: border-box;
  line-height: 1.5;
}

// Summary
.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16rpx;

  &:last-child {
    margin-bottom: 0;
  }
}

.summary-divider {
  height: 1rpx;
  background: #f0f0f0;
  margin: 16rpx 0;
}

.summary-label {
  font-size: 28rpx;
  color: #666;

  &--bold {
    font-size: 30rpx;
    font-weight: 600;
    color: #333;
  }
}

.summary-value {
  font-size: 28rpx;
  color: #333;

  &--free {
    color: #19be6b;
  }

  &--total {
    font-size: 32rpx;
    font-weight: 700;
    color: $danger-color;
  }

  &--discount {
    color: var(--color-primary, #{$primary-color});
  }
}

// Invoice section（与其他 checkout-section 同边距）
.invoice-section {
  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__title {
    font-size: 28rpx;
    font-weight: 600;
    color: #333;
  }
}

.invoice-form {
  margin-top: 24rpx;
  padding-top: 24rpx;
  border-top: 1rpx solid #f5f5f5;
}

.invoice-tabs {
  display: flex;
  gap: 16rpx;
  margin-bottom: 8rpx;
}

.invoice-tab {
  flex: 1;
  height: 64rpx;
  line-height: 64rpx;
  text-align: center;
  border-radius: 12rpx;
  font-size: 26rpx;
  background: #f5f5f5;
  color: $text-color-secondary;

  &--active {
    background: var(--color-primary, #{$primary-color});
    color: #fff;
  }
}

.invoice-field {
  display: flex;
  align-items: center;
  gap: 20rpx;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  &__label {
    flex-shrink: 0;
    width: 72rpx;
    font-size: 26rpx;
    color: #666;
  }

  &__input {
    flex: 1;
    min-width: 0;
    height: 64rpx;
    padding: 0 20rpx;
    font-size: 26rpx;
    color: #333;
    background: #f9f9f9;
    border-radius: 12rpx;
    box-sizing: border-box;
  }

  &__placeholder {
    color: #c0c4cc;
    font-size: 26rpx;
  }
}

// Bottom bar (inline 实现，避免 mp 自定义组件 wrapper 撑开)
.checkout-bottom {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: #ffffff;
  padding: 16rpx 24rpx;
  padding-bottom: calc(16rpx + constant(safe-area-inset-bottom));
  padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
  box-shadow: 0 -2rpx 8rpx rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: center;
  box-sizing: border-box;

  &__total-wrap {
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    width: 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  &__count {
    font-size: 22rpx;
    color: #a1a1aa;
    line-height: 1.2;
  }

  &__total-row {
    display: flex;
    flex-direction: row;
    align-items: baseline;
    margin-top: 4rpx;
  }

  &__total-label {
    font-size: 24rpx;
    color: #71717a;
  }

  &__total {
    font-size: 36rpx;
    font-weight: 700;
    color: #ef4444;
    margin-left: 4rpx;
  }

  &__btn {
    flex-shrink: 0;
    min-width: 220rpx;
    height: 80rpx;
    line-height: 80rpx;
    padding: 0 32rpx;
    border-radius: 40rpx;
    background: #2979ff;
    color: #ffffff;
    font-size: 28rpx;
    font-weight: 600;
    text-align: center;
    margin-left: 16rpx;

    &--disabled {
      background: #d4d4d8;
      color: #ffffff;
    }
  }
}

.delivery-error {
  display: block;
  margin-top: 12rpx;
  padding: 12rpx 20rpx;
  background: rgba(250, 53, 52, 0.08);
  color: var(--color-danger, #fa3534);
  font-size: 24rpx;
  border-radius: 12rpx;
}

.coupon-cell {
  display: flex;
  align-items: center;
  justify-content: space-between;

  &__label { font-size: 28rpx; color: #333; }
  &__right { display: flex; align-items: center; gap: 8rpx; }
  &__hint { font-size: 26rpx; color: #a1a1aa; }
  &__discount { font-size: 26rpx; color: #ef4444; font-weight: 600; }
}

.coupon-popup {
  max-height: 70vh;
  display: flex;
  flex-direction: column;

  &__header { display: flex; justify-content: space-between; align-items: center; padding: 30rpx 32rpx; border-bottom: 1rpx solid #f1f1f1; }
  &__title { font-size: 32rpx; font-weight: 600; color: #27272a; }
  &__list { max-height: 56vh; padding: 16rpx 24rpx 32rpx; box-sizing: border-box; }
  &__empty { text-align: center; color: #a1a1aa; font-size: 26rpx; padding: 64rpx 0; }
}

.coupon-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx;
  margin-bottom: 16rpx;
  border: 2rpx solid #f0f0f0;
  border-radius: 16rpx;

  &.is-active { border-color: var(--color-primary, #{$primary-color}); background: rgba(41, 121, 255, 0.04); }
  &__name, &__desc { display: block; }
  &__name { font-size: 28rpx; color: #27272a; font-weight: 600; }
  &__desc { margin-top: 8rpx; font-size: 24rpx; color: #71717a; }
  &__check { color: var(--color-primary, #{$primary-color}); font-size: 32rpx; }
}
</style>
