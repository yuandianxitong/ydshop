<template>
  <div class="mx-auto max-w-1200px px-4 pt-5 pb-12">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-900">我的订单</h2>
      <NuxtLink
        to="/order/refund-list"
        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-[var(--color-primary)]"
      >
        <span class="i-carbon-task" /> 我的售后
      </NuxtLink>
    </div>

    <div class="flex border-b border-gray-200 mb-4">
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

    <div v-if="loading" class="bg-white rounded-sm p-4 space-y-3">
      <div v-for="i in 3" :key="i" class="h-32 bg-gray-100 rounded animate-pulse" />
    </div>

    <template v-else-if="orders.length">
      <div class="bg-white rounded-sm overflow-hidden">
        <div class="order-row order-row--header">
          <div class="order-cell order-cell--goods">商品信息</div>
          <div class="order-cell order-cell--price">单价</div>
          <div class="order-cell order-cell--qty">数量</div>
          <div class="order-cell order-cell--pay">实付款</div>
          <div class="order-cell order-cell--status">交易状态</div>
          <div class="order-cell order-cell--op">操作</div>
        </div>
      </div>

      <OrderCard
        v-for="order in orders"
        :key="order.id"
        :order="order"
        @action="handleAction"
      />

      <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-6">
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
    </template>

    <div v-else class="bg-white rounded-sm flex flex-col items-center justify-center py-20 text-gray-400">
      <span class="i-carbon-receipt text-5xl mb-3 block" />
      <p class="text-sm">暂无{{ activeTabLabel }}订单</p>
      <NuxtLink to="/goods" class="mt-3 text-sm text-[var(--color-primary)] hover:underline">
        去选购商品
      </NuxtLink>
    </div>

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
            class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
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

definePageMeta({ middleware: 'auth' })

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

.order-row {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) 100px 72px 110px 100px 132px;
  gap: 8px;
  align-items: center;
  padding: 12px 20px;
}
.order-row--header {
  background: #fafafa;
  font-size: 0.8125rem;
  color: #6b7280;
}
.order-cell--price,
.order-cell--qty,
.order-cell--pay,
.order-cell--status,
.order-cell--op {
  text-align: center;
}

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
