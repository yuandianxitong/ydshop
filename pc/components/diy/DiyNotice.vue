<template>
  <div class="diy-notice">
    <span class="diy-notice__icon">📢</span>
    <span>{{ displayItems[0]?.text || '暂无公告' }}</span>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ source?: string; items?: any[]; speed?: number }>()
const displayItems = computed(() => props.items && props.items.length ? props.items : [{ text: '暂无公告' }])
</script>
<style scoped>
.diy-notice { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #fffbeb; border-radius: 6px; font-size: 14px; color: #92400e; }
.diy-notice__icon { font-size: 16px; }
</style>
