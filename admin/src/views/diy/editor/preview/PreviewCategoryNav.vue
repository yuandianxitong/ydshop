<template>
  <div class="preview-category-nav" :style="{ gridTemplateColumns: `repeat(${columns || 5}, 1fr)` }">
    <!-- Loading skeleton -->
    <template v-if="loading">
      <div v-for="i in (rows || 2) * (columns || 5)" :key="'sk'+i" class="preview-category-nav__item">
        <div class="preview-category-nav__icon preview-category-nav__skeleton"></div>
        <div class="preview-category-nav__skeleton-text"></div>
      </div>
    </template>
    <!-- Real data (from API or custom items) -->
    <template v-else-if="categoryList.length">
      <div v-for="(item, i) in categoryList" :key="item.id || i" class="preview-category-nav__item">
        <img :src="item.icon || '/storage/diy-defaults/category-icon.png'" class="preview-category-nav__icon preview-category-nav__icon--real" />
        <span>{{ item.name || item.title }}</span>
      </div>
    </template>
    <!-- Fallback placeholder -->
    <template v-else>
      <div v-for="i in (rows || 2) * (columns || 5)" :key="'ph'+i" class="preview-category-nav__item">
        <div class="preview-category-nav__icon"></div>
        <span>分类{{ i }}</span>
      </div>
    </template>
  </div>
</template>
<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { diyApi } from '@/api/diy'

const props = defineProps<{ style?: string; items?: any[]; category_ids?: number[]; rows?: number; columns?: number }>()

const categoryList = ref<any[]>([])
const loading = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null
let lastHash = ''

function getPropsHash(): string {
  return JSON.stringify({ items: props.items, category_ids: props.category_ids, rows: props.rows, columns: props.columns })
}

async function fetchData() {
  // If custom items are configured, use them directly (no API call needed)
  if (props.items && props.items.length > 0) {
    categoryList.value = props.items
    loading.value = false
    lastHash = getPropsHash()
    return
  }
  const hash = getPropsHash()
  if (hash === lastHash) return
  lastHash = hash
  loading.value = true
  try {
    const res = await diyApi.previewData([{
      type: 'category-nav',
      props: { items: props.items, category_ids: props.category_ids, rows: props.rows, columns: props.columns }
    }])
    categoryList.value = res.data?.components?.[0]?.props?.category_list || []
  } catch {
    categoryList.value = []
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchData, 500)
}

watch(() => [props.items, props.category_ids, props.rows, props.columns], debouncedFetch, { deep: true })
onMounted(fetchData)
</script>
<style scoped>
.preview-category-nav { display: grid; gap: 8px; padding: 12px; text-align: center; }
.preview-category-nav__item { display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 11px; color: #666; }
.preview-category-nav__icon { width: 40px; height: 40px; border-radius: 50%; background: #f0f2f5; }
.preview-category-nav__icon--real { object-fit: cover; }
.preview-category-nav__skeleton { animation: skeleton-pulse 1.5s ease-in-out infinite; }
.preview-category-nav__skeleton-text { width: 32px; height: 10px; background: #e4e7ed; border-radius: 2px; animation: skeleton-pulse 1.5s ease-in-out infinite; }
@keyframes skeleton-pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
