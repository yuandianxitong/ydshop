<template>
  <div class="preview-nav-grid" :style="{ gridTemplateColumns: `repeat(${columns || 4}, 1fr)` }">
    <div v-for="(item, i) in displayItems" :key="i" class="preview-nav-grid__item">
      <div class="preview-nav-grid__icon">
        <img :src="item.icon || '/storage/diy-defaults/nav-icon.png'" style="width:100%;height:100%;object-fit:cover;border-radius:50%" />
      </div>
      <span>{{ item.title || `导航${i + 1}` }}</span>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ items?: any[]; columns?: number; rows?: number }>()
const displayItems = computed(() => {
    const total = (props.columns || 4) * (props.rows || 1)
    if (props.items && props.items.length) return props.items.slice(0, total)
    return Array.from({ length: total }, (_, i) => ({ title: `导航${i + 1}`, icon: '' }))
})
</script>
<style scoped>
.preview-nav-grid { display: grid; gap: 8px; padding: 12px; text-align: center; }
.preview-nav-grid__item { display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 11px; color: #666; }
.preview-nav-grid__icon { width: 44px; height: 44px; border-radius: 50%; background: #f0f2f5; }
</style>
