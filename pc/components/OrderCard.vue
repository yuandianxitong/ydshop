<template>
  <div class="order-card">
    <!-- Header -->
    <div class="order-card__header">
      <div class="flex items-center gap-3 min-w-0">
        <span class="text-sm text-gray-500 flex-shrink-0">订单号：</span>
        <span class="text-sm text-gray-700 font-mono truncate">{{ order.order_no }}</span>
      </div>
      <div class="flex items-center gap-4 flex-shrink-0 ml-4">
        <span class="text-xs text-gray-400">{{ order.created_at?.substring(0, 16) }}</span>
        <span
          class="status-tag"
          :class="statusTagClass"
        >
          {{ statusText }}
        </span>
      </div>
    </div>

    <!-- Body: items list -->
    <div class="order-card__body">
      <div
        v-for="(item, idx) in displayItems"
        :key="item.id"
        class="order-card__item"
      >
        <div class="relative w-16 h-16 flex-shrink-0 rounded overflow-hidden bg-gray-100">
          <img
            :src="item.goods_image || defaultImg"
            :alt="item.goods_name"
            class="w-full h-full object-cover"
            @error="onImgError"
          />
        </div>
        <div class="flex-1 min-w-0 ml-3">
          <p class="text-sm text-gray-700 leading-snug line-clamp-1">{{ item.goods_name }}</p>
          <p v-if="item.spec_text" class="text-xs text-gray-400 mt-0.5">{{ item.spec_text }}</p>
          <p class="text-sm text-[var(--color-primary)] font-medium mt-1">
            ¥{{ formatPrice(item.price) }}
            <span class="text-gray-400 font-normal ml-1">× {{ item.quantity }}</span>
          </p>
        </div>
      </div>

      <!-- More items hint -->
      <div v-if="extraCount > 0" class="order-card__more">
        等 {{ extraCount }} 件商品
      </div>
    </div>

    <!-- Footer -->
    <div class="order-card__footer">
      <div class="text-sm text-gray-500">
        共 {{ order.items?.length ?? 0 }} 件商品，合计：
        <span class="text-base font-semibold text-gray-900">¥{{ formatPrice(order.pay_amount) }}</span>
      </div>
      <div class="flex items-center gap-2">
        <!-- Detail button always shown -->
        <button class="btn-outline" @click="emit('action', 'detail', order)">查看详情</button>

        <!-- Status-specific actions -->
        <template v-if="order.status === 'pending'">
          <button class="btn-outline" @click="emit('action', 'cancel', order)">取消订单</button>
          <button class="btn-primary-sm" @click="emit('action', 'pay', order)">立即付款</button>
        </template>
        <template v-else-if="order.status === 'shipped'">
          <button class="btn-primary-sm" @click="emit('action', 'confirm', order)">确认收货</button>
        </template>
        <template v-else-if="order.status === 'completed'">
          <button class="btn-outline" @click="emit('action', 'review', order)">评价</button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { OrderItem, OrderStatus } from '~/api/order'

const props = defineProps<{
  order: OrderItem
}>()

const emit = defineEmits<{
  action: [type: string, order: OrderItem]
}>()

const defaultImg = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect fill="%23f3f4f6" width="64" height="64"/></svg>'

// Show only first item, show "等N件" hint if multiple
const displayItems = computed(() => (props.order.items ?? []).slice(0, 1))
const extraCount = computed(() => Math.max(0, (props.order.items?.length ?? 0) - 1))

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

// 后端用 decimal 字符串（如 "12.50"）；这里直接转 yuan 字符串显示
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
</script>

<style scoped>
.order-card {
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  margin-bottom: 12px;
}

.order-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #fafafa;
  border-bottom: 1px solid #f0f0f0;
}

.order-card__body {
  padding: 12px 16px;
}

.order-card__item {
  display: flex;
  align-items: flex-start;
  padding: 6px 0;
}

.order-card__more {
  font-size: 0.8125rem;
  color: #9ca3af;
  padding: 6px 0 2px 76px;
}

.order-card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  border-top: 1px solid #f0f0f0;
}

/* Status tags */
.status-tag {
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 2px;
  font-weight: 500;
}
.status-tag--warning { background: #fff7e6; color: #d97706; border: 1px solid #fde68a; }
.status-tag--primary { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.status-tag--processing { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
.status-tag--success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.status-tag--error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.status-tag--default { background: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb; }

/* Buttons */
.btn-outline {
  padding: 5px 14px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid #d1d5db;
  color: #374151;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}
.btn-outline:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.btn-primary-sm {
  padding: 5px 14px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid var(--color-primary);
  color: #fff;
  background: var(--color-primary);
  cursor: pointer;
  transition: opacity 0.15s;
  white-space: nowrap;
}
.btn-primary-sm:hover { opacity: 0.85; }
</style>
