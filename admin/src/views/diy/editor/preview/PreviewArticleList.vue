<template>
  <div class="preview-article-list">
    <!-- Loading skeleton -->
    <template v-if="loading">
      <div v-for="i in Math.min(limit || 3, 3)" :key="'sk'+i" class="preview-article-list__skeleton-item">
        <div v-if="layout !== 'text-only'" class="preview-article-list__skeleton-img" :class="{ 'big': layout === 'big-image' }"></div>
        <div class="preview-article-list__skeleton-info">
          <div class="preview-article-list__skeleton-text" style="width:70%"></div>
          <div class="preview-article-list__skeleton-text" style="width:90%;margin-top:6px"></div>
          <div class="preview-article-list__skeleton-text" style="width:40%;margin-top:6px"></div>
        </div>
      </div>
    </template>
    <!-- Real data -->
    <template v-else-if="articleList.length">
      <!-- Big image layout -->
      <template v-if="layout === 'big-image'">
        <div v-for="item in articleList" :key="item.id" class="preview-article-list__item-big">
          <img :src="item.cover || '/storage/diy-defaults/article.png'" class="preview-article-list__big-img" />
          <div class="preview-article-list__big-info">
            <div class="preview-article-list__title">{{ item.title }}</div>
            <div v-if="showSummary && item.summary" class="preview-article-list__summary">{{ item.summary }}</div>
            <div class="preview-article-list__meta">
              <span v-if="showDate">{{ (item.publish_at || '').slice(0, 10) }}</span>
              <span v-if="showViewCount">{{ item.view_count || 0 }}阅读</span>
            </div>
          </div>
        </div>
      </template>
      <!-- Text only layout -->
      <template v-else-if="layout === 'text-only'">
        <div v-for="item in articleList" :key="item.id" class="preview-article-list__item-text">
          <div class="preview-article-list__title">{{ item.title }}</div>
          <div v-if="showSummary && item.summary" class="preview-article-list__summary">{{ item.summary }}</div>
          <div class="preview-article-list__meta">
            <span v-if="showDate">{{ (item.publish_at || '').slice(0, 10) }}</span>
            <span v-if="showViewCount">{{ item.view_count || 0 }}阅读</span>
          </div>
        </div>
      </template>
      <!-- Left image layout (default) -->
      <template v-else>
        <div v-for="item in articleList" :key="item.id" class="preview-article-list__item">
          <img :src="item.cover || '/storage/diy-defaults/article.png'" class="preview-article-list__img" />
          <div class="preview-article-list__info">
            <div class="preview-article-list__title">{{ item.title }}</div>
            <div v-if="showSummary && item.summary" class="preview-article-list__summary">{{ item.summary }}</div>
            <div class="preview-article-list__meta">
              <span v-if="showDate">{{ (item.publish_at || '').slice(0, 10) }}</span>
              <span v-if="showViewCount">{{ item.view_count || 0 }}阅读</span>
            </div>
          </div>
        </div>
      </template>
    </template>
    <!-- Empty placeholder -->
    <div v-else class="preview-article-list__empty">
      <i class="i-svg:file-text text-2xl" />
      <span>暂无文章数据</span>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { diyApi } from '@/api/diy'

const props = defineProps<{
  source?: string; category_id?: number | null; limit?: number;
  layout?: string; showSummary?: boolean; showViewCount?: boolean; showDate?: boolean
}>()

const articleList = ref<any[]>([])
const loading = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null
let lastHash = ''

function getPropsHash(): string {
  return JSON.stringify({ source: props.source, category_id: props.category_id, limit: props.limit })
}

async function fetchData() {
  const hash = getPropsHash()
  if (hash === lastHash) return
  lastHash = hash
  loading.value = true
  try {
    const res = await diyApi.previewData([{
      type: 'article-list',
      props: { source: props.source, category_id: props.category_id, limit: props.limit }
    }])
    articleList.value = res.data?.components?.[0]?.props?.article_list || []
  } catch {
    articleList.value = []
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchData, 500)
}

watch(() => [props.source, props.category_id, props.limit], debouncedFetch, { deep: true })
onMounted(fetchData)
</script>
<style scoped>
.preview-article-list { padding: 8px; }
.preview-article-list__item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
.preview-article-list__img { width: 80px; height: 60px; border-radius: 6px; object-fit: cover; flex-shrink: 0; }
.preview-article-list__info { flex: 1; min-width: 0; }
.preview-article-list__title { font-size: 13px; font-weight: 600; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.preview-article-list__summary { font-size: 11px; color: #999; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.preview-article-list__meta { font-size: 10px; color: #ccc; margin-top: 4px; display: flex; gap: 12px; }
.preview-article-list__item-big { margin-bottom: 12px; }
.preview-article-list__big-img { width: 100%; height: 120px; border-radius: 6px; object-fit: cover; }
.preview-article-list__big-info { padding: 8px 0; }
.preview-article-list__item-text { padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
.preview-article-list__empty { display: flex; flex-direction: column; align-items: center; gap: 4px; color: #c0c4cc; font-size: 12px; padding: 40px 0; }
.preview-article-list__skeleton-item { display: flex; gap: 10px; padding: 10px 0; }
.preview-article-list__skeleton-img { width: 80px; height: 60px; border-radius: 6px; background: #e4e7ed; flex-shrink: 0; animation: skeleton-pulse 1.5s ease-in-out infinite; }
.preview-article-list__skeleton-img.big { width: 100%; height: 120px; }
.preview-article-list__skeleton-info { flex: 1; }
.preview-article-list__skeleton-text { height: 12px; background: #e4e7ed; border-radius: 2px; animation: skeleton-pulse 1.5s ease-in-out infinite; }
@keyframes skeleton-pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
