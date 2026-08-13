<template>
  <div v-if="src" class="diy-video" :style="{ aspectRatio: aspectRatio || '16 / 9' }">
    <video :src="src" :poster="poster" :autoplay="autoplay" :loop="loop" :muted="muted" controls :style="{ borderRadius: `${borderRadius ?? 8}px`, objectFit: objectFit || 'contain' }" />
  </div>
</template>

<script setup lang="ts">
defineProps<{ src?: string; poster?: string; autoplay?: boolean; loop?: boolean; muted?: boolean; aspectRatio?: string; borderRadius?: number; objectFit?: 'contain' | 'cover' | 'fill' }>()
</script>

<style scoped>
.diy-video { width: 100%; padding: 16px 0; }.diy-video video { width: 100%; height: 100%; background: #000; }
</style>
