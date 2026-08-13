<template>
  <div class="diy-title-bar" :style="{ textAlign: align ?? 'left' }">
    <div>
      <span class="diy-title-bar__title">{{ title }}</span>
      <span v-if="subtitle" class="diy-title-bar__subtitle">{{ subtitle }}</span>
    </div>
    <NuxtLink v-if="more_url" :to="more_url" class="diy-title-bar__more">{{ more_text || '查看更多' }} ></NuxtLink>
  </div>
</template>
<script setup lang="ts">
defineProps<{ title?: string; subtitle?: string; align?: 'left' | 'center' | 'right'; more_url?: string; more_text?: string }>()
</script>
<style scoped>
.diy-title-bar { display: flex; align-items: center; justify-content: space-between; padding: 24px 0; }
.diy-title-bar__title { font-size: 22px; font-weight: 600; color: #333; }
.diy-title-bar__subtitle { font-size: 14px; color: #999; margin-left: 8px; }
.diy-title-bar__more { font-size: 14px; color: #999; text-decoration: none; }
</style>
