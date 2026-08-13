<template>
  <div class="config-panel">
    <el-empty description="无高级设置" :image-size="60" />
  </div>
</template>
<script setup lang="ts">
defineProps<{ modelValue: Record<string, any> }>()
</script>
