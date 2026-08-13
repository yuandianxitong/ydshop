<template>
  <view class="d-delivery-chip">
    <view
      v-for="opt in options"
      :key="opt.value"
      class="d-delivery-chip__item"
      :class="{ 'is-active': modelValue === opt.value }"
      @tap="$emit('update:modelValue', opt.value)"
    >
      {{ opt.label }}
    </view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type DeliveryType = 'express' | 'merchant' | 'pickup'

const props = defineProps<{
  modelValue: DeliveryType
  available: DeliveryType[]
}>()

defineEmits<{ 'update:modelValue': [v: DeliveryType] }>()

const allLabels: Record<DeliveryType, string> = {
  express: '快递配送',
  merchant: '即时送达',
  pickup: '门店自提',
}

const options = computed(() => props.available.map((v) => ({
  value: v,
  label: allLabels[v],
})))
</script>

<style lang="scss" scoped>
.d-delivery-chip {
  display: flex;
  gap: 16rpx;
  padding: 16rpx 0;

  &__item {
    padding: 8rpx 24rpx;
    border-radius: 24rpx;
    background: var(--color-bg-3, #f4f4f5);
    color: var(--color-text-2, #71717a);
    font-size: 26rpx;

    &.is-active {
      background: var(--color-primary, #2979ff);
      color: #fff;
    }
  }
}
</style>
