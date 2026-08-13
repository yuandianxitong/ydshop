<template>
  <!-- Style 1: Tree sidebar + goods grid (original) -->
  <div v-if="categoryStyle === 'style1'" class="bg-gray-50 min-h-screen">
    <div class="mx-auto max-w-1200px px-4 py-6">
      <div class="flex gap-4 items-start">

        <!-- Left sidebar: category tree -->
        <div class="w-56 flex-shrink-0 bg-white rounded-sm overflow-hidden sticky top-4">
          <div class="px-4 py-3 border-b border-gray-100">
            <span class="text-sm font-semibold text-gray-700">全部分类</span>
          </div>
          <div v-if="loadingCategories" class="py-8 text-center text-gray-400 text-sm">加载中...</div>
          <div v-else class="py-2">
            <div v-for="cat in categories" :key="cat.id">
              <!-- Parent category -->
              <div
                class="category-item"
                :class="{ active: selectedId === cat.id }"
                @click="selectCategory(cat)"
              >
                <span
                  v-if="cat.children && cat.children.length"
                  class="i-carbon-chevron-right text-xs transition-transform mr-1 flex-shrink-0"
                  :class="{ 'rotate-90': expandedIds.includes(cat.id) }"
                />
                <span v-else class="w-4 flex-shrink-0" />
                <span class="truncate">{{ cat.name }}</span>
              </div>

              <!-- Children (expandable) -->
              <div
                v-if="cat.children && cat.children.length && expandedIds.includes(cat.id)"
                class="pl-4"
              >
                <div
                  v-for="child in cat.children"
                  :key="child.id"
                  class="category-item text-xs"
                  :class="{ active: selectedId === child.id }"
                  @click="selectCategory(child)"
                >
                  <span class="i-carbon-dot-mark text-gray-300 text-xs mr-1 flex-shrink-0" />
                  <span class="truncate">{{ child.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: goods grid -->
        <div class="flex-1 min-w-0">
          <!-- Breadcrumb & current category -->
          <div class="bg-white rounded-sm px-5 py-3 mb-4 flex items-center gap-2 text-sm">
            <NuxtLink to="/category" class="text-gray-400 hover:text-[var(--color-primary)] transition-colors">全部分类</NuxtLink>
            <template v-if="selectedCategory">
              <span class="i-carbon-chevron-right text-gray-300 text-xs" />
              <span class="text-gray-700 font-medium">{{ selectedCategory.name }}</span>
            </template>
            <span class="ml-auto text-gray-400 text-xs">共 {{ total }} 件商品</span>
          </div>

          <!-- Sort bar -->
          <div class="bg-white rounded-sm px-5 py-3 mb-4 flex items-center gap-2">
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

          <!-- Loading skeleton -->
          <div v-if="loading" class="goods-grid">
            <div v-for="i in 12" :key="i" class="rounded-sm overflow-hidden bg-white">
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
            <span class="i-carbon-catalog text-5xl block mb-3 mx-auto" />
            该分类暂无商品
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
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Style 2: Top tabs + icon grid -->
  <div v-else-if="categoryStyle === 'style2'" class="bg-gray-50 min-h-screen">
    <div class="mx-auto max-w-1200px px-4 py-6">
      <!-- Top tabs -->
      <div class="bg-white rounded-sm mb-4">
        <div class="flex border-b border-gray-100 px-4 overflow-x-auto">
          <button
            v-for="cat in categories"
            :key="cat.id"
            class="tab-btn"
            :class="{ 'tab-btn--active': selectedId === cat.id }"
            @click="router.push({ query: { id: cat.id } })"
          >
            {{ cat.name }}
          </button>
        </div>
      </div>
      <!-- Sub-category icon grid -->
      <div class="bg-white rounded-sm p-6">
        <div v-if="selectedCategory?.children?.length" class="grid grid-cols-6 gap-6">
          <NuxtLink
            v-for="sub in selectedCategory.children"
            :key="sub.id"
            :to="{ path: '/category', query: { id: sub.id } }"
            class="flex flex-col items-center gap-2 group"
          >
            <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden group-hover:bg-blue-50 transition-colors">
              <img v-if="sub.icon" :src="sub.icon" class="w-full h-full object-cover" />
              <span v-else class="i-carbon-tag text-2xl text-gray-300" />
            </div>
            <span class="text-xs text-gray-600 group-hover:text-[var(--color-primary)] transition-colors">{{ sub.name }}</span>
          </NuxtLink>
        </div>
        <div v-else class="py-20 text-center text-gray-400">
          <span class="i-carbon-catalog text-5xl block mb-3 mx-auto" />
          该分类暂无子分类
        </div>
      </div>
    </div>
  </div>

  <!-- Style 3: Sidebar + sub tabs + goods -->
  <div v-else class="bg-gray-50 min-h-screen">
    <div class="mx-auto max-w-1200px px-4 py-6">
      <div class="flex gap-4 items-start">
        <!-- Left sidebar (simpler) -->
        <div class="w-48 flex-shrink-0 bg-white rounded-sm overflow-hidden sticky top-4">
          <div class="py-2">
            <div
              v-for="cat in categories"
              :key="cat.id"
              class="category-item-s3"
              :class="{ active: selectedId === cat.id || cat.children?.some(c => c.id === selectedId) }"
              @click="router.push({ query: { id: cat.id } })"
            >
              {{ cat.name }}
            </div>
          </div>
        </div>

        <div class="flex-1 min-w-0">
          <!-- Sub-category pill tabs -->
          <div v-if="selectedCategory?.children?.length" class="bg-white rounded-sm px-4 py-3 mb-4 flex flex-wrap gap-2">
            <button
              class="pill-tab"
              :class="{ 'pill-tab--active': !route.query.sub }"
              @click="router.push({ query: { id: route.query.id, sort: route.query.sort || undefined } })"
            >
              全部
            </button>
            <button
              v-for="sub in selectedCategory.children"
              :key="sub.id"
              class="pill-tab"
              :class="{ 'pill-tab--active': Number(route.query.sub) === sub.id }"
              @click="router.push({ query: { ...route.query, sub: sub.id, page: undefined } })"
            >
              {{ sub.name }}
            </button>
          </div>

          <!-- Loading skeleton -->
          <div v-if="loading" class="goods-grid">
            <div v-for="i in 12" :key="i" class="rounded-sm overflow-hidden bg-white">
              <div class="bg-gray-200 animate-pulse" style="aspect-ratio: 1/1;" />
              <div class="p-3 space-y-2">
                <div class="h-3 bg-gray-200 rounded animate-pulse" />
                <div class="h-3 bg-gray-200 rounded animate-pulse w-2/3" />
              </div>
            </div>
          </div>
          <div v-else-if="goods.length" class="goods-grid">
            <GoodsCard v-for="item in goods" :key="item.id" :goods="item" />
          </div>
          <div v-else class="bg-white rounded-sm py-20 text-center text-gray-400">
            <span class="i-carbon-catalog text-5xl block mb-3 mx-auto" />
            该分类暂无商品
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
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { goodsApi, type GoodsItem, type CategoryItem } from '~/api/goods'
import { get } from '~/composables/useRequest'

const route = useRoute()
const router = useRouter()

// Fetch category page style from global config
const categoryStyle = ref('style1')
onMounted(async () => {
  try {
    const res = await get<Record<string, any>>('/api/common/config', undefined, false)
    if (res.code === 200 && res.data?.category_page_style_uniapp) {
      categoryStyle.value = res.data.category_page_style_uniapp
    }
  } catch {
    // Use default style1
  }
})

const sortOptions = [
  { label: '综合', value: '' },
  { label: '价格↑', value: 'price_asc' },
  { label: '价格↓', value: 'price_desc' },
  { label: '销量', value: 'sales' },
  { label: '最新', value: 'newest' },
]

const PAGE_SIZE = 20

const categories = ref<CategoryItem[]>([])
const goods = ref<GoodsItem[]>([])
const loadingCategories = ref(true)
const loading = ref(true)
const total = ref(0)
const expandedIds = ref<number[]>([])

const selectedId = computed(() => Number(route.query.id) || 0)
const activeSort = computed(() => (route.query.sort as string) || '')
const currentPage = computed(() => Number(route.query.page) || 1)
const totalPages = computed(() => Math.ceil(total.value / PAGE_SIZE))

const selectedCategory = computed(() => {
  for (const cat of categories.value) {
    if (cat.id === selectedId.value) return cat
    if (cat.children) {
      const child = cat.children.find(c => c.id === selectedId.value)
      if (child) return child
    }
  }
  return null
})

const visiblePages = computed(() => {
  const pages: number[] = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

async function fetchCategories() {
  try {
    const res = await goodsApi.getCategoryTree()
    if (res.code === 200) {
      categories.value = res.data
      // Auto-expand category that contains selected child
      if (selectedId.value) {
        for (const cat of res.data) {
          if (cat.id === selectedId.value || cat.children?.some(c => c.id === selectedId.value)) {
            if (!expandedIds.value.includes(cat.id)) expandedIds.value.push(cat.id)
          }
        }
      }
    }
  } finally {
    loadingCategories.value = false
  }
}

async function fetchGoods() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page_no: currentPage.value,
      page_size: PAGE_SIZE,
    }
    // Style 3: sub-category filter via route.query.sub
    const subId = Number(route.query.sub) || 0
    if (subId) {
      params.category_id = subId
    } else if (selectedId.value) {
      params.category_id = selectedId.value
    }
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

function selectCategory(cat: CategoryItem) {
  // Toggle expand if parent has children
  if (cat.children && cat.children.length) {
    const idx = expandedIds.value.indexOf(cat.id)
    if (idx >= 0) expandedIds.value.splice(idx, 1)
    else expandedIds.value.push(cat.id)
  }
  router.push({ query: { id: cat.id || undefined, page: undefined } })
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
  () => fetchGoods()
)

onMounted(() => {
  fetchCategories()
  fetchGoods()
})
</script>

<style scoped>
/* Style 1: category tree sidebar items */
.category-item {
  display: flex;
  align-items: center;
  padding: 7px 16px;
  font-size: 0.8125rem;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.15s;
}
.category-item:hover {
  background: #f9fafb;
  color: var(--color-primary);
}
.category-item.active {
  background: #eff6ff;
  color: var(--color-primary);
  font-weight: 500;
}

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
  grid-template-columns: repeat(3, 1fr);
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

/* Style 2: Top tabs */
.tab-btn {
  padding: 10px 16px;
  font-size: 0.875rem;
  color: #4b5563;
  white-space: nowrap;
  flex-shrink: 0;
  border-bottom: 2px solid transparent;
  transition: all 0.15s;
  background: none;
  cursor: pointer;
}
.tab-btn:hover { color: var(--color-primary); }
.tab-btn--active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
  font-weight: 500;
}

/* Style 3: Sidebar items */
.category-item-s3 {
  padding: 9px 16px;
  font-size: 0.8125rem;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.15s;
}
.category-item-s3:hover { background: #f9fafb; color: var(--color-primary); }
.category-item-s3.active {
  background: var(--color-primary);
  color: #fff;
  font-weight: 500;
  border-radius: 0 16px 16px 0;
  margin-right: 4px;
}

/* Style 3: Pill tabs */
.pill-tab {
  padding: 4px 14px;
  font-size: 0.8125rem;
  border-radius: 14px;
  background: #f3f4f6;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.15s;
  border: none;
}
.pill-tab:hover { background: #e5e7eb; }
.pill-tab--active { background: var(--color-primary); color: #fff; }
</style>
