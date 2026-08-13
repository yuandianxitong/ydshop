<template>
  <div class="preview-search-bar" :style="{ background: bg_color || '#f5f5f5' }">
    <div class="preview-search-bar__input" :style="{ borderRadius: `${border_radius || 20}px` }">
      <i class="i-svg:search text-sm" />
      <span>{{ placeholder || '搜索商品' }}</span>
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ placeholder?: string; border_radius?: number; bg_color?: string }>()
</script>
<style scoped>
.preview-search-bar { padding: 8px 12px; }
.preview-search-bar__input { display: flex; align-items: center; gap: 6px; padding: 8px 12px; background: #fff; color: #c0c4cc; font-size: 13px; }
</style>
