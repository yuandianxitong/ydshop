<template>
  <div class="preview-banner">
    <img :src="items?.[0]?.image || '/storage/diy-defaults/banner.png'" class="preview-banner__img" />
  </div>
</template>
<script setup lang="ts">
defineProps<{ items?: any[]; autoplay?: boolean; interval?: number; height?: number }>()
</script>
<style scoped>
.preview-banner { background: #f0f2f5; min-height: 80px; display: flex; align-items: center; justify-content: center; }
.preview-banner__img { width: 100%; display: block; }
.preview-banner__placeholder { display: flex; flex-direction: column; align-items: center; gap: 4px; color: #c0c4cc; font-size: 12px; padding: 40px 0; }
</style>
