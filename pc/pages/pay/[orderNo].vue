<template>
  <div class="bg-gray-50 min-h-screen pb-16">
    <div class="mx-auto max-w-600px px-4 py-10">

      <!-- Page title -->
      <div class="flex items-center gap-3 mb-8">
        <NuxtLink to="/order" class="text-gray-400 hover:text-gray-600">
          <span class="i-carbon-arrow-left text-xl" />
        </NuxtLink>
        <h1 class="text-xl font-bold text-gray-800">订单支付</h1>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="bg-white rounded p-8 text-center">
        <div class="flex justify-center mb-4">
          <div class="w-10 h-10 border-3 border-gray-200 border-t-[var(--color-primary)] rounded-full animate-spin" />
        </div>
        <p class="text-gray-400 text-sm">正在加载订单信息...</p>
      </div>

      <!-- Not found -->
      <div v-else-if="!order" class="bg-white rounded p-8 text-center">
        <span class="i-carbon-warning text-5xl text-gray-300 block mb-3" />
        <p class="text-gray-500">订单不存在</p>
        <NuxtLink to="/" class="mt-4 inline-block text-sm text-[var(--color-primary)] hover:underline">
          返回首页
        </NuxtLink>
      </div>

      <!-- Already paid -->
      <div v-else-if="order.status !== 'pending'" class="bg-white rounded p-8 text-center">
        <span class="i-carbon-checkmark-filled text-5xl text-green-500 block mb-3" />
        <p class="text-gray-800 text-lg font-semibold mb-1">该订单已支付</p>
        <p class="text-gray-400 text-sm mb-6">无需重复支付</p>
        <NuxtLink
          :to="`/order/${orderNo}`"
          class="inline-block px-6 py-2 bg-[var(--color-primary)] text-white rounded text-sm hover:opacity-85 transition-opacity"
        >
          查看订单详情
        </NuxtLink>
      </div>

      <!-- Payment panel -->
      <template v-else>

        <!-- Order info -->
        <div class="bg-white rounded p-6 mb-4 text-center">
          <p class="text-sm text-gray-400 mb-1">订单号：<span class="font-mono">{{ orderNo }}</span></p>
          <p class="text-4xl font-bold text-red-500 mt-3">
            <span class="text-xl font-medium mr-1">¥</span>{{ formatPrice(order.pay_amount) }}
          </p>
          <p class="text-sm text-gray-400 mt-1">实付金额</p>
        </div>

        <!-- Payment methods -->
        <div class="bg-white rounded p-6 mb-4">
          <h2 class="text-base font-semibold text-gray-700 mb-4">选择支付方式</h2>

          <div class="flex gap-3">
            <!-- WeChat Pay -->
            <button
              class="pay-method-btn"
              :class="{ 'is-active': channel === 'wechat' }"
              @click="channel = 'wechat'"
            >
              <span class="w-8 h-8 bg-green-500 rounded flex items-center justify-center text-white text-lg flex-shrink-0">
                <span class="i-carbon-chat" />
              </span>
              <span class="text-sm font-medium text-gray-700">微信支付</span>
              <span
                v-if="channel === 'wechat'"
                class="i-carbon-checkmark-filled text-[var(--color-primary)] text-base ml-auto flex-shrink-0"
              />
            </button>

            <!-- Alipay -->
            <button
              class="pay-method-btn"
              :class="{ 'is-active': channel === 'alipay' }"
              @click="channel = 'alipay'"
            >
              <span class="w-8 h-8 bg-blue-500 rounded flex items-center justify-center text-white text-lg flex-shrink-0">
                <span class="i-carbon-wallet" />
              </span>
              <span class="text-sm font-medium text-gray-700">支付宝</span>
              <span
                v-if="channel === 'alipay'"
                class="i-carbon-checkmark-filled text-[var(--color-primary)] text-base ml-auto flex-shrink-0"
              />
            </button>
          </div>
        </div>

        <!-- QR Code panel (WeChat) -->
        <div v-if="channel === 'wechat' && qrCodeDataUrl" class="bg-white rounded p-6 mb-4 text-center">
          <h2 class="text-sm text-gray-500 mb-4">请使用微信扫描以下二维码完成支付</h2>
          <div class="flex justify-center mb-3">
            <img
              :src="qrCodeDataUrl"
              alt="支付二维码"
              class="w-48 h-48 border border-gray-100 rounded p-2"
            />
          </div>
          <div class="flex items-center justify-center gap-2 text-sm text-gray-400">
            <span
              class="inline-block w-2 h-2 rounded-full animate-pulse"
              :class="polling ? 'bg-green-400' : 'bg-gray-300'"
            />
            {{ polling ? '等待支付确认...' : '二维码已生成' }}
          </div>
        </div>

        <!-- Pay button -->
        <NButton
          v-if="channel !== 'wechat' || !qrCodeDataUrl"
          type="primary"
          size="large"
          block
          :loading="paying"
          @click="handlePay"
        >
          {{ paying ? '正在生成支付链接...' : `立即支付 ¥${formatPrice(order.pay_amount)}` }}
        </NButton>

        <!-- WeChat: initiate QR -->
        <NButton
          v-if="channel === 'wechat' && !qrCodeDataUrl"
          type="primary"
          size="large"
          block
          :loading="paying"
          @click="handlePay"
        >
          {{ paying ? '正在生成二维码...' : '生成微信支付二维码' }}
        </NButton>

      </template>

    </div>
  </div>
</template>

<script setup lang="ts">
import { NButton, useMessage } from 'naive-ui'
import QRCode from 'qrcode'
import { orderApi } from '~/api/order'
import { paymentApi } from '~/api/payment'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const message = useMessage()

const orderNo = computed(() => route.params.orderNo as string)

const loading = ref(true)
const paying = ref(false)
const polling = ref(false)
const channel = ref<'wechat' | 'alipay'>('wechat')
const order = ref<Awaited<ReturnType<typeof orderApi.getOrderDetail>>['data'] | null>(null)
const qrCodeDataUrl = ref('')

let pollTimer: ReturnType<typeof setInterval> | null = null

function formatPrice(val: string | number | undefined | null): string {
  if (val == null || val === '') return '0.00'
  const n = typeof val === 'number' ? val : Number(val)
  if (!Number.isFinite(n)) return '0.00'
  return n.toFixed(2)
}

async function loadOrder() {
  loading.value = true
  try {
    const res = await orderApi.getOrderDetail(orderNo.value)
    if (res.code === 200) {
      order.value = res.data
    }
  } finally {
    loading.value = false
  }
}

async function handlePay() {
  if (!order.value) return
  paying.value = true
  try {
    const res = await paymentApi.createOrder({
      order_no: orderNo.value,
      channel: channel.value,
      trade_type: channel.value === 'wechat' ? 'native' : 'page',
    })

    if (res.code !== 200) return
    const data = res.data?.payment_data?.data ?? {}

    if (channel.value === 'wechat') {
      const codeUrl = data.code_url as string | undefined
      if (!codeUrl) {
        message.error('生成微信支付二维码失败')
        return
      }
      qrCodeDataUrl.value = await QRCode.toDataURL(codeUrl, {
        width: 200,
        margin: 1,
        color: { dark: '#000000', light: '#ffffff' },
      })
      startPolling()
    } else {
      // 支付宝 PC 网页支付：driver 返回 data.body 是签名后的自动提交表单 HTML
      // 用 Blob URL 在新标签页加载，浏览器解析后表单自动提交
      const body = data.body as string | undefined
      if (!body) {
        message.error('生成支付宝支付链接失败')
        return
      }
      const blob = new Blob([body], { type: 'text/html' })
      const url = URL.createObjectURL(blob)
      window.open(url, '_blank')
      setTimeout(() => URL.revokeObjectURL(url), 60_000)
      startPolling()
    }
  } finally {
    paying.value = false
  }
}

function startPolling() {
  polling.value = true
  pollTimer = setInterval(async () => {
    try {
      const res = await orderApi.getOrderDetail(orderNo.value)
      if (res.code === 200 && res.data.status !== 'pending') {
        stopPolling()
        const isPaid = ['paid', 'shipped', 'completed'].includes(res.data.status)
        router.push(`/pay/result?orderNo=${orderNo.value}&status=${isPaid ? 'success' : 'fail'}`)
      }
    } catch {
      // ignore polling errors
    }
  }, 3000)
}

function stopPolling() {
  polling.value = false
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(() => {
  loadOrder()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.pay-method-btn {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s;
}

.pay-method-btn:hover {
  border-color: var(--color-primary);
}

.pay-method-btn.is-active {
  border-color: var(--color-primary);
  background: rgb(from var(--color-primary) r g b / 0.04);
}
</style>
