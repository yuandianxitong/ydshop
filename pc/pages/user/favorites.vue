<template>
  <div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">我的收藏</h2>

    <div v-if="loading" class="text-center py-10 text-gray-400">加载中...</div>

    <template v-else>
      <div v-if="favorites.length === 0" class="card p-10 text-center">
        <div class="i-carbon-star text-5xl text-gray-300 mx-auto mb-3" />
        <p class="text-gray-400 text-sm">暂无收藏商品</p>
        <NuxtLink to="/goods" class="btn-primary text-sm inline-flex mt-4">去逛逛</NuxtLink>
      </div>

      <template v-else>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
          <div
            v-for="item in favorites"
            :key="item.id"
            class="relative group"
          >
            <!-- Remove button overlay -->
            <button
              class="absolute top-2 right-2 z-10 w-7 h-7 rounded-full bg-white/80 backdrop-blur-sm border border-gray-200 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-50 hover:border-red-300"
              :title="'取消收藏'"
              @click.prevent="handleUnfavorite(item)"
            >
              <span class="i-carbon-close text-gray-500 hover:text-red-500 text-sm" />
            </button>
            <!-- Reuse GoodsCard by mapping FavoriteItem to GoodsItem shape -->
            <GoodsCard :goods="toGoodsItem(item)" />
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="total > pageSize" class="flex items-center justify-center gap-2 mt-6">
          <button
            :disabled="page <= 1"
            class="px-3 py-1.5 text-sm rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="goPage(page - 1)"
          >
            上一页
          </button>
          <template v-for="p in displayPages" :key="p">
            <button
              class="w-8 h-8 text-sm rounded border transition-colors"
              :class="p === page ? 'border-[var(--color-primary)] bg-[var(--color-primary)] text-white' : 'border-gray-200 hover:bg-gray-50'"
              @click="goPage(p)"
            >
              {{ p }}
            </button>
          </template>
          <button
            :disabled="page >= totalPages"
            class="px-3 py-1.5 text-sm rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="goPage(page + 1)"
          >
            下一页
          </button>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import GoodsCard from '~/components/GoodsCard.vue'
import { memberApi, type FavoriteItem } from '~/api/member'
import type { GoodsItem } from '~/api/goods'

const message = useMessage()

const loading = ref(true)
const favorites = ref<FavoriteItem[]>([])
const page = ref(1)
const pageSize = 12
const total = ref(0)

const totalPages = computed(() => Math.ceil(total.value / pageSize))

const displayPages = computed(() => {
  const pages: number[] = []
  const tp = totalPages.value
  const cp = page.value
  let start = Math.max(1, cp - 2)
  let end = Math.min(tp, cp + 2)
  if (end - start < 4) {
    if (start === 1) end = Math.min(tp, start + 4)
    else start = Math.max(1, end - 4)
  }
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

function toGoodsItem(item: FavoriteItem): GoodsItem {
  return {
    id: item.spu_id,
    name: item.spu_name,
    images: item.spu_image ? [item.spu_image] : [],
    min_price: Number(item.min_price ?? 0),
    max_price: Number(item.min_price ?? 0),
    sales_count: 0,
  }
}

async function fetchFavorites() {
  loading.value = true
  try {
    const res = await memberApi.getFavoriteList({ page_no: page.value, page_size: pageSize })
    if (res.code === 200) {
      favorites.value = res.data.list
      total.value = res.data.pagination.total
    }
  } finally {
    loading.value = false
  }
}

function goPage(p: number) {
  if (p < 1 || p > totalPages.value) return
  page.value = p
  fetchFavorites()
}

async function handleUnfavorite(item: FavoriteItem) {
  try {
    const res = await memberApi.toggleFavorite({ spu_id: item.spu_id })
    if (res.code === 200) {
      message.success('已取消收藏')
      // Remove from current list
      favorites.value = favorites.value.filter(f => f.id !== item.id)
      total.value = Math.max(0, total.value - 1)
      // If current page is now empty and not first page, go back a page
      if (favorites.value.length === 0 && page.value > 1) {
        goPage(page.value - 1)
      }
    }
  } catch {
    message.error('操作失败')
  }
}

onMounted(() => {
  fetchFavorites()
})
</script>
