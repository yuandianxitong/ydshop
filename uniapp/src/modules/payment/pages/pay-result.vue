<template>
  <d-page :safe-area="true">
    <d-pay-result :success="isSuccess" :message="resultMessage">

      <!-- Self-pickup banner (shown when payment succeeded for a pickup order) -->
      <d-pickup-banner
        v-if="isSuccess && orderInfo && (orderInfo as any).delivery_type === 'pickup' && (orderInfo as any).pickup_code && pickupStore"
        :code="(orderInfo as any).pickup_code"
        :store="pickupStore"
        class="pay-result__pickup-banner"
      />

      <view class="result-actions">
        <u-button type="primary" :customStyle="{ width: '100%' }" @click="goHome" class="action-btn">
          返回首页
        </u-button>
        <u-button plain :customStyle="{ width: '100%' }" @click="goOrderDetail" class="action-btn">
          查看订单
        </u-button>
      </view>
    </d-pay-result>
  </d-page>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { usePayment } from '@/composables/usePayment'
import { orderApi } from '@/api/order'
import { storeApi, type Store } from '@/api/store'

const { checkPayResult } = usePayment()

const isSuccess = ref(false)
const resultMessage = ref('')
const orderNo = ref('')
const orderInfo = ref<any>(null)
const pickupStore = ref<Store | null>(null)

// ---- fetch order info for pickup banner ----
watch(orderNo, async (no) => {
  if (!no) return
  try {
    const item = await orderApi.getOrderDetail(no)
    if (item) orderInfo.value = item
  } catch {
    // non-critical — banner simply hidden
  }
}, { immediate: false })

// ---- fetch pickup store when order is loaded ----
watch(orderInfo, async (info) => {
  if (info && info.delivery_type === 'pickup' && info.pickup_store_id) {
    try {
      pickupStore.value = await storeApi.detail(info.pickup_store_id)
    } catch {
      // silent fail
    }
  }
})

onLoad((query) => {
  orderNo.value = query?.order_no || ''
  isSuccess.value = query?.status === 'success'
  resultMessage.value = isSuccess.value ? '支付成功' : '支付失败'
})

onMounted(async () => {
  if (!orderNo.value) return
  // 后端 queryOrder 找不到 payment_orders 时返"订单不存在"——视为未支付，按 query.status 兜底
  let paid = false
  try {
    paid = await checkPayResult(orderNo.value)
  } catch {
    paid = false
  }
  // query.status 为 success 时优先信任前端跳转意图（usePayment.pay 真返回成功）
  if (!paid && isSuccess.value) {
    paid = true
  }
  isSuccess.value = paid
  resultMessage.value = paid ? '支付成功' : '支付失败'
  if (paid) {
    try {
      const item = await orderApi.getOrderDetail(orderNo.value)
      if (item) orderInfo.value = item
    } catch {
      // non-critical
    }
  }
})

function goHome() {
  uni.reLaunch({ url: '/pages/index/index' })
}

function goOrderDetail() {
  // 优先用业务订单主键 id，没拿到就用 order_no（detail.vue 后端已兼容长字符串）
  const idOrNo = orderInfo.value?.id ?? orderNo.value
  if (idOrNo) {
    uni.redirectTo({ url: `/modules/order/pages/detail?id=${idOrNo}` })
  } else {
    uni.navigateBack()
  }
}
</script>

<style lang="scss" scoped>
.pay-result__pickup-banner {
  margin: 0 32rpx 32rpx;
}

.result-actions {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
  padding: 40rpx 32rpx;

  .action-btn {
    border-radius: 16rpx !important;
    height: 88rpx !important;
    font-size: 30rpx !important;
  }
}
</style>
