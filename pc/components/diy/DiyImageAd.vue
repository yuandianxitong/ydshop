<template>
  <div class="diy-image-ad" :class="`layout-${layout || 'single'}`">
    <NuxtLink v-for="(item, i) in items" :key="i" :to="item.url || '#'" class="diy-image-ad__item">
      <img :src="item.image" class="diy-image-ad__img" />
    </NuxtLink>
  </div>
</template>
<script setup lang="ts">
defineProps<{ items?: any[]; layout?: string }>()
</script>
<style scoped>
.diy-image-ad { display: flex; gap: 12px; padding: 12px 0; }
.layout-single .diy-image-ad__item { width: 100%; }
.layout-double .diy-image-ad__item { width: 50%; }
.layout-triple .diy-image-ad__item { width: 33.3%; }
.diy-image-ad__item { border-radius: 8px; overflow: hidden; }
.diy-image-ad__img { width: 100%; height: 160px; object-fit: cover; display: block; }
</style>
