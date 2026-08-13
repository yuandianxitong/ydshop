<template>
  <div class="diy-float-btn" :class="`pos-${position || 'right-bottom'}`">
    <NuxtLink v-for="(item, i) in (items || [])" :key="i" :to="item.url || '#'" class="diy-float-btn__item">
      {{ item.label }}
    </NuxtLink>
  </div>
</template>
<script setup lang="ts">
defineProps<{ items?: any[]; position?: string }>()
</script>
<style scoped>
.diy-float-btn { position: fixed; z-index: 99; display: flex; flex-direction: column; gap: 10px; }
.pos-right-bottom { right: 24px; bottom: 80px; }
.pos-right-center { right: 24px; top: 50%; transform: translateY(-50%); }
.diy-float-btn__item { width: 48px; height: 48px; border-radius: 50%; background: #409eff; color: #fff; font-size: 11px; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 2px 10px rgba(0,0,0,0.15); }
</style>
