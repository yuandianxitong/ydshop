<template>
  <div class="bg-gray-50 min-h-screen pb-16">
    <div class="mx-auto max-w-800px px-4 pt-6">

      <!-- Back -->
      <div class="flex items-center gap-3 mb-6">
        <button class="text-gray-400 hover:text-gray-600" @click="router.back()">
          <span class="i-carbon-arrow-left text-xl" />
        </button>
        <h1 class="text-xl font-bold text-gray-800">物流跟踪</h1>
      </div>

      <!-- Loading skeleton（接口同步调快递鸟可能较慢） -->
      <div v-if="loading" class="space-y-4">
        <div class="bg-white rounded-sm p-5 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-40 mb-3" />
          <div class="h-4 bg-gray-100 rounded w-56" />
        </div>
        <div class="bg-white rounded-sm p-5 animate-pulse space-y-4">
          <div v-for="i in 5" :key="i" class="flex gap-3">
            <div class="w-3 h-3 bg-gray-200 rounded-full mt-1 flex-shrink-0" />
            <div class="flex-1 space-y-2">
              <div class="h-3.5 bg-gray-100 rounded" :style="{ width: `${85 - i * 8}%` }" />
              <div class="h-3 bg-gray-100 rounded w-32" />
            </div>
          </div>
        </div>
        <p class="text-center text-xs text-gray-400">正在查询物流轨迹，请稍候…</p>
      </div>

      <!-- No logistics record -->
      <div v-else-if="!logistics" class="bg-white rounded-sm py-20 text-center text-gray-400">
        <span class="i-carbon-delivery-truck text-4xl block mb-3 mx-auto" />
        <p>该订单暂无物流信息</p>
      </div>

      <template v-else>
        <!-- ===== Express info ===== -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="text-sm text-gray-700 space-y-1.5">
              <p>
                <span class="text-gray-400 inline-block w-20">快递公司</span>
                {{ logistics.express_company || '-' }}
              </p>
              <p>
                <span class="text-gray-400 inline-block w-20">快递单号</span>
                {{ logistics.express_no || '-' }}
                <button
                  v-if="logistics.express_no"
                  class="ml-2 text-xs text-[var(--color-primary)] hover:underline"
                  @click="copyExpressNo"
                >
                  复制
                </button>
              </p>
            </div>
            <NTag :type="statusTagType" :bordered="false">{{ statusText }}</NTag>
          </div>
        </div>

        <!-- ===== Traces timeline ===== -->
        <div class="bg-white rounded-sm p-6">
          <h2 class="text-sm font-semibold text-gray-800 mb-5">物流轨迹</h2>

          <NTimeline v-if="traces.length">
            <NTimelineItem
              v-for="(trace, index) in reversedTraces"
              :key="index"
              :type="index === 0 ? 'success' : 'default'"
              :title="trace.desc"
              :time="trace.time"
            />
          </NTimeline>

          <div v-else class="py-12 text-center text-gray-400">
            <span class="i-carbon-map text-4xl block mb-3 mx-auto" />
            <p class="text-sm">暂无轨迹信息</p>
            <p class="text-xs mt-1.5 text-gray-300">包裹可能尚未被揽收，或物流轨迹暂未同步，请稍后再来查看</p>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup lang="ts">
import { NTimeline, NTimelineItem, NTag, useMessage } from 'naive-ui'
import { orderApi, type TrackingLogistics, type TrackingTrace } from '~/api/order'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const message = useMessage()

const orderId = computed(() => route.params.id as string)

const loading = ref(true)
const logistics = ref<TrackingLogistics | null>(null)
const traces = ref<TrackingTrace[]>([])

/** 后端轨迹按时间正序返回，页面倒序展示（最新在上） */
const reversedTraces = computed(() => [...traces.value].reverse())

const STATUS_TEXT: Record<number, string> = {
  0: '待揽收',
  1: '运输中',
  2: '已签收',
}

const statusText = computed(() =>
  logistics.value ? STATUS_TEXT[logistics.value.status] ?? '未知状态' : '',
)

const statusTagType = computed<'default' | 'success' | 'info' | 'warning'>(() => {
  if (!logistics.value) return 'default'
  if (logistics.value.status === 2) return 'success'
  if (logistics.value.status === 1) return 'info'
  return 'warning'
})

async function copyExpressNo() {
  if (!logistics.value?.express_no) return
  try {
    await navigator.clipboard.writeText(logistics.value.express_no)
    message.success('快递单号已复制')
  } catch {
    message.error('复制失败，请手动复制')
  }
}

async function fetchTracking() {
  loading.value = true
  try {
    const res = await orderApi.getTracking(orderId.value)
    if (res.code === 200) {
      logistics.value = res.data.logistics
      traces.value = res.data.traces || []
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTracking()
})
</script>
