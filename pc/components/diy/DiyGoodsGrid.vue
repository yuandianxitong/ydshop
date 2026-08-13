<template>
  <div class="diy-goods-grid">
    <div class="diy-goods-grid__list" :style="{ gridTemplateColumns: `repeat(${columns || 4}, 1fr)` }">
      <GoodsCard v-for="item in (goods_list || [])" :key="item.id" :goods="item" />
    </div>
  </div>
</template>
<script setup lang="ts">
import GoodsCard from '~/components/GoodsCard.vue'
defineProps<{ title?: string; source?: string; goods_ids?: number[]; category_id?: number; tag?: string; limit?: number; columns?: number; goods_list?: any[] }>()
</script>
<style scoped>
.diy-goods-grid { padding: 0 0 20px; }
.diy-goods-grid__list { display: grid; gap: 14px; }
</style>
