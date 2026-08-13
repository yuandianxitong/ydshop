<template>
  <section v-if="article_list?.length" class="article-list" :class="`layout-${layout || 'left-image'}`">
    <NuxtLink v-for="article in article_list" :key="article.id" :to="`/article/${article.id}`" class="article">
      <img v-if="layout !== 'text-only' && article.cover" :src="article.cover" :alt="article.title" />
      <div>
        <h3>{{ article.title }}</h3>
        <p v-if="showSummary !== false && article.summary">{{ article.summary }}</p>
        <small><span v-if="showDate !== false">{{ (article.publish_at || '').slice(0, 10) }}</span><span v-if="showViewCount !== false">{{ article.view_count || 0 }} 阅读</span></small>
      </div>
    </NuxtLink>
  </section>
</template>

<script setup lang="ts">
defineProps<{ layout?: string; showSummary?: boolean; showViewCount?: boolean; showDate?: boolean; article_list?: any[] }>()
</script>

<style scoped>
.article-list { padding: 8px 0; }
.article { display: flex; gap: 16px; padding: 16px 0; color: inherit; border-bottom: 1px solid #eee; }
.article img { width: 180px; height: 120px; border-radius: 8px; object-fit: cover; }
.article h3 { margin: 0; font-size: 17px; }.article p { color: #666; margin: 9px 0; line-height: 1.6; }
.article small { display: flex; gap: 18px; color: #aaa; }
.layout-big-image { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 20px; }
.layout-big-image .article { display: block; }.layout-big-image .article img { width: 100%; height: 220px; margin-bottom: 10px; }
</style>
