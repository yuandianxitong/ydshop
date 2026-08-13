<template>
  <div class="preview-float-button" :class="`pos-${position || 'right-bottom'}`">
    <div v-for="(item, i) in items && items.length ? items : [{ label: '客服' }]" :key="i" class="preview-float-button__item">
      {{ item.label || '按钮' }}
    </div>
  </div>
</template>
<script setup lang="ts">
defineProps<{ items?: any[]; position?: string }>()
</script>
<style scoped>
.preview-float-button { position: absolute; display: flex; flex-direction: column; gap: 8px; z-index: 5; }
.pos-right-bottom { right: 12px; bottom: 60px; }
.pos-right-center { right: 12px; top: 50%; transform: translateY(-50%); }
.preview-float-button__item { width: 44px; height: 44px; border-radius: 50%; background: var(--el-color-primary); color: #fff; font-size: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
</style>
