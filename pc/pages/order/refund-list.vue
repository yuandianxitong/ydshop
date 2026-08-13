<template>
  <div class="bg-gray-50 min-h-screen pb-12">
    <div class="mx-auto max-w-1200px px-4 pt-5">

      <!-- Back link -->
      <div class="mb-4 flex items-center justify-between">
        <NuxtLink to="/order" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
          <span class="i-carbon-arrow-left mr-1" /> 返回订单列表
        </NuxtLink>
      </div>

      <h1 class="text-xl font-bold text-gray-800 mb-4">我的售后</h1>

      <div class="bg-white rounded-sm">
        <!-- Status tabs -->
        <div class="flex border-b border-gray-100 overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            class="refund-tab"
            :class="{ 'refund-tab--active': activeTab === tab.value }"
            @click="setTab(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="p-4 min-h-300px">
          <!-- Loading -->
          <div v-if="loading" class="space-y-3">
            <div v-for="i in 3" :key="i" class="h-28 bg-gray-100 rounded animate-pulse" />
          </div>

          <!-- List -->
          <template v-else-if="refunds.length">
            <div
              v-for="refund in refunds"
              :key="refund.id"
              class="refund-card"
              @click="router.push(`/order/refund-detail/${refund.id}`)"
            >
              <!-- Card header -->
              <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 text-xs text-gray-500 rounded-t">
                <div class="flex items-center gap-4">
                  <span>售后单号：{{ refund.refund_no }}</span>
                  <span v-if="refund.order?.order_no">订单号：{{ refund.order.order_no }}</span>
                  <span>{{ refund.created_at?.substring(0, 16) }}</span>
                </div>
                <span class="status-badge" :class="statusBadgeClass(refund.status)">
                  {{ REFUND_STATUS_TEXT[refund.status] ?? refund.status }}
                </span>
              </div>

              <!-- Card body -->
              <div class="flex items-center gap-4 px-4 py-3">
                <template v-if="itemOf(refund)">
                  <div class="w-14 h-14 flex-shrink-0 rounded overflow-hidden bg-gray-100">
                    <img
                      :src="itemOf(refund)!.goods_image || defaultImg"
                      :alt="itemOf(refund)!.goods_name"
                      class="w-full h-full object-cover"
                      @error="onImgError"
                    />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-800 line-clamp-1">{{ itemOf(refund)!.goods_name }}</p>
                    <p v-if="itemOf(refund)!.spec_text" class="text-xs text-gray-400 mt-0.5">规格：{{ itemOf(refund)!.spec_text }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ REFUND_TYPE_TEXT[refund.type] ?? refund.type }} · {{ refund.reason }}</p>
                  </div>
                </template>
                <div v-else class="flex-1 min-w-0 text-sm text-gray-400">
                  {{ REFUND_TYPE_TEXT[refund.type] ?? refund.type }} · {{ refund.reason }}
                </div>

                <div class="text-right flex-shrink-0">
                  <p class="text-xs text-gray-400">退款金额</p>
                  <p class="text-base font-semibold text-red-500 mt-0.5">¥{{ formatPrice(refund.refund_amount) }}</p>
                </div>
                <span class="i-carbon-chevron-right text-gray-300 flex-shrink-0" />
              </div>
            </div>
          </template>

          <!-- Empty -->
          <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
            <span class="i-carbon-task-remove text-5xl mb-3 block" />
            <p class="text-sm">暂无{{ activeTabLabel }}售后记录</p>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-6 pt-4 border-t border-gray-100">
            <button class="pagination-btn" :disabled="currentPage <= 1" @click="goPage(currentPage - 1)">
              &lsaquo; 上一页
            </button>
            <button
              v-for="p in visiblePages"
              :key="p"
              class="pagination-btn"
              :class="{ 'pagination-btn--active': currentPage === p }"
              @click="goPage(p)"
            >
              {{ p }}
            </button>
            <button class="pagination-btn" :disabled="currentPage >= totalPages" @click="goPage(currentPage + 1)">
              下一页 &rsaquo;
            </button>
            <span class="text-sm text-gray-400 ml-2">{{ currentPage }} / {{ totalPages }} 页</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import {
  orderApi,
  refundOrderItemOf,
  REFUND_STATUS_TEXT,
  REFUND_TYPE_TEXT,
  type RefundItem,
  type RefundStatus,
} from '~/api/order'

definePageMeta({ middleware: 'auth' })

const router = useRouter()

const tabs: { label: string; value: '' | RefundStatus }[] = [
  { label: '全部', value: '' },
  { label: '待审核', value: 'pending' },
  { label: '待退货', value: 'approved' },
  { label: '退货中', value: 'returning' },
  { label: '退款中', value: 'refunding' },
  { label: '已退款', value: 'refunded' },
  { label: '已拒绝', value: 'rejected' },
]

const PAGE_SIZE = 10

const refunds = ref<RefundItem[]>([])
const loading = ref(false)
const total = ref(0)
const activeTab = ref<'' | RefundStatus>('')
const currentPage = ref(1)

const defaultImg = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56"><rect fill="%23f3f4f6" width="56" height="56"/></svg>'

const totalPages = computed(() => Math.ceil(total.value / PAGE_SIZE))
const activeTabLabel = computed(() => {
  const label = tabs.find(t => t.value === activeTab.value)?.label ?? ''
  return label === '全部' ? '' : label
})

const visiblePages = computed(() => {
  const pages: number[] = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

const itemOf = refundOrderItemOf

const STATUS_BADGE: Record<RefundStatus, string> = {
  pending: 'badge--warning',
  approved: 'badge--primary',
  returning: 'badge--processing',
  received: 'badge--processing',
  refunding: 'badge--processing',
  retryable_failed: 'badge--error',
  manual_review: 'badge--warning',
  refunded: 'badge--success',
  rejected: 'badge--error',
}

function statusBadgeClass(status: RefundStatus): string {
  return STATUS_BADGE[status] ?? 'badge--default'
}

function formatPrice(val: string | number | undefined | null): string {
  if (val == null || val === '') return '0.00'
  const n = typeof val === 'number' ? val : Number(val)
  if (!Number.isFinite(n)) return '0.00'
  return n.toFixed(2)
}

function onImgError(e: Event) {
  const img = e.target as HTMLImageElement
  img.src = defaultImg
}

async function fetchRefunds() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page: currentPage.value,
      limit: PAGE_SIZE,
    }
    if (activeTab.value) params.status = activeTab.value
    const res = await orderApi.getRefundList(params)
    if (res.code === 200) {
      refunds.value = res.data.list
      total.value = res.data.pagination.total
    }
  } finally {
    loading.value = false
  }
}

function setTab(value: '' | RefundStatus) {
  activeTab.value = value
  currentPage.value = 1
  fetchRefunds()
}

function goPage(page: number) {
  currentPage.value = page
  fetchRefunds()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  fetchRefunds()
})
</script>

<style scoped>
.refund-tab {
  padding: 12px 20px;
  font-size: 0.875rem;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  background: none;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s;
}
.refund-tab:hover { color: var(--color-primary); }
.refund-tab--active {
  color: var(--color-primary);
  font-weight: 500;
  border-bottom-color: var(--color-primary);
}

.refund-card {
  border: 1px solid #f3f4f6;
  border-radius: 4px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: box-shadow 0.15s, border-color 0.15s;
}
.refund-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
.refund-card:last-child { margin-bottom: 0; }

.status-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 10px;
  border-radius: 3px;
}
.badge--warning { background: #fff7e6; color: #d97706; }
.badge--primary { background: #eff6ff; color: #2563eb; }
.badge--processing { background: #f0f9ff; color: #0284c7; }
.badge--success { background: #f0fdf4; color: #16a34a; }
.badge--error { background: #fef2f2; color: #dc2626; }
.badge--default { background: #f9fafb; color: #6b7280; }

.pagination-btn {
  padding: 5px 12px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid #e5e7eb;
  color: #4b5563;
  background: #fff;
  cursor: pointer;
  min-width: 2rem;
  transition: all 0.15s;
}
.pagination-btn:hover:not(:disabled) { border-color: var(--color-primary); color: var(--color-primary); }
.pagination-btn--active { background: var(--color-primary) !important; border-color: var(--color-primary) !important; color: #fff !important; }
.pagination-btn:disabled { opacity: 0.4; cursor: not-allowed; }
</style>
