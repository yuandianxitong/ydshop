<template>
  <div class="bg-gray-50 min-h-screen pb-12">
    <div class="mx-auto max-w-1200px px-4 pt-5">

      <!-- Back link -->
      <div class="mb-4 flex items-center justify-between">
        <NuxtLink to="/order" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
          <span class="i-carbon-arrow-left mr-1" /> 返回订单列表
        </NuxtLink>
        <!-- 订单详情接口暂不返回单条退款记录，统一从「我的售后」入口查看售后进度 -->
        <NuxtLink to="/order/refund-list" class="inline-flex items-center text-sm text-gray-500 hover:text-[var(--color-primary)]">
          <span class="i-carbon-task mr-1" /> 我的售后
        </NuxtLink>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="space-y-4">
        <div v-for="i in 4" :key="i" class="bg-white rounded-sm p-5 h-32 animate-pulse" />
      </div>

      <!-- Error -->
      <div v-else-if="!order" class="bg-white rounded-sm py-20 text-center text-gray-400">
        <span class="i-carbon-warning text-4xl block mb-3 mx-auto" />
        <p>订单不存在</p>
      </div>

      <template v-else>

        <!-- ===== Status banner with step indicator ===== -->
        <div class="bg-white rounded-sm p-6 mb-4">
          <div class="flex items-center justify-between mb-6">
            <div>
              <span class="status-badge" :class="statusBadgeClass">{{ statusText }}</span>
              <span class="ml-3 text-sm text-gray-400">订单号：{{ order.order_no }}</span>
            </div>
            <span class="text-sm text-gray-400">{{ order.created_at?.substring(0, 16) }}</span>
          </div>

          <!-- Step progress -->
          <div class="flex items-center">
            <template v-for="(step, idx) in steps" :key="step.status">
              <div class="flex flex-col items-center">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                  :class="stepCircleClass(step.status)"
                >
                  {{ idx + 1 }}
                </div>
                <span
                  class="text-xs mt-1.5 whitespace-nowrap"
                  :class="isStepDone(step.status) ? 'text-[var(--color-primary)] font-medium' : 'text-gray-400'"
                >
                  {{ step.label }}
                </span>
              </div>
              <div
                v-if="idx < steps.length - 1"
                class="flex-1 h-0.5 mx-2 mt-[-14px]"
                :class="isStepLineActive(idx) ? 'bg-[var(--color-primary)]' : 'bg-gray-200'"
              />
            </template>
          </div>
        </div>

        <!-- ===== Address info ===== -->
        <div v-if="order.address_snapshot" class="bg-white rounded-sm p-5 mb-4">
          <h3 class="section-title">收货地址</h3>
          <div class="text-sm text-gray-700 mt-3">
            <span class="font-medium mr-3">{{ order.address_snapshot.name }}</span>
            <span class="text-gray-500 mr-4">{{ order.address_snapshot.phone }}</span>
            <span>{{ order.address_snapshot.province }}{{ order.address_snapshot.city }}{{ order.address_snapshot.district }}{{ order.address_snapshot.detail }}</span>
          </div>
        </div>

        <!-- ===== Items table ===== -->
        <div class="bg-white rounded-sm mb-4 overflow-hidden">
          <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="section-title">商品信息</h3>
          </div>
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 text-gray-500 text-xs">
                <th class="py-3 px-5 text-left font-medium">商品</th>
                <th class="py-3 px-4 text-center font-medium">规格</th>
                <th class="py-3 px-4 text-center font-medium">单价</th>
                <th class="py-3 px-4 text-center font-medium">数量</th>
                <th class="py-3 px-4 text-center font-medium">小计</th>
                <th class="py-3 px-4 text-center font-medium">操作</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in order.items"
                :key="item.id"
                class="border-b border-gray-50 last:border-0"
              >
                <td class="py-4 px-5">
                  <div class="flex items-center gap-3">
                    <div class="w-14 h-14 flex-shrink-0 rounded overflow-hidden bg-gray-100">
                      <img
                        :src="item.goods_image || defaultImg"
                        :alt="item.goods_name"
                        class="w-full h-full object-cover"
                        @error="onImgError"
                      />
                    </div>
                    <span class="text-gray-700 line-clamp-2 max-w-48">{{ item.goods_name }}</span>
                  </div>
                </td>
                <td class="py-4 px-4 text-center text-gray-500 text-xs">{{ item.spec_text || '-' }}</td>
                <td class="py-4 px-4 text-center">¥{{ formatPrice(item.price) }}</td>
                <td class="py-4 px-4 text-center">{{ item.quantity }}</td>
                <td class="py-4 px-4 text-center font-medium">¥{{ formatPrice(item.total_amount) }}</td>
                <td class="py-4 px-4 text-center">
                  <button
                    v-if="canApplyRefund"
                    class="text-xs text-[var(--color-primary)] hover:underline"
                    @click="handleRefund(item)"
                  >
                    申请退款
                  </button>
                  <button
                    v-if="order.status === 'completed' && !item.is_reviewed"
                    class="text-xs text-gray-500 hover:text-gray-700 hover:underline ml-1"
                    @click="handleReview(item)"
                  >
                    评价
                  </button>
                  <span v-if="!canApplyRefund && order.status !== 'completed'" class="text-xs text-gray-300">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ===== Amount breakdown ===== -->
        <div class="bg-white rounded-sm p-5 mb-4">
          <h3 class="section-title mb-3">费用明细</h3>
          <div class="max-w-xs ml-auto space-y-2 text-sm">
            <div class="flex justify-between text-gray-500">
              <span>商品金额</span>
              <span>¥{{ formatPrice(order.goods_amount) }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
              <span>运费</span>
              <span>¥{{ formatPrice(order.freight_amount) }}</span>
            </div>
            <div v-if="Number(order.discount_amount) > 0" class="flex justify-between text-gray-500">
              <span>优惠减免</span>
              <span class="text-[var(--color-primary)]">- ¥{{ formatPrice(order.discount_amount) }}</span>
            </div>
            <div class="flex justify-between border-t border-gray-100 pt-2 font-semibold text-gray-900">
              <span>实付金额</span>
              <span class="text-[var(--color-primary)]">¥{{ formatPrice(order.pay_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- ===== Action buttons ===== -->
        <div class="bg-white rounded-sm p-5 flex justify-end gap-3">
          <NuxtLink to="/order" class="btn-outline">返回列表</NuxtLink>
          <button
            v-if="order.status === 'pending'"
            class="btn-outline"
            @click="cancelModalVisible = true"
          >
            取消订单
          </button>
          <button
            v-if="order.status === 'pending'"
            class="btn-primary"
            @click="handlePay"
          >
            立即付款
          </button>
          <NuxtLink
            v-if="order.status === 'shipped' || order.status === 'completed'"
            :to="`/order/logistics/${order.id}`"
            class="btn-outline"
          >
            查看物流
          </NuxtLink>
          <button
            v-if="order.status === 'shipped'"
            class="btn-primary"
            @click="handleConfirm"
          >
            确认收货
          </button>
        </div>

      </template>
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
            class="btn-primary"
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
import { orderApi, type OrderItem, type OrderItemRow, type OrderStatus } from '~/api/order'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const router = useRouter()
const message = useMessage()

const orderId = computed(() => route.params.id as string)

const order = ref<OrderItem | null>(null)
const loading = ref(true)

const defaultImg = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56"><rect fill="%23f3f4f6" width="56" height="56"/></svg>'

const STATUS_TEXT: Record<OrderStatus, string> = {
  pending: '待付款',
  paid: '待发货',
  shipped: '待收货',
  completed: '已完成',
  cancelled: '已取消',
  closed: '已关闭',
}

const STATUS_BADGE: Record<OrderStatus, string> = {
  pending: 'badge--warning',
  paid: 'badge--primary',
  shipped: 'badge--processing',
  completed: 'badge--success',
  cancelled: 'badge--error',
  closed: 'badge--default',
}

const statusText = computed(() =>
  order.value ? STATUS_TEXT[order.value.status] ?? '未知' : '',
)
const statusBadgeClass = computed(() =>
  order.value ? STATUS_BADGE[order.value.status] ?? 'badge--default' : 'badge--default',
)

const steps: { status: OrderStatus; label: string }[] = [
  { status: 'pending', label: '待付款' },
  { status: 'paid', label: '待发货' },
  { status: 'shipped', label: '待收货' },
  { status: 'completed', label: '已完成' },
]
const STATUS_ORDER: Record<OrderStatus, number> = {
  pending: 1,
  paid: 2,
  shipped: 3,
  completed: 4,
  cancelled: -1,
  closed: -1,
}

function isStepDone(stepStatus: OrderStatus): boolean {
  if (!order.value) return false
  const cur = STATUS_ORDER[order.value.status]
  if (cur < 0) return false
  return cur >= STATUS_ORDER[stepStatus]
}

function stepCircleClass(stepStatus: OrderStatus): string {
  if (isStepDone(stepStatus)) return 'bg-[var(--color-primary)] text-white'
  return 'bg-gray-100 text-gray-400'
}

function isStepLineActive(idx: number): boolean {
  const next = steps[idx + 1]
  return next ? isStepDone(next.status) : false
}

const canApplyRefund = computed(() => {
  if (!order.value) return false
  return ['paid', 'shipped', 'completed'].includes(order.value.status)
})

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

async function fetchOrder() {
  loading.value = true
  try {
    const res = await orderApi.getOrderDetail(orderId.value)
    if (res.code === 200) order.value = res.data
  } finally {
    loading.value = false
  }
}

// Pay
function handlePay() {
  if (!order.value) return
  router.push(`/pay/${order.value.order_no}`)
}

// Confirm receive
async function handleConfirm() {
  try {
    const res = await orderApi.confirmReceive(orderId.value)
    if (res.code === 200) {
      message.success('确认收货成功')
      fetchOrder()
    }
  } catch {
    // handled by request layer
  }
}

// Refund
function handleRefund(item: OrderItemRow) {
  router.push(`/order/refund/${orderId.value}?goods_id=${item.id}`)
}

// Review
function handleReview(item: OrderItemRow) {
  router.push(`/order/review/${orderId.value}?goods_id=${item.id}`)
}

// Cancel
const cancelModalVisible = ref(false)
const cancelReason = ref('')
const cancelling = ref(false)

const cancelReasons = [
  '暂时不想买了',
  '商品信息填写有误',
  '重复下单',
  '不想要了',
  '其他原因',
]

async function confirmCancel() {
  if (!order.value || !cancelReason.value) return
  cancelling.value = true
  try {
    const res = await orderApi.cancelOrder(order.value.id, { reason: cancelReason.value })
    if (res.code === 200) {
      message.success('订单已取消')
      cancelModalVisible.value = false
      fetchOrder()
    }
  } finally {
    cancelling.value = false
  }
}

onMounted(() => {
  fetchOrder()
})
</script>

<style scoped>
.section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #111827;
}

.status-badge {
  display: inline-block;
  font-size: 0.875rem;
  font-weight: 600;
  padding: 3px 12px;
  border-radius: 3px;
}
.badge--warning { background: #fff7e6; color: #d97706; }
.badge--primary { background: #eff6ff; color: #2563eb; }
.badge--processing { background: #f0f9ff; color: #0284c7; }
.badge--success { background: #f0fdf4; color: #16a34a; }
.badge--error { background: #fef2f2; color: #dc2626; }
.badge--default { background: #f9fafb; color: #6b7280; }

.btn-outline {
  padding: 7px 18px;
  font-size: 0.875rem;
  border-radius: 2px;
  border: 1px solid #d1d5db;
  color: #374151;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
  text-decoration: none;
  display: inline-block;
}
.btn-outline:hover { border-color: var(--color-primary); color: var(--color-primary); }

.btn-primary {
  padding: 7px 18px;
  font-size: 0.875rem;
  border-radius: 2px;
  border: 1px solid var(--color-primary);
  color: #fff;
  background: var(--color-primary);
  cursor: pointer;
  transition: opacity 0.15s;
}
.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary:not(:disabled):hover { opacity: 0.85; }

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
