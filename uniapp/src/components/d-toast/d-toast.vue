<template>
  <view v-if="visible" class="d-toast" :class="`d-toast--${variant}`">
    <text v-if="iconChar" class="d-toast__icon">{{ iconChar }}</text>
    <text class="d-toast__msg">{{ message }}</text>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'

const visible = ref(false)
const message = ref('')
const variant = ref<'info' | 'success' | 'error'>('info')

const iconChar = computed(() => {
  if (variant.value === 'success') return '✓'
  if (variant.value === 'error') return '✕'
  return ''
})

let timer: ReturnType<typeof setTimeout> | null = null

function show(msg: string, opts?: { variant?: 'info' | 'success' | 'error'; duration?: number }) {
  message.value = msg
  variant.value = opts?.variant || 'info'
  visible.value = true
  if (timer) clearTimeout(timer)
  timer = setTimeout(() => { visible.value = false }, opts?.duration ?? 2000)
}

onUnmounted(() => {
  if (timer) clearTimeout(timer)
})

defineExpose({ show })
</script>

<style lang="scss" scoped>

.d-toast {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  min-width: 240rpx;
  max-width: 70vw;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  font-size: var(--font-base);
  text-align: center;
  z-index: 999;

  &__icon {
    display: block;
    font-size: var(--font-2xl);
    margin-bottom: var(--space-1);
  }
  &__msg { display: block; }

  &--success { background: rgba(16, 185, 129, 0.92); }
  &--error   { background: rgba(239, 68, 68, 0.92); }
}
</style>
