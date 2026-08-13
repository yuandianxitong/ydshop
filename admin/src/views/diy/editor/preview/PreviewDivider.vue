<template>
  <div v-if="type === 'line'" :style="{ borderTop: `1px solid ${color || '#eee'}`, margin: `${(height || 20) / 2}px 12px` }"></div>
  <div v-else :style="{ height: `${height || 20}px`, background: '#f5f5f5' }"></div>
</template>
<script setup lang="ts">
defineProps<{ type?: string; height?: number; color?: string }>()
</script>
