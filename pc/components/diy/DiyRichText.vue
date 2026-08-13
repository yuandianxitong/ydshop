<template>
  <div class="diy-rich-text" v-html="content || ''"></div>
</template>
<script setup lang="ts">
defineProps<{ content?: string }>()
</script>
<style scoped>
.diy-rich-text { padding: 16px 0; font-size: 14px; line-height: 1.8; }
</style>
