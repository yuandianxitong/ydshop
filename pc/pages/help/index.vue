<template>
  <div class="bg-gray-50 min-h-screen pb-12">
    <div class="mx-auto max-w-1200px px-4 py-6">
      <!-- 顶部搜索条 -->
      <div class="bg-white rounded-sm p-4 mb-4">
        <div class="flex items-center gap-3">
          <span class="i-lucide:circle-help text-xl text-[var(--color-primary)]" />
          <h1 class="text-lg font-semibold text-gray-800">帮助中心</h1>
          <div class="flex-1" />
          <div class="flex items-center gap-2">
            <input
              v-model="keyword"
              class="search-input"
              placeholder="搜索帮助"
              @keyup.enter="onSearch"
            />
            <button class="search-btn" @click="onSearch">搜索</button>
          </div>
        </div>
      </div>

      <!-- 主体两栏 -->
      <div class="flex gap-4">
        <!-- 左栏：分类树 -->
        <aside class="w-60 flex-shrink-0 bg-white rounded-sm p-3">
          <button
            class="cat-item"
            :class="{ 'cat-item--active': activeCat === 0 }"
            @click="setCategory(0)"
          >
            所有问题
          </button>
          <button
            v-for="c in categories"
            :key="c.id"
            class="cat-item"
            :class="{ 'cat-item--active': activeCat === c.id }"
            @click="setCategory(c.id)"
          >
            {{ c.name }}
          </button>
        </aside>

        <!-- 右栏：列表 -->
        <div class="flex-1 min-w-0 bg-white rounded-sm">
          <div class="px-5 py-3 border-b border-gray-100 text-sm text-gray-500">
            当前分类：<span class="text-gray-800 font-medium">{{ currentCatName }}</span>
            <span v-if="keyword" class="ml-2">· 搜索「{{ keyword }}」</span>
          </div>

          <div v-if="loading" class="px-5 py-10 text-center text-gray-400 text-sm">加载中...</div>
          <div v-else-if="!list.length" class="px-5 py-12 text-center text-gray-400 text-sm">该分类下暂无帮助</div>

          <ul v-else class="divide-y divide-gray-100">
            <li v-for="item in list" :key="item.id" class="px-5 py-3 hover:bg-gray-50 transition-colors">
              <NuxtLink :to="`/help/${item.id}`" class="flex items-center gap-3 group">
                <span class="i-lucide:chevron-right text-gray-300 group-hover:text-[var(--color-primary)]" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-800 group-hover:text-[var(--color-primary)] truncate">{{ item.title }}</p>
                  <p v-if="item.summary" class="text-xs text-gray-400 truncate mt-0.5">{{ item.summary }}</p>
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0">阅读 {{ item.view_count }}</span>
              </NuxtLink>
            </li>
          </ul>

          <!-- 分页 -->
          <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 py-4 border-t border-gray-100">
            <button class="page-btn" :disabled="currentPage <= 1" @click="goPage(currentPage - 1)">‹</button>
            <span class="text-sm text-gray-500">第 {{ currentPage }} / {{ totalPages }} 页</span>
            <button class="page-btn" :disabled="currentPage >= totalPages" @click="goPage(currentPage + 1)">›</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { helpApi, type HelpCategoryInfo, type HelpItem } from '~/api/help'

useHead({ title: '帮助中心 - 元点Shop' })

const route = useRoute()
const router = useRouter()

const categories = ref<HelpCategoryInfo[]>([])
const list = ref<HelpItem[]>([])
const loading = ref(true)
const total = ref(0)
const pageSize = 20

const keyword = ref((route.query.keyword as string) || '')
const activeCat = computed(() => Number(route.query.category_id) || 0)
const currentPage = computed(() => Number(route.query.page) || 1)
const totalPages = computed(() => Math.ceil(total.value / pageSize))

const currentCatName = computed(() => {
  if (!activeCat.value) return '所有问题'
  return categories.value.find(c => c.id === activeCat.value)?.name || '-'
})

async function loadCategories() {
  const res = await helpApi.getCategories()
  if (res.code === 200) categories.value = res.data || []
}

async function loadList() {
  loading.value = true
  try {
    const params: any = { page_no: currentPage.value, page_size: pageSize }
    if (activeCat.value) params.category_id = activeCat.value
    if (keyword.value.trim()) params.keyword = keyword.value.trim()
    const res = await helpApi.getList(params)
    if (res.code === 200) {
      list.value = res.data.list || []
      total.value = res.data.pagination?.total ?? 0
    }
  } finally {
    loading.value = false
  }
}

function setCategory(id: number) {
  router.push({ path: '/help', query: { category_id: id || undefined, page: undefined, keyword: keyword.value || undefined } })
}

function onSearch() {
  router.push({ path: '/help', query: { category_id: activeCat.value || undefined, page: undefined, keyword: keyword.value || undefined } })
}

function goPage(p: number) {
  router.push({ path: '/help', query: { ...route.query, page: p > 1 ? p : undefined } })
}

watch(() => route.query, loadList)

onMounted(async () => { await loadCategories(); await loadList() })
</script>

<style scoped>
.search-input {
  width: 220px; height: 32px; padding: 0 12px;
  border: 1px solid #e5e7eb; border-radius: 2px;
  font-size: 13px; outline: 0;
}
.search-input:focus { border-color: var(--color-primary); }
.search-btn {
  height: 32px; padding: 0 16px;
  background: var(--color-primary); color: #fff;
  border: 0; border-radius: 2px; cursor: pointer; font-size: 13px;
}
.cat-item {
  display: block; width: 100%; text-align: left;
  padding: 8px 12px; font-size: 14px; color: #4b5563;
  background: transparent; border: 0; cursor: pointer;
  border-radius: 4px; transition: all 0.15s;
}
.cat-item:hover { background: #f3f4f6; color: var(--color-primary); }
.cat-item--active { background: var(--color-primary); color: #fff; }
.cat-item--active:hover { background: var(--color-primary); color: #fff; }
.page-btn {
  width: 32px; height: 32px; border: 1px solid #e5e7eb; background: #fff;
  border-radius: 2px; cursor: pointer; font-size: 14px;
}
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
</style>
