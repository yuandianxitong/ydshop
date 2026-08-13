<template>
  <div class="preview-image-ad" :class="`layout-${layout || 'single'}`">
    <template v-if="items && items.length">
      <div v-for="(item, i) in items" :key="i" class="preview-image-ad__item">
        <img :src="item.image || '/storage/diy-defaults/image-ad.png'" style="width:100%;height:100%;object-fit:cover" />
      </div>
    </template>
    <div v-else class="preview-image-ad__placeholder">
      <i class="i-lucide:image mr-1" /> 图片广告
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ items?: any[]; layout?: string }>()
</script>
<style scoped>
.preview-image-ad { padding: 8px; display: flex; gap: 8px; min-height: 80px; }
.layout-single .preview-image-ad__item { width: 100%; height: 100px; }
.layout-double .preview-image-ad__item { width: 50%; height: 100px; }
.layout-triple .preview-image-ad__item { width: 33.3%; height: 100px; }
.preview-image-ad__placeholder { width: 100%; display: flex; align-items: center; justify-content: center; gap: 4px; background: #f0f2f5; color: #c0c4cc; font-size: 12px; border-radius: 4px; min-height: 80px; }
.preview-image-ad__item { background: #f0f2f5; border-radius: 4px; overflow: hidden; }
</style>
