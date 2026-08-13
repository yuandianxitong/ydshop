<template>
  <div class="bg-gray-50 min-h-screen">
    <div class="mx-auto max-w-1200px px-4 py-6">

      <!-- Search bar -->
      <div class="bg-white rounded-sm px-5 py-4 mb-4">
        <div class="flex gap-3 items-center max-w-2xl">
          <div class="relative flex-1">
            <span class="i-carbon-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base" />
            <input
              v-model="keyword"
              type="text"
              placeholder="搜索商品..."
              class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-sm outline-none focus:border-[var(--color-primary)] transition-colors"
              @keydown.enter="doSearch"
            />
          </div>
          <button
            class="px-5 py-2 bg-[var(--color-primary)] text-white text-sm rounded-sm hover:opacity-90 transition-opacity flex-shrink-0"
            @click="doSearch"
          >
            搜索
          </button>
        </div>
      </div>

      <!-- Sort & result count -->
      <div class="bg-white rounded-sm px-5 py-3 mb-4 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-500">排序：</span>
          <button
            v-for="s in sortOptions"
            :key="s.value"
            class="filter-tag"
            :class="{ active: activeSort === s.value }"
            @click="setSort(s.value)"
          >
            {{ s.label }}
          </button>
        </div>
        <span class="text-sm text-gray-400">
          <template v-if="route.query.keyword">
            "{{ route.query.keyword }}" 相关商品 {{ total }} 件
          </template>
          <template v-else>共 {{ total }} 件商品</template>
        </span>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="goods-grid">
        <div v-for="i in 20" :key="i" class="rounded-sm overflow-hidden bg-white">
          <div class="bg-gray-200 animate-pulse" style="aspect-ratio: 1/1;" />
          <div class="p-3 space-y-2">
            <div class="h-3 bg-gray-200 rounded animate-pulse" />
            <div class="h-3 bg-gray-200 rounded animate-pulse w-2/3" />
          </div>
        </div>
      </div>

      <!-- Goods grid -->
      <div v-else-if="goods.length" class="goods-grid">
        <GoodsCard v-for="item in goods" :key="item.id" :goods="item" />
      </div>
      <div v-else class="bg-white rounded-sm py-20 text-center text-gray-400">
        <span class="i-carbon-search text-5xl block mb-3 mx-auto" />
        <p>未找到相关商品</p>
        <p v-if="route.query.keyword" class="text-xs mt-1">
          尝试更换关键词或
          <NuxtLink to="/goods" class="text-[var(--color-primary)] hover:underline">浏览全部商品</NuxtLink>
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 mt-6 py-2">
        <button
          class="pagination-btn"
          :disabled="currentPage <= 1"
          @click="goPage(currentPage - 1)"
        >
          &lsaquo; 上一页
        </button>
        <button
          v-for="p in visiblePages"
          :key="p"
          class="pagination-btn"
          :class="{ 'pagination-btn--active': currentPage === p }"
          @click="goPage(p)"
        >
          {{ p }}
        </button>
        <button
          class="pagination-btn"
          :disabled="currentPage >= totalPages"
          @click="goPage(currentPage + 1)"
        >
          下一页 &rsaquo;
        </button>
        <span class="text-sm text-gray-400 ml-2">第 {{ currentPage }} / {{ totalPages }} 页</span>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { goodsApi, type GoodsItem } from '~/api/goods'

const route = useRoute()
const router = useRouter()

const sortOptions = [
  { label: '综合', value: '' },
  { label: '价格↑', value: 'price_asc' },
  { label: '价格↓', value: 'price_desc' },
  { label: '销量', value: 'sales' },
  { label: '最新', value: 'newest' },
]

const PAGE_SIZE = 20

const keyword = ref((route.query.keyword as string) || '')
const goods = ref<GoodsItem[]>([])
const loading = ref(true)
const total = ref(0)

const activeSort = computed(() => (route.query.sort as string) || '')
const currentPage = computed(() => Number(route.query.page) || 1)
const totalPages = computed(() => Math.ceil(total.value / PAGE_SIZE))

const visiblePages = computed(() => {
  const pages: number[] = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function fetchGoods() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page_no: currentPage.value,
      page_size: PAGE_SIZE,
    }
    if (route.query.keyword) params.keyword = route.query.keyword
    if (activeSort.value) params.sort = activeSort.value
    const res = await goodsApi.getGoodsList(params)
    if (res.code === 200) {
      goods.value = res.data.list
      total.value = res.data.pagination.total
    }
  } finally {
    loading.value = false
  }
}

function doSearch() {
  const kw = keyword.value.trim()
  router.push({ path: '/search', query: kw ? { keyword: kw } : {} })
}

function setSort(sort: string) {
  router.push({ query: { ...route.query, sort: sort || undefined, page: undefined } })
}

function goPage(page: number) {
  router.push({ query: { ...route.query, page: page > 1 ? page : undefined } })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

watch(
  () => route.query,
  (q) => {
    keyword.value = (q.keyword as string) || ''
    fetchGoods()
  }
)

onMounted(() => {
  fetchGoods()
})
</script>

<style scoped>
.filter-tag {
  padding: 3px 10px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid #e5e7eb;
  color: #4b5563;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
}
.filter-tag:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.filter-tag.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}

.goods-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

@media (max-width: 900px) {
  .goods-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.pagination-btn {
  padding: 5px 12px;
  font-size: 0.8125rem;
  border-radius: 2px;
  border: 1px solid #e5e7eb;
  color: #4b5563;
  background: #fff;
  cursor: pointer;
  transition: all 0.15s;
  min-width: 2rem;
}
.pagination-btn:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.pagination-btn--active {
  background: var(--color-primary) !important;
  border-color: var(--color-primary) !important;
  color: #fff !important;
}
.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
</style>
