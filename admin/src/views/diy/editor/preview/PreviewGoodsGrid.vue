<template>
  <div class="preview-goods-grid">
    <div class="preview-goods-grid__list" :style="{ gridTemplateColumns: `repeat(${columns || 2}, 1fr)` }">
      <!-- Loading skeleton -->
      <template v-if="loading">
        <div v-for="i in Math.min(limit || 4, 4)" :key="'sk'+i" class="preview-goods-grid__item">
          <div class="preview-goods-grid__img preview-goods-grid__skeleton"></div>
          <div class="preview-goods-grid__info">
            <div class="preview-goods-grid__skeleton-text" style="width:80%"></div>
            <div class="preview-goods-grid__skeleton-text" style="width:40%;margin-top:4px"></div>
          </div>
        </div>
      </template>
      <!-- Real data -->
      <template v-else-if="goodsList.length">
        <div v-for="item in goodsList" :key="item.id" class="preview-goods-grid__item">
          <img :src="item.image || item.images?.[0] || '/storage/diy-defaults/goods.png'" class="preview-goods-grid__img preview-goods-grid__img--real" />
          <div class="preview-goods-grid__info">
            <div class="preview-goods-grid__name">{{ item.name }}</div>
            <div class="preview-goods-grid__price">¥{{ item.min_price }}</div>
          </div>
        </div>
      </template>
      <!-- Fallback placeholder -->
      <template v-else>
        <div v-for="i in Math.min(limit || 4, 4)" :key="'ph'+i" class="preview-goods-grid__item">
          <div class="preview-goods-grid__img"></div>
          <div class="preview-goods-grid__info">
            <div class="preview-goods-grid__name">商品名称</div>
            <div class="preview-goods-grid__price">¥99.00</div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { diyApi } from '@/api/diy'

const props = defineProps<{
  title?: string; source?: string; goods_ids?: number[];
  category_id?: number; tag?: string; limit?: number; columns?: number
}>()

const goodsList = ref<any[]>([])
const loading = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null
let lastHash = ''

function getPropsHash(): string {
  return JSON.stringify({ source: props.source, goods_ids: props.goods_ids, category_id: props.category_id, tag: props.tag, limit: props.limit })
}

async function fetchData() {
  const hash = getPropsHash()
  if (hash === lastHash) return
  lastHash = hash
  loading.value = true
  try {
    const res = await diyApi.previewData([{
      type: 'goods-grid',
      props: { source: props.source, goods_ids: props.goods_ids, category_id: props.category_id, tag: props.tag, limit: props.limit }
    }])
    goodsList.value = res.data?.components?.[0]?.props?.goods_list || []
  } catch {
    goodsList.value = []
  } finally {
    loading.value = false
  }
}

function debouncedFetch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchData, 500)
}

watch(() => [props.source, props.goods_ids, props.category_id, props.tag, props.limit], debouncedFetch, { deep: true })
onMounted(fetchData)
</script>
<style scoped>
.preview-goods-grid { padding: 0 8px 8px; }
.preview-goods-grid__list { display: grid; gap: 8px; }
.preview-goods-grid__item { background: #f9f9f9; border-radius: 6px; overflow: hidden; }
.preview-goods-grid__img { padding-top: 100%; background: linear-gradient(135deg, #f5f7fa, #e4e7ed); display: block; position: relative; }
.preview-goods-grid__img--real { padding-top: 0; width: 100%; aspect-ratio: 1; object-fit: cover; }
.preview-goods-grid__info { padding: 6px 8px; }
.preview-goods-grid__name { font-size: 12px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.preview-goods-grid__price { font-size: 14px; color: #e74c3c; font-weight: 600; margin-top: 2px; }
.preview-goods-grid__skeleton { animation: skeleton-pulse 1.5s ease-in-out infinite; }
.preview-goods-grid__skeleton-text { height: 12px; background: #e4e7ed; border-radius: 2px; animation: skeleton-pulse 1.5s ease-in-out infinite; }
@keyframes skeleton-pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
