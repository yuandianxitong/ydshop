<template>
  <div class="order-block">
    <div class="order-block__meta">
      <div class="flex items-center gap-3 min-w-0">
        <span class="text-sm text-gray-500 flex-shrink-0">订单号：</span>
        <span class="text-sm text-gray-700 font-mono truncate">{{ order.order_no }}</span>
      </div>
      <span class="text-xs text-gray-400 flex-shrink-0 ml-4">{{ order.created_at?.substring(0, 16) }}</span>
    </div>

    <div class="order-block__body">
      <div class="order-block__items">
        <div
          v-for="item in items"
          :key="item.id"
          class="order-item"
        >
          <div class="order-cell order-cell--goods">
            <div class="flex items-start gap-3 min-w-0">
              <NuxtLink
                :to="goodsPath(item)"
                class="block w-16 h-16 flex-shrink-0 rounded overflow-hidden bg-gray-100"
              >
                <img
                  :src="item.goods_image || defaultImg"
                  :alt="item.goods_name"
                  class="w-full h-full object-cover"
                  @error="onImgError"
                />
              </NuxtLink>
              <div class="min-w-0 flex-1">
                <NuxtLink
                  :to="goodsPath(item)"
                  class="text-sm text-gray-700 leading-snug line-clamp-2 hover:text-[var(--color-primary)]"
                >
                  {{ item.goods_name }}
                </NuxtLink>
                <p v-if="item.spec_text" class="text-xs text-gray-400 mt-0.5">规格：{{ item.spec_text }}</p>
              </div>
            </div>
          </div>
          <div class="order-cell order-cell--price">
            <span class="text-sm text-gray-700">¥{{ formatPrice(item.price) }}</span>
          </div>
          <div class="order-cell order-cell--qty">
            <span class="text-sm text-gray-700">{{ item.quantity }}</span>
          </div>
        </div>
      </div>

      <div class="order-cell order-cell--pay">
        <div class="text-sm font-semibold text-gray-900">¥{{ formatPrice(order.pay_amount) }}</div>
        <div class="text-xs text-gray-400 mt-0.5">共 {{ items.length }} 件</div>
      </div>

      <div class="order-cell order-cell--status">
        <span class="status-tag" :class="statusTagClass">{{ statusText }}</span>
      </div>

      <div class="order-cell order-cell--op">
        <button class="btn-outline" @click="emit('action', 'detail', order)">查看详情</button>
        <template v-if="order.status === 'pending'">
          <button class="btn-outline" @click="emit('action', 'cancel', order)">取消订单</button>
          <button class="btn-primary" @click="emit('action', 'pay', order)">立即付款</button>
        </template>
        <template v-else-if="order.status === 'shipped'">
          <button class="btn-primary" @click="emit('action', 'confirm', order)">确认收货</button>
        </template>
        <template v-else-if="order.status === 'completed'">
          <button class="btn-outline" @click="emit('action', 'review', order)">评价</button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { OrderItem, OrderItemRow, OrderStatus } from '~/api/order'

const props = defineProps<{
  order: OrderItem
}>()

const emit = defineEmits<{
  action: [type: string, order: OrderItem]
}>()

const defaultImg = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect fill="%23f3f4f6" width="64" height="64"/></svg>'

const items = computed(() => props.order.items ?? [])

const STATUS_TEXT: Record<OrderStatus, string> = {
  pending: '待付款',
  paid: '待发货',
  shipped: '待收货',
  completed: '已完成',
  cancelled: '已取消',
  closed: '已关闭',
}

const STATUS_CLASS: Record<OrderStatus, string> = {
  pending: 'status-tag--warning',
  paid: 'status-tag--primary',
  shipped: 'status-tag--processing',
  completed: 'status-tag--success',
  cancelled: 'status-tag--error',
  closed: 'status-tag--default',
}

const statusText = computed(() => STATUS_TEXT[props.order.status] ?? '未知')
const statusTagClass = computed(() => STATUS_CLASS[props.order.status] ?? 'status-tag--default')

function formatPrice(val: string | number | undefined | null): string {
  if (val == null || val === '') return '0.00'
  const n = typeof val === 'number' ? val : Number(val)
  if (!Number.isFinite(n)) return '0.00'
  return n.toFixed(2)
}

function goodsPath(item: OrderItemRow) {
  return item.spu_id ? `/goods/${item.spu_id}` : '/goods'
}

function onImgError(e: Event) {
  const img = e.target as HTMLImageElement
  img.src = defaultImg
}
</script>

<style scoped>
.order-block {
  background: #fff;
  border-radius: 2px;
  overflow: hidden;
  margin-top: 12px;
}

.order-block__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  background: #fafafa;
  border-bottom: 1px solid #f0f0f0;
}

.order-block__body {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) 100px 72px 110px 100px 132px;
  gap: 8px;
  align-items: center;
  padding: 16px 20px;
}

.order-block__items {
  grid-column: 1 / 4;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.order-item {
  display: grid;
  grid-template-columns: minmax(240px, 1fr) 100px 72px;
  gap: 8px;
  align-items: center;
}

.order-cell--price,
.order-cell--qty,
.order-cell--pay,
.order-cell--status,
.order-cell--op {
  text-align: center;
}

.order-cell--pay,
.order-cell--status,
.order-cell--op {
  align-self: stretch;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.order-cell--op {
  gap: 8px;
}

.order-cell--op :deep(button) {
  width: 100%;
  padding: 5px 10px;
  font-size: 0.8125rem;
}

.status-tag {
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 2px;
  font-weight: 500;
  white-space: nowrap;
}
.status-tag--warning { background: #fff7e6; color: #d97706; border: 1px solid #fde68a; }
.status-tag--primary { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.status-tag--processing { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
.status-tag--success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.status-tag--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.status-tag--default { background: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb; }
</style>
