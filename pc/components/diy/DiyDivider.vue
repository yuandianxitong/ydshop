<template>
  <div v-if="type === 'line'" :style="{ borderTop: `1px solid ${color || '#eee'}`, margin: `${(height || 20) / 2}px 0` }"></div>
  <div v-else :style="{ height: `${height || 20}px` }"></div>
</template>
<script setup lang="ts">
defineProps<{ type?: string; height?: number; color?: string }>()
</script>
