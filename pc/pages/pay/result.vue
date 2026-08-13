<template>
  <div class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded p-10 max-w-md w-full mx-4 text-center shadow-sm">

      <!-- Success -->
      <template v-if="isSuccess">
        <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-5">
          <span class="i-carbon-checkmark-filled text-4xl text-green-500 block" />
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">支付成功</h1>
        <p class="text-sm text-gray-400 mb-1">
          订单号：<span class="font-mono text-gray-600">{{ orderNo }}</span>
        </p>
        <p class="text-sm text-gray-400 mb-8">感谢您的购买，我们会尽快为您安排发货。</p>

        <div class="flex flex-col gap-3">
          <NuxtLink
            :to="`/order/${orderNo}`"
            class="block w-full py-2.5 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:opacity-85 transition-opacity"
          >
            查看订单详情
          </NuxtLink>
          <NuxtLink
            to="/"
            class="block w-full py-2.5 border border-gray-200 text-gray-600 rounded text-sm hover:border-gray-300 transition-colors"
          >
            继续购物
          </NuxtLink>
        </div>
      </template>

      <!-- Fail -->
      <template v-else>
        <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-5">
          <span class="i-carbon-close-filled text-4xl text-red-400 block" />
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">支付失败</h1>
        <p class="text-sm text-gray-400 mb-8">
          支付未能完成，您可以重新尝试或稍后再试。
        </p>

        <div class="flex flex-col gap-3">
          <button
            v-if="orderNo"
            class="w-full py-2.5 bg-[var(--color-primary)] text-white rounded text-sm font-medium hover:opacity-85 transition-opacity"
            @click="retryPay"
          >
            重新支付
          </button>
          <NuxtLink
            to="/order"
            class="block w-full py-2.5 border border-gray-200 text-gray-600 rounded text-sm hover:border-gray-300 transition-colors"
          >
            查看我的订单
          </NuxtLink>
          <NuxtLink
            to="/"
            class="block w-full py-2.5 text-gray-400 text-sm hover:text-gray-600 transition-colors"
          >
            返回首页
          </NuxtLink>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()

const orderNo = computed(() => route.query.orderNo as string || '')
const isSuccess = computed(() => route.query.status === 'success')

function retryPay() {
  if (orderNo.value) {
    router.push(`/pay/${orderNo.value}`)
  }
}
</script>
