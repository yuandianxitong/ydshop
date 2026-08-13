<template>
  <d-page :safe-area="true">
    <view class="review-page">

      <!-- Item info -->
      <view class="section-card item-card">
        <view class="section-title">评价商品</view>
        <view v-if="goodsItem" class="item-info">
          <image
            :src="appStore.getImageUrl(goodsItem.goods_image)"
            mode="aspectFill"
            class="item-cover"
          />
          <view class="item-detail">
            <text class="item-name">{{ goodsItem.goods_name }}</text>
            <text v-if="goodsItem.spec_text" class="item-spec">规格：{{ goodsItem.spec_text }}</text>
          </view>
        </view>
        <view v-else class="item-loading">
          <u-loading-icon size="40rpx" />
        </view>
      </view>

      <!-- Star rating -->
      <view class="section-card">
        <view class="section-title">商品评分</view>
        <view class="rating-row">
          <u-rate v-model="form.rating" :size="40" />
          <text class="rating-label">{{ ratingLabel }}</text>
        </view>
      </view>

      <!-- Review content -->
      <view class="section-card">
        <view class="section-title">评价内容 <text class="section-hint">（选填）</text></view>
        <u-textarea
          v-model="form.content"
          placeholder="分享您的使用体验，帮助其他买家做出更好的选择..."
          :maxlength="500"
          count
          :height="200"
        />
      </view>

      <!-- Images -->
      <view class="section-card">
        <view class="section-title">上传图片 <text class="section-hint">（最多5张）</text></view>
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

      <!-- Anonymous toggle -->
      <view class="section-card anonymous-card">
        <view class="anonymous-row">
          <view>
            <text class="anonymous-label">匿名评价</text>
            <text class="anonymous-hint">开启后将以匿名方式显示评价</text>
          </view>
          <u-switch v-model="isAnonymous" />
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
        提交评价
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

// Route params — 必须在 onLoad 时机拿，setup 顶层调 getCurrentPages 在 mp 当前页注册前 options 永远空
let orderId = ''
let orderItemId = ''

const goodsItem = ref<OrderGoodsItem | null>(null)
const submitting = ref(false)
const isAnonymous = ref(false)

const form = reactive({
  rating: 5,
  content: '',
  images: [] as string[],
})

const ratingLabels: Record<number, string> = {
  1: '很差',
  2: '较差',
  3: '一般',
  4: '不错',
  5: '非常好',
}

const ratingLabel = computed(() => ratingLabels[form.rating] ?? '')

const canSubmit = computed(() => {
  return form.rating > 0 && !submitting.value
})

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

  submitting.value = true
  try {
    // http 拦截器已解包，成功直接拿到 review 数据；失败抛错由 catch 处理
    await orderApi.createReview({
      order_id: Number(orderId),
      order_item_id: goodsItem.value.id,  // 后端字段名是 order_item_id
      spu_id: goodsItem.value.spu_id,
      rating: form.rating,
      content: form.content.trim() || undefined,
      images: form.images.length > 0 ? form.images : undefined,
      is_anonymous: isAnonymous.value ? 1 : 0,
    })
    uni.showToast({ title: '评价成功', icon: 'success' })
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
      if (items.length > 0) {
        console.warn('[review] orderItemId 未匹配任何 items，fallback 到第一项')
      } else {
        uni.showToast({ title: '订单未含商品', icon: 'none' })
      }
    } else {
      goodsItem.value = item
    }
  } catch (e) {
    console.error('[review] loadItem error', e)
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

.review-page {
  padding: 0;
}

.section-card {
  background: #ffffff;
  border-radius: 24rpx;
  overflow: hidden;
  margin-bottom: 24rpx;
  padding: 30rpx;
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
  color: $text-color-secondary;
}

/* Item info */
.item-card {
  .item-info {
    display: flex;
    align-items: center;
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
    gap: 10rpx;
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
    color: $text-color-secondary;
  }

  .item-loading {
    display: flex;
    justify-content: center;
    padding: 40rpx;
  }
}

/* Rating */
.rating-row {
  display: flex;
  align-items: center;
  gap: 24rpx;

  .rating-label {
    font-size: 28rpx;
    color: var(--color-primary, #{$primary-color});
    font-weight: 500;
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
  border: 2rpx dashed #cccccc;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg, #{$bg-color});
}

/* Anonymous toggle */
.anonymous-card {
  padding-top: 24rpx;
  padding-bottom: 24rpx;

  .anonymous-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .anonymous-label {
    font-size: 30rpx;
    font-weight: 500;
    color: var(--color-text, #{$text-color});
    display: block;
    margin-bottom: 6rpx;
  }

  .anonymous-hint {
    font-size: 24rpx;
    color: $text-color-secondary;
    display: block;
  }
}

/* Submit */
.submit-btn {
  border-radius: 16rpx !important;
  height: 96rpx !important;
  font-size: 32rpx !important;
  margin-top: 12rpx;
}
</style>
