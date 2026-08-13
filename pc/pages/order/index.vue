<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-900">我的订单</h2>
      <NuxtLink
        to="/order/refund-list"
        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-[var(--color-primary)]"
      >
        <span class="i-carbon-task" /> 我的售后
      </NuxtLink>
    </div>

    <!-- Status tabs -->
    <div class="card mb-4">
      <div class="flex border-b border-gray-100">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          class="order-tab"
          :class="{ 'order-tab--active': activeTab === tab.value }"
          @click="setTab(tab.value)"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Order list -->
      <div class="p-4 min-h-300px">
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-32 bg-gray-100 rounded animate-pulse" />
        </div>

        <template v-else-if="orders.length">
          <OrderCard
            v-for="order in orders"
            :key="order.id"
            :order="order"
            @action="handleAction"
          />
        </template>

        <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
          <span class="i-carbon-receipt text-5xl mb-3 block" />
          <p class="text-sm">暂无{{ activeTabLabel }}订单</p>
          <NuxtLink to="/goods" class="mt-3 text-sm text-[var(--color-primary)] hover:underline">
            去选购商品
          </NuxtLink>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-6 pt-4 border-t border-gray-100">
          <button
            class="pagination-btn"
            :disabled="currentPage <= 1"
            @click="goPage(currentPage - 1)"
          >
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
          <button
            class="pagination-btn"
            :disabled="currentPage >= totalPages"
            @click="goPage(currentPage + 1)"
          >
            下一页 &rsaquo;
          </button>
          <span class="text-sm text-gray-400 ml-2">{{ currentPage }} / {{ totalPages }} 页</span>
        </div>
      </div>
    </div>

    <!-- Cancel modal -->
    <div v-if="cancelModalVisible" class="modal-overlay" @click.self="cancelModalVisible = false">
      <div class="modal-box">
        <h3 class="text-base font-semibold text-gray-800 mb-4">取消订单</h3>
        <p class="text-sm text-gray-500 mb-3">请选择取消原因：</p>
        <div class="space-y-2 mb-4">
          <label
            v-for="reason in cancelReasons"
            :key="reason"
            class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer"
          >
            <input
              type="radio"
              :value="reason"
              v-model="cancelReason"
              class="accent-[var(--color-primary)]"
            />
            {{ reason }}
          </label>
        </div>
        <div class="flex gap-2 justify-end">
          <button class="btn-outline" @click="cancelModalVisible = false">返回</button>
          <button
            class="btn-primary-sm"
            :disabled="!cancelReason || cancelling"
            @click="confirmCancel"
          >
            {{ cancelling ? '处理中...' : '确认取消' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import OrderCard from '~/components/OrderCard.vue'
import { orderApi, type OrderItem, type OrderStatus } from '~/api/order'

definePageMeta({ layout: false, middleware: 'auth' })

const message = useMessage()
const router = useRouter()

const tabs: { label: string; value: '' | OrderStatus }[] = [
  { label: '全部', value: '' },
  { label: '待付款', value: 'pending' },
  { label: '待发货', value: 'paid' },
  { label: '待收货', value: 'shipped' },
  { label: '已完成', value: 'completed' },
]

const PAGE_SIZE = 10

const orders = ref<OrderItem[]>([])
const loading = ref(false)
const total = ref(0)
const activeTab = ref<'' | OrderStatus>('')
const currentPage = ref(1)

const totalPages = computed(() => Math.ceil(total.value / PAGE_SIZE))
const activeTabLabel = computed(() => tabs.find(t => t.value === activeTab.value)?.label ?? '')

const visiblePages = computed(() => {
  const pages: number[] = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function fetchOrders() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page_no: currentPage.value,
      page_size: PAGE_SIZE,
    }
    if (activeTab.value) params.status = activeTab.value
    const res = await orderApi.getOrderList(params)
    if (res.code === 200) {
      orders.value = res.data.list
      total.value = res.data.pagination.total
    }
  } finally {
    loading.value = false
  }
}

function setTab(value: '' | OrderStatus) {
  activeTab.value = value
  currentPage.value = 1
  fetchOrders()
}

function goPage(page: number) {
  currentPage.value = page
  fetchOrders()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Cancel modal
const cancelModalVisible = ref(false)
const cancelReason = ref('')
const cancelling = ref(false)
const cancelTargetOrder = ref<OrderItem | null>(null)

const cancelReasons = [
  '暂时不想买了',
  '商品信息填写有误',
  '重复下单',
  '不想要了',
  '其他原因',
]

function handleAction(type: string, order: OrderItem) {
  if (type === 'detail') {
    router.push(`/order/${order.id}`)
  } else if (type === 'pay') {
    router.push(`/pay/${order.order_no}`)
  } else if (type === 'cancel') {
    cancelTargetOrder.value = order
    cancelReason.value = ''
    cancelModalVisible.value = true
  } else if (type === 'confirm') {
    handleConfirmReceive(order)
  } else if (type === 'review') {
    const item = order.items.find(row => !row.is_reviewed)
    router.push(item ? `/order/review/${order.id}?goods_id=${item.id}` : `/order/${order.id}`)
  }
}

async function handleConfirmReceive(order: OrderItem) {
  try {
    const res = await orderApi.confirmReceive(order.id)
    if (res.code === 200) {
      message.success('确认收货成功')
      fetchOrders()
    }
  } catch {
    // handled by request layer
  }
}

async function confirmCancel() {
  if (!cancelTargetOrder.value || !cancelReason.value) return
  cancelling.value = true
  try {
    const res = await orderApi.cancelOrder(cancelTargetOrder.value.id, { reason: cancelReason.value })
    if (res.code === 200) {
      message.success('订单已取消')
      cancelModalVisible.value = false
      fetchOrders()
    }
  } finally {
    cancelling.value = false
  }
}

onMounted(() => {
  fetchOrders()
})
</script>

<style scoped>
.order-tab {
  padding: 12px 20px;
  font-size: 0.875rem;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  background: none;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s;
}
.order-tab:hover { color: var(--color-primary); }
.order-tab--active {
  color: var(--color-primary);
  font-weight: 500;
  border-bottom-color: var(--color-primary);
}

.btn-outline {
  padding: 6px 16px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid #d1d5db;
  color: #374151;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }

.btn-primary-sm {
  padding: 6px 16px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid var(--color-primary);
  color: #fff;
  background: var(--color-primary);
  cursor: pointer;
  transition: opacity 0.15s;
}
.btn-primary-sm:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary-sm:not(:disabled):hover { opacity: 0.85; }

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

/* Cancel modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-box {
  background: #fff;
  border-radius: 6px;
  padding: 24px;
  width: 360px;
  max-width: calc(100vw - 32px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}
</style>
