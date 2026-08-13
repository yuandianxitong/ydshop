<template>
  <div class="preview-notice">
    <i class="i-svg:bell text-base text-[#f59e0b]" />
    <span v-if="items && items.length">{{ items[0].text }}</span>
    <span v-else>这是一条公告消息</span>
  </div>
</template>
<script setup lang="ts">
defineProps<{ source?: string; items?: any[]; speed?: number }>()
</script>
<style scoped>
.preview-notice { display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: #fffbeb; font-size: 13px; color: #92400e; }
</style>
