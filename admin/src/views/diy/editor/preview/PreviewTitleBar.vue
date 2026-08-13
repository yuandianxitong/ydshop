<template>
  <div class="preview-title-bar" :style="{ textAlign: (align || 'left') as any }">
    <div class="preview-title-bar__main">
      <span class="preview-title-bar__title">{{ title || '标题' }}</span>
      <span v-if="subtitle" class="preview-title-bar__subtitle">{{ subtitle }}</span>
    </div>
    <span v-if="more_text" class="preview-title-bar__more">
      <span>{{ more_text }}</span>
      <span class="preview-title-bar__arrow">›</span>
    </span>
  </div>
</template>
<script setup lang="ts">
defineProps<{ title?: string; subtitle?: string; align?: string; more_url?: string; more_text?: string }>()
</script>
<style scoped>
.preview-title-bar { padding: 12px; display: flex; align-items: center; justify-content: space-between; }
.preview-title-bar__title { font-size: 16px; font-weight: 600; color: #333; }
.preview-title-bar__subtitle { font-size: 12px; color: #999; margin-left: 8px; }
.preview-title-bar__more { display: inline-flex; align-items: center; font-size: 12px; color: #999; }
.preview-title-bar__arrow { margin-left: 3px; font-size: 14px; line-height: 1; }
</style>
