<template>
  <NRadioGroup :value="modelValue" @update:value="onChange">
    <NRadioButton
      v-for="type in options"
      :key="type.value"
      :value="type.value"
    >
      <span class="inline-flex items-center gap-1.5">
        <span :class="type.icon" />
        {{ type.label }}
      </span>
    </NRadioButton>
  </NRadioGroup>
</template>

<script setup lang="ts">
import { NRadioGroup, NRadioButton } from 'naive-ui'
import type { DeliveryType } from '~/api/order'

const props = defineProps<{
  /** 可用配送方式（商品 delivery_modes 交集） */
  available: string[]
  modelValue: DeliveryType
}>()

const emit = defineEmits<{
  'update:modelValue': [value: DeliveryType]
}>()

const LABELS: Record<DeliveryType, { label: string; icon: string }> = {
  express: { label: '快递配送', icon: 'i-carbon-delivery-truck' },
  merchant: { label: '同城配送', icon: 'i-carbon-scooter' },
  pickup: { label: '到店自提', icon: 'i-carbon-store' },
}

const ORDER: DeliveryType[] = ['express', 'merchant', 'pickup']

const options = computed(() =>
  ORDER
    .filter(t => props.available.includes(t))
    .map(t => ({ value: t, ...LABELS[t] }))
)

function onChange(value: DeliveryType) {
  emit('update:modelValue', value)
}
</script>
