<template>
  <div class="diy-category-nav">
    <NuxtLink v-for="item in (category_list || [])" :key="item.id" :to="`/goods?category_id=${item.id}`" class="diy-category-nav__item">
      <img v-if="item.icon" :src="item.icon" class="diy-category-nav__icon" />
      <div v-else class="diy-category-nav__icon diy-category-nav__icon--empty"></div>
      <span>{{ item.name }}</span>
    </NuxtLink>
  </div>
</template>
<script setup lang="ts">
defineProps<{ style?: string; category_ids?: number[]; rows?: number; columns?: number; category_list?: any[] }>()
</script>
<style scoped>
.diy-category-nav { display: flex; gap: 16px; padding: 16px 0; overflow-x: auto; justify-content: center; }
.diy-category-nav__item { display: flex; flex-direction: column; align-items: center; gap: 8px; text-decoration: none; color: #666; font-size: 13px; min-width: 70px; }
.diy-category-nav__icon { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
.diy-category-nav__icon--empty { background: #f0f2f5; }
</style>
