<template>
  <view class="d-amount-stepper">
    <view
      class="d-amount-stepper__btn d-amount-stepper__btn--minus"
      :class="{ 'd-amount-stepper__btn--disabled': isMinDisabled }"
      @tap="dec"
    >−</view>
    <input
      class="d-amount-stepper__input"
      type="number"
      :value="String(modelValue)"
      :disabled="disabledInput"
      @blur="onBlur"
    />
    <view
      class="d-amount-stepper__btn d-amount-stepper__btn--plus"
      :class="{ 'd-amount-stepper__btn--disabled': isMaxDisabled }"
      @tap="inc"
    >+</view>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: number
    min?: number
    max?: number
    step?: number
    disabledInput?: boolean   // 禁用输入框（仅按钮可改）
  }>(),
  {
    min: 1,
    max: 99,
    step: 1,
    disabledInput: false,
  }
)

const emit = defineEmits<{ 'update:modelValue': [value: number]; change: [value: number] }>()

const isMinDisabled = computed(() => props.modelValue <= props.min)
const isMaxDisabled = computed(() => props.modelValue >= props.max)

function clamp(v: number) {
  if (Number.isNaN(v)) return props.min
  return Math.min(props.max, Math.max(props.min, v))
}

function dec() {
  if (isMinDisabled.value) return
  const v = clamp(props.modelValue - props.step)
  emit('update:modelValue', v)
  emit('change', v)
}

function inc() {
  if (isMaxDisabled.value) return
  const v = clamp(props.modelValue + props.step)
  emit('update:modelValue', v)
  emit('change', v)
}

function onBlur(e: any) {
  // WeChat MP: e.detail.value; H5: e.target.value; fallback: 当前值
  const raw = Number(e?.detail?.value ?? e?.target?.value ?? props.modelValue)
  const v = clamp(raw)
  // 防止虚假更新：值未变就不 emit（避免触发上层 API 重复调用）
  if (v !== props.modelValue) {
    emit('update:modelValue', v)
    emit('change', v)
  }
}
</script>

<style lang="scss" scoped>

.d-amount-stepper {
  display: inline-flex;
  align-items: center;
  height: 56rpx;
  border-radius: var(--radius-sm);
  overflow: hidden;
  background: var(--color-bg-3);
  flex-shrink: 0;
  box-sizing: border-box;

  &__btn {
    width: 56rpx;
    min-width: 56rpx;
    height: 56rpx;
    line-height: 56rpx;
    text-align: center;
    font-size: var(--font-md);
    color: var(--color-text-1);
    flex-shrink: 0;
    box-sizing: border-box;

    &:active { background: var(--color-border-1); }
    &--disabled { color: var(--color-text-3); &:active { background: var(--color-bg-3); } }
  }
  &__input {
    width: 88rpx;
    min-width: 88rpx;
    max-width: 88rpx;
    height: 56rpx;
    line-height: 56rpx;
    text-align: center;
    background: var(--color-bg-1);
    font-size: var(--font-base);
    color: var(--color-text-1);
    flex-shrink: 0;
    box-sizing: border-box;
    padding: 0;
  }
}
</style>
