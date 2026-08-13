<template>
  <div class="diy-nav-grid" :style="{ gridTemplateColumns: `repeat(${columns || 4}, 1fr)` }">
    <NuxtLink v-for="(item, i) in (items || [])" :key="i" :to="item.url || '#'" class="diy-nav-grid__item">
      <img v-if="item.icon" :src="item.icon" class="diy-nav-grid__icon" />
      <div v-else class="diy-nav-grid__icon diy-nav-grid__icon--empty"></div>
      <span>{{ item.title }}</span>
    </NuxtLink>
  </div>
</template>
<script setup lang="ts">
defineProps<{ items?: any[]; columns?: number; rows?: number }>()
</script>
<style scoped>
.diy-nav-grid { display: grid; gap: 16px; padding: 20px 0; text-align: center; }
.diy-nav-grid__item { display: flex; flex-direction: column; align-items: center; gap: 6px; text-decoration: none; color: #666; font-size: 13px; }
.diy-nav-grid__icon { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
.diy-nav-grid__icon--empty { background: #f0f2f5; }
</style>
