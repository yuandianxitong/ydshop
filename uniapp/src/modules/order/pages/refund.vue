<template>
  <d-page :safe-area="true">
    <view class="refund-page">

      <!-- Item info -->
      <view class="section-card item-card">
        <view class="section-title">退款商品</view>
        <view v-if="goodsItem" class="item-info">
          <image
            :src="appStore.getImageUrl(goodsItem.goods_image)"
            mode="aspectFill"
            class="item-cover"
          />
          <view class="item-detail">
            <text class="item-name">{{ goodsItem.goods_name }}</text>
            <text v-if="goodsItem.spec_text" class="item-spec">规格：{{ goodsItem.spec_text }}</text>
            <view class="item-price-row">
              <text class="item-price">¥{{ formatPrice(goodsItem.price) }}</text>
              <text class="item-qty"> × {{ goodsItem.quantity }}</text>
            </view>
          </view>
        </view>
        <view v-else class="item-loading">
          <u-loading-icon size="40rpx" />
        </view>
      </view>

      <!-- Refund type -->
      <view class="section-card">
        <view class="section-title">退款类型</view>
        <u-radio-group v-model="form.refund_type">
          <u-radio :name="1" label="仅退款" />
          <u-radio :name="2" label="退货退款" />
        </u-radio-group>
      </view>

      <!-- Refund amount -->
      <view class="section-card">
        <view class="section-title">退款金额</view>
        <view class="amount-row">
          <text class="amount-symbol">¥</text>
          <input
            v-model="amountInput"
            type="digit"
            class="amount-input"
            placeholder="请输入退款金额"
            placeholder-class="input-placeholder"
            @input="onAmountInput"
          />
          <text class="amount-max">最多 ¥{{ maxAmountText }}</text>
        </view>
      </view>

      <!-- Reason -->
      <view class="section-card">
        <view class="section-title">退款原因</view>
        <view class="reason-list">
          <view
            v-for="item in reasonOptions"
            :key="item.value"
            class="reason-tag"
            :class="{ active: form.reason === item.value }"
            @tap="form.reason = item.value"
          >
            {{ item.label }}
          </view>
        </view>
      </view>

      <!-- Description -->
      <view class="section-card">
        <view class="section-title">问题描述 <text class="section-hint">（选填）</text></view>
        <u-textarea
          v-model="form.description"
          placeholder="请详细描述退款原因..."
          :maxlength="500"
          count
          :height="160"
        />
      </view>

      <!-- Images -->
      <view class="section-card">
        <view class="section-title">凭证图片 <text class="section-hint">（最多5张）</text></view>
        <view class="image-upload">
          <view
            v-for="(img, index) in form.images"
            :key="index"
            class="image-item"
          >
            <image
              :src="appStore.getImageUrl(img)"
              mode="aspectFill"
              class="preview-image"
              @tap="previewImage(index)"
            />
            <view class="image-delete" @tap="removeImage(index)">
              <d-icon name="trash" size="24rpx" color="#fff" />
            </view>
          </view>
          <view
            v-if="form.images.length < 5"
            class="image-add"
            @tap="handleUpload"
          >
            <d-icon name="image" size="56rpx" color="#cccccc" />
          </view>
        </view>
      </view>

      <!-- Submit button -->
      <u-button
        :loading="submitting"
        :disabled="!canSubmit"
        :customStyle="{ width: '100%' }"
        class="submit-btn"
        @click="handleSubmit"
      >
        提交申请
      </u-button>

    </view>
  </d-page>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { orderApi, type OrderGoodsItem } from '@/api/order'
import { useUpload } from '@/hooks/useUpload'
import { useAppStore } from '@/store/app.store'

const appStore = useAppStore()
const { chooseAndUpload } = useUpload()

// Route params — 必须 onLoad 时机拿，setup 顶层调 getCurrentPages 在 mp 当前页注册前 options 永远空
let orderId = ''
let orderItemId = ''

const goodsItem = ref<OrderGoodsItem | null>(null)
const submitting = ref(false)

const form = reactive({
  refund_type: 1,
  reason: '',
  description: '',
  images: [] as string[],
})

const amountInput = ref('')

const reasonOptions = [
  { label: '不想要了', value: '不想要了' },
  { label: '商品质量问题', value: '商品质量问题' },
  { label: '商品与描述不符', value: '商品与描述不符' },
  { label: '发错货', value: '发错货' },
  { label: '商品损坏/破损', value: '商品损坏/破损' },
  { label: '未收到货', value: '未收到货' },
  { label: '其他原因', value: '其他原因' },
]

const maxAmountText = computed(() => {
  if (!goodsItem.value) return '0.00'
  return formatPrice(Number(goodsItem.value.price) * goodsItem.value.quantity)
})

const canSubmit = computed(() => {
  return !!form.reason && !!amountInput.value && !submitting.value
})

function formatPrice(val: string | number | undefined): string {
  if (val == null) return '0.00'
  return Number(val).toFixed(2)
}

function onAmountInput(e: any) {
  if (!goodsItem.value) return
  const max = Number(goodsItem.value.price) * goodsItem.value.quantity
  const val = parseFloat(e.detail.value)
  if (!isNaN(val) && val > max) {
    amountInput.value = max.toFixed(2)
  }
}

function removeImage(index: number) {
  form.images.splice(index, 1)
}

function previewImage(index: number) {
  const urls = form.images.map((img) => appStore.getImageUrl(img))
  uni.previewImage({ urls, current: urls[index] })
}

async function handleUpload() {
  if (form.images.length >= 5) return
  try {
    const path = await chooseAndUpload()
    form.images.push(path)
  } catch {
    // user cancelled or upload failed
  }
}

async function handleSubmit() {
  if (!canSubmit.value || !goodsItem.value) return

  const amountVal = parseFloat(amountInput.value)
  if (isNaN(amountVal) || amountVal <= 0) {
    uni.showToast({ title: '请输入正确的退款金额', icon: 'none' })
    return
  }

  submitting.value = true
  try {
    // http 拦截器已解包，成功直接拿到结果；后端 refund_amount 单位是元（float）
    await orderApi.applyRefund({
      order_id: Number(orderId),
      order_item_id: goodsItem.value.id,
      type: form.refund_type === 2 ? 'return_refund' : 'refund_only',
      refund_amount: amountVal,
      reason: form.reason,
      description: form.description || undefined,
      images: form.images.length > 0 ? form.images : undefined,
    })
    uni.showToast({ title: '申请已提交', icon: 'success' })
    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
  } catch {
    // handled by request interceptor
  } finally {
    submitting.value = false
  }
}

async function loadItem() {
  if (!orderId) {
    uni.showToast({ title: '缺少订单参数', icon: 'none' })
    return
  }
  try {
    // http 拦截器已解包，直接拿到 OrderItem（含 items 数组）
    const order = await orderApi.getOrderDetail(orderId)
    const items = (order as any)?.items || []
    const item = items.find((g: any) => String(g.id) === String(orderItemId))
    if (!item) {
      // 兜底：参数没匹配上时取第一项也比一直 loading 强
      goodsItem.value = items[0] || null
      if (items.length === 0) {
        uni.showToast({ title: '订单未含商品', icon: 'none' })
      }
    } else {
      goodsItem.value = item
    }
    if (goodsItem.value) {
      amountInput.value = formatPrice(Number(goodsItem.value.price) * goodsItem.value.quantity)
    }
  } catch (e) {
    console.error('[refund] loadItem error', e)
  }
}

onLoad((options: any) => {
  orderId = String(options?.order_id ?? '')
  orderItemId = String(options?.goods_id ?? options?.order_item_id ?? '')
  loadItem()
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.refund-page {
  padding: 0;
}

.section-card {
  background: var(--color-white, #ffffff);
  border-radius: 16rpx;
  overflow: hidden;
  margin-bottom: 24rpx;
  padding: 28rpx;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.05);
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: var(--color-text, #{$text-color});
  margin-bottom: 24rpx;
}

.section-hint {
  font-size: 24rpx;
  font-weight: 400;
  color: var(--color-text-2, #{$text-color-secondary});
}

/* Item info */
.item-card {
  .item-info {
    display: flex;
    align-items: flex-start;
    gap: 20rpx;
  }

  .item-cover {
    width: 120rpx;
    height: 120rpx;
    border-radius: 12rpx;
    flex-shrink: 0;
    background: var(--color-bg, #{$bg-color});
  }

  .item-detail {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
  }

  .item-name {
    font-size: 28rpx;
    font-weight: 500;
    color: var(--color-text, #{$text-color});
    line-height: 1.4;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
  }

  .item-spec {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
  }

  .item-price-row {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
    margin-top: 4rpx;
  }

  .item-price {
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-primary, #{$primary-color});
  }

  .item-qty {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
  }

  .item-loading {
    display: flex;
    justify-content: center;
    padding: 40rpx;
  }
}

/* Amount */
.amount-row {
  display: flex;
  align-items: center;
  border: 2rpx solid var(--color-border, #{$border-color});
  border-radius: 16rpx;
  padding: 20rpx 24rpx;
  gap: 8rpx;

  .amount-symbol {
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
    flex-shrink: 0;
  }

  .amount-input {
    flex: 1;
    font-size: 32rpx;
    font-weight: 600;
    color: var(--color-text, #{$text-color});
    min-width: 0;
  }

  .amount-max {
    font-size: 24rpx;
    color: var(--color-text-2, #{$text-color-secondary});
    white-space: nowrap;
    flex-shrink: 0;
  }
}

/* Reason tags */
.reason-list {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
}

.reason-tag {
  padding: 16rpx 30rpx;
  border-radius: 40rpx;
  font-size: 26rpx;
  color: var(--color-text, #{$text-color});
  background: var(--color-bg, #{$bg-color});
  transition: all 0.2s;
  border: 2rpx solid transparent;

  &.active {
    color: var(--color-primary, #{$primary-color});
    background: rgba(41, 121, 255, 0.08);
    border-color: var(--color-primary, #{$primary-color});
  }
}

/* Image upload */
.image-upload {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
}

.image-item {
  position: relative;
  width: 200rpx;
  height: 200rpx;
  border-radius: 16rpx;
  overflow: hidden;
}

.preview-image {
  width: 100%;
  height: 100%;
}

.image-delete {
  position: absolute;
  top: 0;
  right: 0;
  width: 44rpx;
  height: 44rpx;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 0 0 0 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-add {
  width: 200rpx;
  height: 200rpx;
  border-radius: 16rpx;
  border: 2rpx dashed var(--color-border, #{$border-color});
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg, #{$bg-color});
}

/* Submit */
.submit-btn {
  border-radius: 16rpx !important;
  height: 96rpx !important;
  font-size: 32rpx !important;
  margin-top: 12rpx;
}

/* Placeholder */
.input-placeholder {
  color: var(--color-text-3, #c0c4cc);
  font-size: 28rpx;
}
</style>
