<template>
  <div class="diy-search-bar" :style="{ background: bg_color || '#f5f5f5' }">
    <NuxtLink to="/goods" class="diy-search-bar__input" :style="{ borderRadius: `${border_radius || 20}px` }">
      {{ placeholder || '搜索商品' }}
    </NuxtLink>
  </div>
</template>
<script setup lang="ts">
defineProps<{ placeholder?: string; border_radius?: number; bg_color?: string }>()
</script>
<style scoped>
.diy-search-bar { padding: 12px 0; }
.diy-search-bar__input { display: flex; align-items: center; padding: 10px 20px; background: #fff; color: #c0c4cc; font-size: 14px; text-decoration: none; border: 1px solid #e4e7ed; }
</style>
