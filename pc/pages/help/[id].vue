<template>
  <div class="bg-gray-50 min-h-screen pb-12">
    <div class="mx-auto max-w-960px px-4 pt-5">
      <!-- Back -->
      <div class="mb-4">
        <NuxtLink to="/help" class="inline-flex items-center text-sm text-gray-500 hover:text-[var(--color-primary)]">
          <span class="i-lucide:arrow-left mr-1" /> 返回帮助中心
        </NuxtLink>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-sm p-8 text-center text-gray-400">加载中...</div>

      <!-- 404 -->
      <div v-else-if="!detail" class="bg-white rounded-sm p-12 text-center">
        <span class="i-lucide:file-question text-4xl text-gray-300 block mb-3" />
        <p class="text-gray-400">帮助不存在或已下架</p>
        <NuxtLink to="/help" class="mt-4 inline-block text-sm text-[var(--color-primary)] hover:underline">返回帮助中心</NuxtLink>
      </div>

      <!-- Content -->
      <template v-else>
        <article class="bg-white rounded-sm p-8">
          <h1 class="text-xl font-bold text-gray-800 leading-snug">{{ detail.title }}</h1>
          <div class="mt-3 flex items-center gap-3 text-xs text-gray-400">
            <span>{{ detail.category_name }}</span>
            <span>·</span>
            <span>阅读 {{ detail.view_count }}</span>
            <span>·</span>
            <span>更新于 {{ detail.updated_at?.slice(0, 16) }}</span>
          </div>
          <div v-if="detail.summary" class="mt-3 p-3 bg-gray-50 rounded text-sm text-gray-600">
            {{ detail.summary }}
          </div>
          <div class="mt-5 help-content prose max-w-none" v-html="sanitizedContent" />
        </article>

        <!-- 相关帮助 -->
        <div v-if="related.length" class="mt-5 bg-white rounded-sm p-6">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">相关帮助</h3>
          <ul class="flex flex-col gap-2">
            <li v-for="r in related" :key="r.id">
              <NuxtLink :to="`/help/${r.id}`" class="text-sm text-gray-600 hover:text-[var(--color-primary)] flex items-center gap-2">
                <span class="i-lucide:chevron-right text-xs text-gray-300" />
                {{ r.title }}
              </NuxtLink>
            </li>
          </ul>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import DOMPurify from 'dompurify'
import { helpApi, type HelpDetail, type HelpItem } from '~/api/help'

const route = useRoute()
const id = computed(() => route.params.id as string)
const detail = ref<HelpDetail | null>(null)
const related = ref<HelpItem[]>([])
const loading = ref(true)

const sanitizedContent = computed(() => {
  if (!detail.value?.content) return ''
  if (import.meta.client) return DOMPurify.sanitize(detail.value.content)
  return detail.value.content
})

async function loadDetail() {
  loading.value = true
  try {
    const res = await helpApi.getDetail(id.value)
    if (res.code === 200) {
      detail.value = res.data
      useHead({ title: `${res.data.title} - 帮助中心` })
      const relRes = await helpApi.getList({ category_id: res.data.category_id, page_size: 6 })
      if (relRes.code === 200) {
        related.value = (relRes.data.list || []).filter(r => r.id !== res.data.id).slice(0, 5)
      }
    } else {
      detail.value = null
    }
  } finally {
    loading.value = false
  }
}

watch(id, loadDetail)
onMounted(loadDetail)
</script>

<style scoped>
.help-content {
  font-size: 14px;
  line-height: 1.8;
  color: #333;
}
.help-content :deep(p) { margin-bottom: 12px; }
.help-content :deep(img) { max-width: 100%; border-radius: 4px; margin: 8px 0; }
.help-content :deep(h1),
.help-content :deep(h2),
.help-content :deep(h3) { font-weight: 600; margin: 16px 0 10px; }
.help-content :deep(ul), .help-content :deep(ol) { padding-left: 24px; margin-bottom: 12px; }
.help-content :deep(li) { margin-bottom: 4px; }
</style>
