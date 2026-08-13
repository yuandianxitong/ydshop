<template>
  <div class="preview-rich-text" v-if="content" v-html="content"></div>
  <div v-else class="preview-rich-text preview-rich-text--empty">
    <i class="i-svg:file-text mr-1" /> 富文本内容
  </div>
</template>
<script setup lang="ts">
defineProps<{ content?: string }>()
</script>
<style scoped>
.preview-rich-text { padding: 12px; font-size: 13px; line-height: 1.6; }
.preview-rich-text--empty { display: flex; align-items: center; justify-content: center; gap: 4px; min-height: 60px; color: #c0c4cc; background: #f9f9f9; }
</style>
